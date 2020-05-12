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

defined('MOODLE_INTERNAL') || die();

function xmldb_auth_catadmin_upgrade($oldverison) {
    global $CFG, $DB;

    if ($oldverison < 2020042801) {
        if ($DB->record_exists('user' , ['username' => 'catadmin'])) {
            $catadminid = $DB->get_field_select('user', 'id', 'username = ?', array('catadmin'));
            $DB->update_record('user', ['id' => $catadminid, 'firstname' => 'Catalyst']);
            $DB->update_record('user', ['id' => $catadminid, 'lastname' => 'TestAccount']);

            $admins = explode(',', $CFG->siteadmins);
            $key = array_search($catadminid, $admins);
            if ($key !== false && $key !== null) {
                unset($admins[$key]);
                set_config('siteadmins', implode(',', $admins));
            }
        }
        upgrade_plugin_savepoint(true, 2020042801, 'auth', 'catadmin');
    }
}
