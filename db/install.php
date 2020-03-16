<?php

/**
 * Enable the plugin by default
 *
 * @package     auth_catadmin
 * @copyright   2020 Alex Morris <alex.morris@catalyst.net.nz>
 */

global $CFG;

require_once("{$CFG->dirroot}/auth/catadmin/setuplib.php");

defined('MOODLE_INTERNAL');

/**
 * Enable the plugin on installation
 */
function xmldb_auth_catadmin_install() {
    set_config('privatekeypass', get_site_identifier(), 'auth_catadmin');
    set_config('auth', get_config('core', 'auth') . ",catadmin");

    $catadminsaml = new auth_plugin_catadmin();
    $catadminsaml->initialise();

    $metadata = new \auth_catadmin\task\metadata_refresh();
    $metadata->execute();
}
