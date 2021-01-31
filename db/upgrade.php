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
    global $DB, $CFG;

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

    // WR345138: compatibility fixes for old HWR data migrations.
    if ($oldversion < 2018021800) {
        require_once($CFG->dirroot . '/mod/ojt/locallib.php');

        /* We are currently relying on the previous vendor having already
         * added fields to the TL9 database. When upstreaming later, we
         * will need to explicitly add in the fields so a stock TL9 can
         * create the necessary schema.
         */

        // Ensure 'position' fields in ojt_topic and ojt_topic_item.
        $table = new xmldb_table('ojt_topic_item');
        $field = new xmldb_field('position', XMLDB_TYPE_INTEGER, '4', null, null, null, '0', 'allowselffileuploads');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('ojt_topic');
        $field = new xmldb_field('position', XMLDB_TYPE_INTEGER, '4', null, null, null, '0', 'allowcomments');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ensure OJT topics and items have explicit 'position' field values.
        echo("* Auditing OJT position records...");
        $ojtrs = $DB->get_recordset('ojt');
        foreach ($ojtrs as $ojt) {
            ojt_reorder_topics($ojt);
        }
        echo " Position records updated.\n";

        echo "* Adding 'outcome' field and mapping old status values.\n";
        // Define field outcome to be added to ojt_completion.
        $table = new xmldb_table('ojt_completion');
        $field = new xmldb_field('outcome', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'modifiedby');

        // Conditionally launch add field outcome.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Remapping for assessment status values.
        require_once($CFG->dirroot . '/mod/ojt/lib.php');

        $completionrs = $DB->get_recordset('ojt_completion');
        foreach ($completionrs as $completion) {
            // echo json_encode($completion) . "\n";
            switch($completion->status) {
                case OJT_INCOMPLETE:
                    $completion->status = OJT_INCOMPLETE;
                    $completion->outcome = OJT_OUTCOME_NONE;
                    $DB->update_record('ojt_completion', $completion);
                    break;
                case OJT_REQUIREDCOMPLETE:
                    $completion->status = OJT_REQUIREDCOMPLETE;
                    $completion->outcome = OJT_OUTCOME_NONE; // TODO: Should this be PASSED?
                    $DB->update_record('ojt_completion', $completion);
                    break;
                case OJT_COMPLETE:
                    $completion->status = OJT_COMPLETE;
                    $completion->outcome = OJT_OUTCOME_PASSED;
                    $DB->update_record('ojt_completion', $completion);
                    break;
                case 3: // TL9 HWR's OJT_COMPLETION_FAILED
                    $completion->status = OJT_COMPLETE;
                    $completion->outcome = OJT_OUTCOME_FAILED;
                    $DB->update_record('ojt_completion', $completion);
                    break;
                case 4: // TL9 HWR's OJT_COMPLETION_REASSESSMENT
                    $completion->status = OJT_COMPLETE;
                    $completion->outcome = OJT_OUTCOME_REASSESSMENT;
                    $DB->update_record('ojt_completion', $completion);
                    break;
                default:
                    echo "Unknown status: {$completion->status} for ID: {$completion->id}\n";
                    echo json_encode($completion) . "\n";
            }
        }

        upgrade_mod_savepoint(true, 2018021800, 'ojt');
    }

    if ($oldversion < 2021020100) {
        // WR345138: transitional database schema.

        // Ensure OJT archive table exists.
        $table = new xmldb_table('ojt_archives');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('ojtid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fileid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('filename', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Ensure 'archived' field added to ojt_completion table.
        $table = new xmldb_table('ojt_completion');
        $field = new xmldb_field('archived', XMLDB_TYPE_INTEGER, '2', null, null, null, '0', 'outcome');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Menu topic item fields.
        $table = new xmldb_table('ojt_topic_item');
        $field = new xmldb_field('type', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'name');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('other', XMLDB_TYPE_TEXT, '', null, null, null, null, 'position');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Custom legacy DB fields; unsure if still needed.
        $table = new xmldb_table('ojt');
        $field = new xmldb_field('allowselfevaluation', XMLDB_TYPE_INTEGER, '4', null, null, null, '0', 'itemwitness');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('saveallonsubmit', XMLDB_TYPE_INTEGER, '4', null, null, null, '0', 'allowselfevaluation');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2021020100, 'ojt');
    }


    return true;
}
