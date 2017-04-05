<?php

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * JSON Configuration local lib
 *
 * @package    local_jsonconfig
 * @author     Pierre Guinoiseau <pierre.guinoiseau@catalyst.net.nz>
 * @copyright  2011 Moodle Pty Ltd (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Export site configuration in JSON format.
 * @return string
 */
function local_jsonconfig_export_config_as_json() {
    global $DB;

    $full_config = array('core' => array(), 'plugins' => array());

    // Get core config
    $core_config = $DB->get_records('config', array(), 'name');
    foreach ($core_config as $config) {
        if (local_jsonconfig_ignore_key($config->name)) { continue; }
        $full_config['core'][$config->name] = $config->value;
    }

    // Get plugins config
    $plugins_config = $DB->get_records('config_plugins', array(), 'plugin,name');
    foreach ($plugins_config as $config) {
        if (local_jsonconfig_ignore_key($config->name, $config->plugin)) { continue; }
        if (!array_key_exists($config->plugin, $full_config['plugins'])) {
            $full_config['plugins'][$config->plugin] = array();
        }
        $full_config['plugins'][$config->plugin][$config->name] = $config->value;
    }

    // Make our JSON pretty if using PHP >= 5.4
    if (version_compare(phpversion(), '5.4.0', '<')) {
        $json_options = 0;
    } else {
        $json_options = JSON_PRETTY_PRINT;
    }

    return json_encode($full_config, $json_options);
}

/**
 * Import site configuration from JSON string.
 * @var $config Imported site configuration as a JSON string
 * @return bool true or exception
 */
function local_jsonconfig_import_config_from_json($config) {
    global $DB;

    // JSON decode and syntax check
    $imported_config = json_decode($config, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new moodle_exception('invalid', 'local_jsonconfig');
    }

    try {
        // Import using a transaction, so we can abort and revert the whole thing is there is any error
        $transaction = $DB->start_delegated_transaction();

        // The 'core' and 'plugins' have to be present
        if (!array_key_exists('core', $imported_config) ||
            !array_key_exists('plugins', $imported_config)) {
            throw new moodle_exception('invalid', 'local_jsonconfig');
        }

        // Create / update core config values
        foreach ($imported_config['core'] as $key => $value) {
            if (local_jsonconfig_ignore_key($key)) { continue; }
            set_config($key, $value);
        }

        // Delete core config values missing from import
        $core_keys = $DB->get_records('config', array(), 'name', 'id,name');
        foreach($core_keys as $key) {
            if (local_jsonconfig_ignore_key($key->name)) { continue; }
            if (!array_key_exists($key->name, $imported_config['core'])) {
                set_config($key->name, null);
            }
        }

        // Create / update plugins config values
        foreach ($imported_config['plugins'] as $plugin_name => $plugin_config) {
            foreach ($plugin_config as $key => $value) {
                if (local_jsonconfig_ignore_key($key, $plugin_name)) { continue; }
                set_config($key, $value, $plugin_name);
            }
        }

        // Delete plugins config values missing from import
        $plugins_keys = $DB->get_records('config_plugins', array(), 'plugin,name', 'id,plugin,name');
        foreach($plugins_keys as $key) {
            if (local_jsonconfig_ignore_key($key->name, $key->plugin)) { continue; }
            if (!array_key_exists($key->plugin, $imported_config['plugins']) ||
                !array_key_exists($key->name, $imported_config['plugins'][$key->plugin])) {
                set_config($key->name, null, $key->plugin);
            }
        }

        // No error, commit our changes!
        $transaction->allow_commit();
    } catch(Exception $e) {
        $transaction->rollback($e);
        throw $e;
    }

    return true;
}

/**
 * Compare the current config with the imported one in JSON format
 * @var $config Imported site configuration as a JSON string
 * @return array
 */
function local_jsonconfig_diff_config_with_json($config) {
    global $DB;

    $diff = array('core' => array(), 'plugins' => array());

    // JSON decode and syntax check
    $imported_config = json_decode($config, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new moodle_exception('invalid', 'local_jsonconfig');
    }

    if (!array_key_exists('core', $imported_config) ||
        !array_key_exists('plugins', $imported_config)) {
        throw new moodle_exception('invalid', 'local_jsonconfig');
    }

    // Get current config from the database
    $core_config = $DB->get_records('config', array(), 'name');
    $plugins_config = $DB->get_records('config_plugins', array(), 'plugin,name');

    // Compare core config items
    foreach($core_config as $config) {
        if (local_jsonconfig_ignore_key($config->name)) { continue; }
        if (!array_key_exists($config->name, $imported_config['core'])) {
            // Deleted config item
            $diff['core'][$config->name] =
                array('key' => $config->name, 'value' => $config->value, 'new_value' => null);
        } elseif ($config->value !== $imported_config['core'][$config->name]) {
            // Existing config item
            $diff['core'][$config->name] =
                array('key' => $config->name, 'value' => $config->value,
                      'new_value' => $imported_config['core'][$config->name]);
        }
        unset($imported_config['core'][$config->name]);
    }
    // New config item
    foreach($imported_config['core'] as $key => $value) {
        if (local_jsonconfig_ignore_key($key)) { continue; }
        $diff['core'][$key] = array('key' => $key, 'value' => null, 'new_value' => $value);
    }

    // Compare plugins config items
    foreach($plugins_config as $config) {
        if (local_jsonconfig_ignore_key($config->name, $config->plugin)) { continue; }
        if (!array_key_exists($config->plugin, $imported_config['plugins']) ||
            !array_key_exists($config->name, $imported_config['plugins'][$config->plugin])) {
            // Deleted config item
            if (!array_key_exists($config->plugin, $diff['plugins'])) {
                $diff['plugins'][$config->plugin] = array();
            }
            $diff['plugins'][$config->plugin][$config->name] =
                array('key' => $config->name, 'value' => $config->value, 'new_value' => null);
        } elseif ($config-> value !== $imported_config['plugins'][$config->plugin][$config->name]) {
            // Existing config item
            if (!array_key_exists($config->plugin, $diff['plugins'])) {
                $diff['plugins'][$config->plugin] = array();
            }
            $diff['plugins'][$config->plugin][$config->name] =
                array('key' => $config->name, 'value' => $config->value,
                      'new_value' => $imported_config['plugins'][$config->plugin][$config->name]);
        }
        unset($imported_config['plugins'][$config->plugin][$config->name]);
        if (empty($imported_config['plugins'][$config->plugin])) {
            unset($imported_config['plugins'][$config->plugin]);
        }
    }
    // New config item
    foreach($imported_config['plugins'] as $plugin => $config) {
        if (!array_key_exists($plugin, $diff['plugins'])) {
            $diff['plugins'][$plugin] = array();
        }
        foreach($config as $key => $value) {
            if (local_jsonconfig_ignore_key($key, $plugin)) { continue; }
            $diff['plugins'][$plugin][$key] = array('key' => $key, 'value' => null, 'new_value' => $value);
        }
    }

    ksort($diff['core']);
    ksort($diff['plugins']);
    foreach(array_keys($diff['plugins']) as $plugin) {
        ksort($diff['plugins'][$plugin]);
    }

    return $diff;
}

/**
 * Returns a row for the configuration changes diff table
 * @var $config Configuration item
 * @return array
 */
function local_jsonconfig_diff_table_row($config) {
    $value = $config['value'] !== NULL
        ? htmlentities($config['value'])
        : html_writer::tag('em', '('.get_string('new', 'local_jsonconfig').')');
    $new_value = $config['new_value'] !== NULL
        ? htmlentities($config['new_value'])
        : html_writer::tag('em', '('.get_string('deleted', 'local_jsonconfig').')');
    return array($config['key'], $value, $new_value);
}

/**
 * Returns true if key must be skipped
 * @var $key Configuration key
 * @var $plugin Plugin
 * @return boolean
 */
function local_jsonconfig_ignore_key($key, $plugin = null) {
    if ($plugin) {
        // all plugins config
        return in_array($key, array(
            'lastcron', 'version'
        ));
    } else {
        // core config
        // chat_serverhost ignored because it's usually the same as $CFG->wwwroot
        return in_array($key, array(
            'allversionshash', 'chat_serverhost', 'geoipfile', 'geoip2file',
            'siteidentifier'
        ));
    }
}
