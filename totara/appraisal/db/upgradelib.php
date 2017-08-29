<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_appraisal
 */

require_once($CFG->dirroot.'/totara/appraisal/lib.php');
require_once($CFG->dirroot.'/totara/job/classes/job_assignment.php');

use totara_job\job_assignment;

/**
 * Make sure $param1 is json encoded for all aggregate questions.
 */
function appraisals_upgrade_clean_aggregate_params() {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    $aggregates = $DB->get_records('appraisal_quest_field', array('datatype' => 'aggregate'));

    foreach ($aggregates as $aggregate) {
        // We only need to fix comma deliminated strings, skip encoded params.
        if (strpos($aggregate->param1, ']') || strpos($aggregate->param1, '}')) {
            continue;
        }

        $param1 = str_replace('"', '', $aggregate->param1);
        $param1 = explode(',', $param1);
        $aggregate->param1 = json_encode($param1);

        $DB->update_record('appraisal_quest_field', $aggregate);
    }
}

// TL-15900 Update team leaders in dynamic appraisals.
// Due to a bug in job assignments, the timemodified was not being updated when the
// manager path was updated. After fixing it, we need to make sure that all appraisal
// team leaders will be updated in dynamic appraisals. Just reduce the user assignment
// jobassignmentlastmodified field where the current team lead doesn't match.
function totara_appraisal_upgrade_update_team_leaders() {
    global $DB;

    // A team leader job assignment exists, but no team leader has been assigned in the appraisal.
    $sql = "UPDATE {appraisal_user_assignment}
               SET jobassignmentlastmodified = 0
             WHERE jobassignmentlastmodified > 0
               AND EXISTS (SELECT 1
                     FROM {job_assignment} learnerja
                     JOIN {job_assignment} managerja
                       ON learnerja.managerjaid = managerja.id
                     JOIN {job_assignment} teamleadja
                       ON managerja.managerjaid = teamleadja.id
                    WHERE learnerja.id = {appraisal_user_assignment}.jobassignmentid)
           AND NOT EXISTS (SELECT 1
                     FROM {appraisal_role_assignment} teamleadra
                    WHERE teamleadra.appraisaluserassignmentid = {appraisal_user_assignment}.id
                      AND teamleadra.appraisalrole = :teamleaderrole)";
    $DB->execute($sql, array('teamleaderrole' => appraisal::ROLE_TEAM_LEAD));

    // A team leader has been assigned in the appraisal, but no team leader job assignment exists.
    $sql = "UPDATE {appraisal_user_assignment}
               SET jobassignmentlastmodified = 0
             WHERE jobassignmentlastmodified > 0
               AND EXISTS (SELECT 1
                     FROM {appraisal_role_assignment} teamleadra
                    WHERE teamleadra.appraisaluserassignmentid = {appraisal_user_assignment}.id
                      AND teamleadra.appraisalrole = :teamleaderrole
                      AND teamleadra.userid <> 0)
           AND NOT EXISTS (SELECT 1
                     FROM {job_assignment} learnerja
                     JOIN {job_assignment} managerja
                       ON learnerja.managerjaid = managerja.id
                     JOIN {job_assignment} teamleadja
                       ON managerja.managerjaid = teamleadja.id
                    WHERE learnerja.id = {appraisal_user_assignment}.jobassignmentid)";
    $DB->execute($sql, array('teamleaderrole' => appraisal::ROLE_TEAM_LEAD));

    // Both exist, but they don't have matching users.
    $sql = "UPDATE {appraisal_user_assignment}
               SET jobassignmentlastmodified = 0
             WHERE jobassignmentlastmodified > 0
               AND EXISTS (SELECT 1
                     FROM {job_assignment} learnerja
                     JOIN {job_assignment} managerja
                       ON learnerja.managerjaid = managerja.id
                     JOIN {job_assignment} teamleadja
                       ON managerja.managerjaid = teamleadja.id
                     JOIN {appraisal_role_assignment} teamleadra
                       ON teamleadra.appraisalrole = :teamleaderrole
                    WHERE learnerja.id = {appraisal_user_assignment}.jobassignmentid
                      AND teamleadra.appraisaluserassignmentid = {appraisal_user_assignment}.id
                      AND (teamleadja.userid <> teamleadra.userid AND teamleadja.userid IS NOT NULL OR
                           teamleadra.userid = 0))";
    $DB->execute($sql, array('teamleaderrole' => appraisal::ROLE_TEAM_LEAD));
}
