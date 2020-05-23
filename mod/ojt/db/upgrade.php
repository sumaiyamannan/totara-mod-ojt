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

    if ($oldversion < 2016031400) {

        $table = new xmldb_table('ojt_topic_item');

        // Define field allowselffileuploads to be added to ojt_topic_item.
        $field = new xmldb_field('allowselffileuploads', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'allowfileuploads');

        // Conditionally launch add field allowselffileuploads.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // ojt savepoint reached.
        upgrade_mod_savepoint(true, 2016031400, 'ojt');
    }
    
    // KINEO CCM MPIHAS-384
    // Add sort position to topics items
    if ($oldversion < 2017011101) {
        $table = new xmldb_table('ojt_topic_item');

        // Define field position to be added to ojt_topic_item.
        $field = new xmldb_field('position', XMLDB_TYPE_INTEGER, '4', null, null, null, '0', 'allowselffileuploads');

        // Conditionally launch add field position.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // ojt savepoint reached.
        upgrade_mod_savepoint(true, 2017011101, 'ojt');
    }
    
    // KINEO CCM MPIHAS-384
    // Additional feature request
    // Add sort position to topics
    if ($oldversion < 2017011102) {
        $table = new xmldb_table('ojt_topic');

        // Define field position to be added to ojt_topic.
        $field = new xmldb_field('position', XMLDB_TYPE_INTEGER, '4', null, null, null, '0', 'allowcomments');

        // Conditionally launch add field position.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // ojt savepoint reached.
        upgrade_mod_savepoint(true, 2017011102, 'ojt');
    }
    
    // KINEO CCM MPIHAS-523
    if ($oldversion < 2017011103) {
        $table = new xmldb_table('ojt');

        // Define field allowselfevaluation to be added to ojt.
        $field = new xmldb_field('allowselfevaluation', XMLDB_TYPE_INTEGER, '4', null, null, null, '0', 'itemwitness');

        // Conditionally launch add field allowselfevaluation.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // ojt savepoint reached.
        upgrade_mod_savepoint(true, 2017011103, 'ojt');
    }
    
    // KINEO CCM HWRHAS-160
    if ($oldversion < 2017011104) {
        $table = new xmldb_table('ojt_archives');
        
        // Adding fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('ojtid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fileid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('filename', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        // Conditionally launch create table
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // ojt savepoint reached.
        upgrade_mod_savepoint(true, 2017011104, 'ojt');
    }
    
    
    // KINEO CCM HWRHAS-160
    if ($oldversion < 2017011105) {
        // add new field 'archived' in ojt_completion
        $table = new xmldb_table('ojt_completion');

        // Define field archived to be added to ojt_completion.
        $field = new xmldb_field('archived', XMLDB_TYPE_INTEGER, '2', null, null, null, '0', 'modifiedby');

        // Conditionally launch add field ojt_completion.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // ojt savepoint reached.
        upgrade_mod_savepoint(true, 2017011105, 'ojt');
    }
    
    
    // KINEO CCM HWRHAS-162
    if ($oldversion < 2017011108) {
        $table = new xmldb_table('ojt');

        // Define field saveallonsubmit to be added to ojt.
        $field = new xmldb_field('saveallonsubmit', XMLDB_TYPE_INTEGER, '4', null, null, null, '0', 'allowselfevaluation');

        // Conditionally launch add field saveallonsubmit.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // ojt savepoint reached.
        upgrade_mod_savepoint(true, 2017011108, 'ojt');
    }
    
    // KINEO CCM HWRHAS-161
    if ($oldversion < 2017011109) {
        global  $CFG;
        require_once($CFG->dirroot.'/mod/ojt/lib.php');
        
        $table = new xmldb_table('ojt_topic_item');

        // Define field saveallonsubmit to be added to ojt_topic_item.
        $field = new xmldb_field('type', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'name');
        // Conditionally launch add field type.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Define field saveallonsubmit to be added to ojt_topic_item.
        $field = new xmldb_field('other', XMLDB_TYPE_TEXT, '', null, null, null, null, 'position');
        // Conditionally launch add field other.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // update ojt_topic_item
        // set the type for existing items as text - OJT_QUESTION_TYPE_TEXT
        $rs = $DB->get_recordset('ojt_topic_item');

        foreach($rs as $record) {
            if(!empty($record)) {
                $record->type = OJT_QUESTION_TYPE_TEXT;
                $DB->update_record('ojt_topic_item', $record);
            }
        }
        $rs->close();

        // ojt savepoint reached.
        upgrade_mod_savepoint(true, 2017011109, 'ojt');
    }
    
    return true;
}
