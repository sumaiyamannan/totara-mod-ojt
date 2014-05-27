<?php

// This file is part of Moodle - http://moodle.org/
//
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
 * Resource module admin settings and defaults
 *
 * @package local_bouncechecker
 * @author  Eugene Venter <eugene@catalyst.net.nz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_bouncechecker', get_string('pluginname', 'local_bouncechecker'));

    $settings->add(new admin_setting_configcheckbox('local_bouncechecker/enabled',
        get_string('enabled', 'local_bouncechecker'), '', 0));

    $settings->add(new admin_setting_configtext('local_bouncechecker/mailserver',
        get_string('mailserver', 'local_bouncechecker'), get_string('mailserverconfig', 'local_bouncechecker'), 'mail.catalyst.net.nz', PARAM_TEXT, 25));

    $settings->add(new admin_setting_configtext('local_bouncechecker/mailuserame',
        get_string('mailusername', 'local_bouncechecker'), '', '', PARAM_TEXT, 20));

    if ($USER->username == 'catadmin') {
        $settings->add(new admin_setting_configpasswordunmask('local_bouncechecker/mailpassword',
            get_string('mailpassword', 'local_bouncechecker'), '',''));
    }

    $ADMIN->add('localplugins', $settings);

}
