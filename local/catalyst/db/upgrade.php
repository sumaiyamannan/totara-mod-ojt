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
 * Catalyst-specific
 *
 * @package local_catalyst
 * @author  Eugene Venter <eugene@catalyst.net.nz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


function xmldb_local_catalyst_upgrade($oldversion) {

    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2016102800) {
        set_config('exportadhoclimit', 15000, 'reportbuilder');

        upgrade_plugin_savepoint(true, 2016102800, 'local', 'catalyst');
    }

    if ($oldversion < 2018102800) {
        $table = new xmldb_table('scorm_scoes_track');
        $index = new xmldb_index('element', XMLDB_INDEX_UNIQUE, ['element']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        // Save point reached.
        upgrade_plugin_savepoint(true, 2018102800, 'local', 'catalyst');
    }

    return true;
}

