<?php
/*
 * Copyright (C) 2015 onwards Catalyst IT
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Eugene Venter <eugene@catalyst.net.nz>
 * @package mod_ojt
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This file keeps track of upgrades to the ojt module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute ojt upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_ojt_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2015022002) {

        // Define competencies field to be added.
        $table = new xmldb_table('ojt_topic');
        $field = new xmldb_field('competencies', XMLDB_TYPE_CHAR, '150', null, null, null, null, 'completionreq');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // OJT savepoint reached.
        upgrade_mod_savepoint(true, 2015022002, 'ojt');
    }

    if ($oldversion < 2015052901) {

        // Define table ojt_item_witness to be created.
        $table = new xmldb_table('ojt_item_witness');

        // Adding fields to table ojt_item_witness.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('topicitemid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('witnessedby', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timewitnessed', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table ojt_item_witness.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('topicitemid', XMLDB_KEY_FOREIGN, array('topicitemid'), 'ojt_topic_item', array('id'));
        $table->add_key('witnessedby', XMLDB_KEY_FOREIGN, array('witnessedby'), 'user', array('id'));

        // Adding indexes to table ojt_item_witness.
        $table->add_index('usertopicitem', XMLDB_INDEX_UNIQUE, array('userid', 'topicitemid'));
        $table->add_index('timewitnessed', XMLDB_INDEX_NOTUNIQUE, array('timewitnessed'));

        // Conditionally launch create table for ojt_item_witness.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // OJT savepoint reached.
        upgrade_mod_savepoint(true, 2015052901, 'ojt');
    }

    if ($oldversion < 2015052902) {

        // Define field allowcomments to be added to ojt_topic.
        $table = new xmldb_table('ojt_topic');
        $field = new xmldb_field('allowcomments', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'completionreq');

        // Conditionally launch add field allowcomments.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field allowfileuploads to be added to ojt_topic_item.
        $table = new xmldb_table('ojt_topic_item');
        $field = new xmldb_field('allowfileuploads', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'completionreq');

        // Conditionally launch add field allowfileuploads.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field managersignoff to be added to ojt.
        $table = new xmldb_table('ojt');
        $field = new xmldb_field('managersignoff', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'itemwitness');

        // Conditionally launch add field managersignoff.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field itemwitness to be added to ojt.
        $field = new xmldb_field('itemwitness', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'timemodified');

        // Conditionally launch add field itemwitness.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // OJT savepoint reached.
        upgrade_mod_savepoint(true, 2015052902, 'ojt');
    }


    return true;
}
