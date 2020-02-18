<?php

/**
 * Enable the plugin by default
 *
 * @package     auth_catadmin
 * @copyright   2020 Alex Morris <alex.morris@catalyst.net.nz>
 */

defined('MOODLE_INTERNAL');

/**
 * Enable the plugin on installation
 */
function xmldb_auth_catadmin_install() {
    set_config('privatekeypass', get_site_identifier(), 'auth_catadmin');
    set_config('auth', get_config('core', 'auth') . ",catadmin");
}
