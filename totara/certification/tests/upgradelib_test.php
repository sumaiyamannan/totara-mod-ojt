<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara_certification
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/certification/lib.php');
require_once($CFG->dirroot . '/totara/certification/db/upgradelib.php');
require_once($CFG->dirroot . '/totara/program/lib.php');

/**
 * Certification module PHPUnit archive test class.
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_certification_upgradelib_testcase totara/certification/tests/upgradelib_test.php
 */
class totara_certification_upgradelib_testcase extends reportcache_advanced_testcase {

    public $users = array();
    public $programs = array();
    public $certifications = array();
    public $numtestusers = 10;
    public $numtestcerts = 10;
    public $numtestprogs = 7;

    protected function tearDown() {
        $this->users = null;
        $this->programs = null;
        $this->certifications = null;
        $this->numtestusers = null;
        $this->numtestcerts = null;
        $this->numtestprogs = null;
        parent::tearDown();
    }

    /**
     * Set up the users, certifications and completions.
     */
    public function setup_completions() {
        // Create users.
        for ($i = 1; $i <= $this->numtestusers; $i++) {
            $this->users[$i] = $this->getDataGenerator()->create_user();
        }

        // Create programs, mostly so that we don't end up with coincidental success due to matching ids.
        for ($i = 1; $i <= $this->numtestprogs; $i++) {
            $this->programs[$i] = $this->getDataGenerator()->create_program();
        }

        // Create certifications.
        for ($i = 1; $i <= $this->numtestcerts; $i++) {
            $this->certifications[$i] = $this->getDataGenerator()->create_certification();
        }

        // Assign users to the certification as individuals.
        foreach ($this->users as $user) {
            foreach ($this->certifications as $prog) {
                $this->getDataGenerator()->assign_to_program($prog->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
            }
        }
    }

    /**
     * Change the state of all completion records to certified, before the window opens.
     */
    public function shift_completions_to_certified($timecompleted) {
        global $DB;

        // Manually change their state.
        $sql = "UPDATE {prog_completion}
                   SET status = :progstatus, timecompleted = :timecompleted, timedue = :timedue
                 WHERE coursesetid = 0";
        $params = array('progstatus' => STATUS_PROGRAM_COMPLETE, 'timecompleted' => $timecompleted,
            'timedue' => $timecompleted + 2000);
        $DB->execute($sql, $params);
        $sql = "UPDATE {certif_completion}
                   SET status = :certstatus, renewalstatus = :renewalstatus, certifpath = :certifpath,
                       timecompleted = :timecompleted, timewindowopens = :timewindowopens, timeexpires = :timeexpires";
        $params = array('certstatus' => CERTIFSTATUS_COMPLETED, 'renewalstatus' => CERTIFRENEWALSTATUS_NOTDUE,
            'certifpath' => CERTIFPATH_RECERT, 'timecompleted' => $timecompleted, 'timewindowopens' => $timecompleted + 1000,
            'timeexpires' => $timecompleted + 2000);
        $DB->execute($sql, $params);
    }

    public function test_certif_upgrade_fix_reassigned_users() {
        global $DB;

        $this->resetAfterTest(true);

        // Create users and certs, assign users.
        $this->setup_completions();

        $now = time();

        // Mark users complete (will be used in history).
        $this->shift_completions_to_certified($now);

        // Check that all records are ok.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

        // Copy current completion records into history.
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            copy_certif_completion_to_hist($certcompletion->certifid, $certcompletion->userid);
        }
        // Create second copy of history records, to test that the unassigned flag is correctly cleared.
        $DB->execute("UPDATE {certif_completion}
                         SET timecompleted = timecompleted - 10000,
                             timewindowopens = timewindowopens - 10000,
                             timeexpires = timeexpires - 10000");
        $certcompletions = $DB->get_records('certif_completion');
        foreach ($certcompletions as $certcompletion) {
            copy_certif_completion_to_hist($certcompletion->certifid, $certcompletion->userid);
        }

        // Check that all history records are valid.
        $histcompletions = $DB->get_records('certif_completion_history');
        foreach ($histcompletions as $histcompletion) {
            $errors = certif_get_completion_errors($histcompletion, null);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numtestusers * $this->numtestcerts * 2, count($histcompletions));

        // Set up the specific test completion data:
        // * Target/base record:
        //      program complete, cert incomplete, history unassigned "certified, before window opens"
        // * Control records
        //      1) cert complete
        //      2) program incomplete
        //      3) history NOT unassigned "certified, before window opens"
        //      4) history unassigned "certified, window HAS opened"
        //      5) history unassigned "certified, before window opens" with date error.
        $controluser = array(
            1 => $this->users[3]->id,
            2 => $this->users[4]->id,
            3 => $this->users[7]->id,
            4 => $this->users[5]->id,
            5 => $this->users[7]->id);
        $controlcert = array(
            1 => $this->certifications[4]->certifid,
            2 => $this->certifications[6]->certifid,
            3 => $this->certifications[3]->certifid,
            4 => $this->certifications[4]->certifid,
            5 => $this->certifications[4]->certifid);

        // Change data to look like the target and control records.
        $DB->execute("UPDATE {certif_completion_history} SET unassigned = 1");
        $certcompletions = $DB->get_records('certif_completion');
        $controlscreated = 0;
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);

            // First make all records look like the ones we are targeting, then make specific changes for each control.
            // Targets: cert incomplete, prog complete, history unassigned "certified, before window opens".
            $certcompletion->status = CERTIFSTATUS_ASSIGNED;
            $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
            $certcompletion->certifpath = CERTIFPATH_CERT;
            $certcompletion->timecompleted = 0;
            $certcompletion->timewindowopens = 0;
            $certcompletion->timeexpires = 0;
            $certcompletion->timemodified = $now;
            $DB->update_record('certif_completion', $certcompletion);

            // Controls.
            if ($certcompletion->userid == $controluser[1] && $certcompletion->certifid == $controlcert[1]) {
                // Control 1: cert complete, prog complete, history unassigned "certified, before window opens".
                $certcompletion->status = CERTIFSTATUS_COMPLETED;
                $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
                $certcompletion->certifpath = CERTIFPATH_RECERT;
                $certcompletion->timecompleted = $now;
                $certcompletion->timewindowopens = $now + 1000;
                $certcompletion->timeexpires = $now + 2000;
                $DB->update_record('certif_completion', $certcompletion);
                $controlscreated++;
            } else if ($certcompletion->userid == $controluser[2] && $certcompletion->certifid == $controlcert[2]) {
                // Control 2: cert incomplete, prog incomplete, history unassigned "certified, before window opens".
                $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
                $progcompletion->timecompleted = 0;
                $DB->update_record('prog_completion', $progcompletion);
                $controlscreated++;
            } else if ($certcompletion->userid == $controluser[3] && $certcompletion->certifid == $controlcert[3]) {
                // Control 3: cert incomplete, prog complete, history NOT unassigned "certified, before window opens".
                $DB->set_field('certif_completion_history', 'unassigned', 0,
                    array('userid' => $controluser[3], 'certifid' => $controlcert[3]));
                $controlscreated++;
            } else if ($certcompletion->userid == $controluser[4] && $certcompletion->certifid == $controlcert[4]) {
                // Control 4: cert incomplete, prog complete, history unassigned "certified, window HAS opened".
                $cchs = $DB->get_records('certif_completion_history',
                    array('userid' => $controluser[4], 'certifid' => $controlcert[4]), 'timeexpires DESC');
                $cchlatest = reset($cchs);
                $ccholder = next($cchs);
                $cchlatest->renewalstatus = CERTIFRENEWALSTATUS_DUE;
                $DB->update_record('certif_completion_history', $cchlatest);
                $DB->delete_records('certif_completion_history', array('id' => $ccholder->id)); // Remove so it doesn't interfere.
                $controlscreated++;
            } else if ($certcompletion->userid == $controluser[5] && $certcompletion->certifid == $controlcert[5]) {
                // Control 5: cert incomplete, prog complete, history unassigned "certified, before window opens" with date error.
                $cchs = $DB->get_records('certif_completion_history',
                    array('userid' => $controluser[5], 'certifid' => $controlcert[5]), 'timeexpires DESC');
                $cchlatest = reset($cchs);
                $cchlatest->timecompleted = $now + 2000;
                $cchlatest->timewindowopens = $now + 1000;
                $cchlatest->timeexpires = $now + 3000;
                $DB->update_record('certif_completion_history', $cchlatest);
                // Leave older history as it is - the latest should be selected, but will fail.
                $controlscreated++;
            }
        }
        $this->assertEquals($controlscreated, 5);
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

        // Check that all records are set up in the specified way.
        $certcompletions = $DB->get_records('certif_completion');
        $controlschecked = 0;
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $state = certif_get_completion_state($certcompletion);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            $history = $DB->get_records('certif_completion_history',
                array('userid' => $certcompletion->userid, 'certifid' => $certcompletion->certifid));
            $historyunassigned = $DB->get_records('certif_completion_history',
                array('userid' => $certcompletion->userid, 'certifid' => $certcompletion->certifid, 'unassigned' => 1));
            if ($certcompletion->userid == $controluser[1] && $certcompletion->certifid == $controlcert[1]) {
                // Control 1: cert complete, prog complete, history unassigned "certified, before window opens".
                $this->assertEquals(CERTIFCOMPLETIONSTATE_CERTIFIED, $state);
                $this->assertEquals(array(), $errors);
                $this->assertEquals(2, count($history));
                $this->assertEquals(2, count($historyunassigned));
                $controlschecked++;
            } else if ($certcompletion->userid == $controluser[2] && $certcompletion->certifid == $controlcert[2]) {
                // Control 2: cert incomplete, prog incomplete, history unassigned "certified, before window opens".
                $this->assertEquals(CERTIFCOMPLETIONSTATE_ASSIGNED, $state);
                $this->assertEquals(array(), $errors);
                $this->assertEquals(2, count($history));
                $this->assertEquals(2, count($historyunassigned));
                $controlschecked++;
            } else if ($certcompletion->userid == $controluser[3] && $certcompletion->certifid == $controlcert[3]) {
                // Control 3: cert incomplete, prog complete, history NOT unassigned "certified, before window opens".
                $this->assertEquals(CERTIFCOMPLETIONSTATE_ASSIGNED, $state);
                $this->assertEquals(array('error:stateassigned-progstatusincorrect' => 'progstatus',
                    'error:stateassigned-progtimecompletednotempty' => 'progtimecompleted'), $errors);
                $this->assertEquals(2, count($history));
                $this->assertEquals(0, count($historyunassigned));
                $controlschecked++;
            } else if ($certcompletion->userid == $controluser[4] && $certcompletion->certifid == $controlcert[4]) {
                // Control 4: cert incomplete, prog complete, history unassigned "certified, window HAS opened".
                $this->assertEquals(CERTIFCOMPLETIONSTATE_ASSIGNED, $state);
                $this->assertEquals(array('error:stateassigned-progstatusincorrect' => 'progstatus',
                    'error:stateassigned-progtimecompletednotempty' => 'progtimecompleted'), $errors);
                $this->assertEquals(1, count($history)); // Older was removed during setup.
                $this->assertEquals(1, count($historyunassigned));
                $controlschecked++;
            } else if ($certcompletion->userid == $controluser[5] && $certcompletion->certifid == $controlcert[5]) {
                // Control 5: cert incomplete, prog complete, history unassigned "certified, before window opens" with date error.
                $this->assertEquals(CERTIFCOMPLETIONSTATE_ASSIGNED, $state);
                $this->assertEquals(array('error:stateassigned-progstatusincorrect' => 'progstatus',
                    'error:stateassigned-progtimecompletednotempty' => 'progtimecompleted'), $errors);
                $this->assertEquals(2, count($history));
                $this->assertEquals(2, count($historyunassigned));
                $controlschecked++;
            } else {
                // Targets: cert incomplete, prog complete, history unassigned "certified, before window opens".
                $this->assertEquals(CERTIFCOMPLETIONSTATE_ASSIGNED, $state);
                $this->assertEquals(array('error:stateassigned-progstatusincorrect' => 'progstatus',
                    'error:stateassigned-progtimecompletednotempty' => 'progtimecompleted'), $errors);
                $this->assertEquals(2, count($history));
                $this->assertEquals(2, count($historyunassigned));
            }
        }
        $this->assertEquals($controlschecked, 5);
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

        // Run the upgrade.
        certif_upgrade_fix_reassigned_users();

        // Check that target records have been fixed and others have been unaffected.
        $certcompletions = $DB->get_records('certif_completion');
        $controlschecked = 0;
        foreach ($certcompletions as $certcompletion) {
            $sql = "SELECT pc.*
                      FROM {prog_completion} pc
                      JOIN {prog} prog ON prog.id = pc.programid
                     WHERE prog.certifid = :certifid AND pc.userid = :userid AND pc.coursesetid = 0";
            $params = array('certifid' => $certcompletion->certifid, 'userid' => $certcompletion->userid);
            $progcompletion = $DB->get_record_sql($sql, $params);
            $state = certif_get_completion_state($certcompletion);
            $errors = certif_get_completion_errors($certcompletion, $progcompletion);
            $history = $DB->get_records('certif_completion_history',
                array('userid' => $certcompletion->userid, 'certifid' => $certcompletion->certifid));
            $historyunassigned = $DB->get_records('certif_completion_history',
                array('userid' => $certcompletion->userid, 'certifid' => $certcompletion->certifid, 'unassigned' => 1));
            if ($certcompletion->userid == $controluser[1] && $certcompletion->certifid == $controlcert[1]) {
                // Control 1: cert complete, prog complete, history unassigned "certified, before window opens".
                $this->assertEquals(CERTIFCOMPLETIONSTATE_CERTIFIED, $state);
                $this->assertEquals(array(), $errors);
                $this->assertEquals(2, count($history));
                $this->assertEquals(2, count($historyunassigned));
                $controlschecked++;
            } else if ($certcompletion->userid == $controluser[2] && $certcompletion->certifid == $controlcert[2]) {
                // Control 2: cert incomplete, prog incomplete, history unassigned "certified, before window opens".
                $this->assertEquals(CERTIFCOMPLETIONSTATE_ASSIGNED, $state);
                $this->assertEquals(array(), $errors);
                $this->assertEquals(2, count($history));
                $this->assertEquals(2, count($historyunassigned));
                $controlschecked++;
            } else if ($certcompletion->userid == $controluser[3] && $certcompletion->certifid == $controlcert[3]) {
                // Control 3: cert incomplete, prog complete, history NOT unassigned "certified, before window opens".
                $this->assertEquals(CERTIFCOMPLETIONSTATE_ASSIGNED, $state);
                $this->assertEquals(array('error:stateassigned-progstatusincorrect' => 'progstatus',
                    'error:stateassigned-progtimecompletednotempty' => 'progtimecompleted'), $errors);
                $this->assertEquals(2, count($history));
                $this->assertEquals(0, count($historyunassigned));
                $controlschecked++;
            } else if ($certcompletion->userid == $controluser[4] && $certcompletion->certifid == $controlcert[4]) {
                // Control 4: cert incomplete, prog complete, history unassigned "certified, window HAS opened".
                $this->assertEquals(CERTIFCOMPLETIONSTATE_ASSIGNED, $state);
                $this->assertEquals(array('error:stateassigned-progstatusincorrect' => 'progstatus',
                    'error:stateassigned-progtimecompletednotempty' => 'progtimecompleted'), $errors);
                $this->assertEquals(1, count($history)); // Older was removed during setup.
                $this->assertEquals(1, count($historyunassigned));
                $controlschecked++;
            } else if ($certcompletion->userid == $controluser[5] && $certcompletion->certifid == $controlcert[5]) {
                // Control 5: cert incomplete, prog complete, history unassigned "certified, before window opens" with date error.
                $this->assertEquals(CERTIFCOMPLETIONSTATE_ASSIGNED, $state);
                $this->assertEquals(array('error:stateassigned-progstatusincorrect' => 'progstatus',
                    'error:stateassigned-progtimecompletednotempty' => 'progtimecompleted'), $errors);
                $this->assertEquals(2, count($history));
                $this->assertEquals(2, count($historyunassigned));
                $controlschecked++;
            } else {
                // Targets: cert complete, prog complete, history has been removed.
                $this->assertEquals(CERTIFCOMPLETIONSTATE_CERTIFIED, $state, $certcompletion);
                $this->assertEquals(array(), $errors);
                $this->assertEquals(1, count($history));
                $this->assertEquals(0, count($historyunassigned));
            }
        }
        $this->assertEquals($controlschecked, 5);
        $this->assertEquals($this->numtestusers * $this->numtestcerts, count($certcompletions));

    }

    /**
     * Tests totara_certification_upgrade_non_zero_prog_completions. This test is pretty much overkill. We really only
     * need to prove that the correct records are deleted and others are not, but this test also shows that the
     * correct records will be recreated when cron next runs.
     */
    public function test_totara_certification_upgrade_non_zero_prog_completions() {
        global $DB;

        $this->resetAfterTest(true);

        $now = time();

        $user1 = $this->getDataGenerator()->create_user(); // Main test user. Also has program with target problem.
        $user2 = $this->getDataGenerator()->create_user(); // Control, same cert, certified already.
        $user3 = $this->getDataGenerator()->create_user(); // Control, same cert, has valid non-zero prog_completion.
        $user4 = $this->getDataGenerator()->create_user(); // Second test user, same cert, should be certified.
        $user5 = $this->getDataGenerator()->create_user(); // Third test user, different cert.
        $user6 = $this->getDataGenerator()->create_user(); // Fourth test user, missing non-zero prog_completion.

        $prog1 = $this->getDataGenerator()->create_program();

        $cert1 = $this->getDataGenerator()->create_certification();
        $cert2 = $this->getDataGenerator()->create_certification();

        // Set default settings for courses.
        set_config('enablecompletion', '1');
        $coursedefaults = array(
            'enablecompletion' => COMPLETION_ENABLED,
            'completionstartonenrol' => 1,
            'completionprogressonview' => 1);
        $course1 = $this->getDataGenerator()->create_course($coursedefaults);
        $course2 = $this->getDataGenerator()->create_course($coursedefaults);
        $course3 = $this->getDataGenerator()->create_course($coursedefaults);
        $course4 = $this->getDataGenerator()->create_course($coursedefaults);

        $this->getDataGenerator()->add_courseset_program($prog1->id, array($course1->id), CERTIFPATH_STD);
        $this->getDataGenerator()->add_courseset_program($cert1->id, array($course2->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($cert1->id, array($course2->id), CERTIFPATH_RECERT);
        $this->getDataGenerator()->add_courseset_program($cert2->id, array($course3->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($cert2->id, array($course4->id), CERTIFPATH_RECERT);

        $this->getDataGenerator()->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $this->getDataGenerator()->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $this->getDataGenerator()->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);
        $this->getDataGenerator()->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user3->id);
        $this->getDataGenerator()->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user4->id);
        $this->getDataGenerator()->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user5->id);
        $this->getDataGenerator()->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user6->id);

        // Check that everything is in the correct state to start with.
        $this->assertEquals(14, $DB->count_records('prog_completion')); // Each user assignment also has a non-zero prog_completion.
        $this->assertEquals(7, $DB->count_records('prog_completion', array('coursesetid' => 0, 'status' => STATUS_PROGRAM_INCOMPLETE)));
        $this->assertEquals(4, $DB->count_records('prog_completion', array('userid' => $user1->id)));

        $where = "coursesetid <> 0 AND status = " . STATUS_COURSESET_INCOMPLETE;
        $this->assertEquals(7, $DB->count_records_select('prog_completion', $where));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where . " AND timestarted = 0"));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where . " AND timedue = 0"));
        $this->assertEquals(7, $DB->count_records_select('prog_completion', $where . " AND timecompleted = 0"));

        // Put data in a state that should either be fixed or left alone.

        // User 2 is certified. The non-zero record should be deleted but shouldn't be recreated.
        list($certcompletion, $progcompletion) = certif_load_completion($cert1->id, $user2->id);
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = $now - DAYSECS * 10;
        $certcompletion->timewindowopens = $now + DAYSECS * 10;
        $certcompletion->timeexpires = $now + DAYSECS * 20;
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $progcompletion->timecompleted = $now - DAYSECS * 10;
        $progcompletion->timedue = $now + DAYSECS * 20;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));

        // User 3's non-zero record is already correct and shouldn't be touched.
        $where = "userid = :userid AND coursesetid <> 0";
        $user3prenonzerocompletion = $DB->get_record_select('prog_completion', $where, array('userid' => $user3->id));
        $user3prenonzerocompletion->timestarted = 123;
        $user3prenonzerocompletion->timedue = 234;
        $user3prenonzerocompletion->timecompleted = 345;
        $DB->update_record('prog_completion', $user3prenonzerocompletion);

        // User 4 has already completed the course requirements for certification, but hasn't been certified.
        $this->getDataGenerator()->enrol_user($user4->id, $course2->id);
        $completion = new completion_completion(array('userid' => $user4->id, 'course' => $course2->id));
        $completion->mark_enrolled();
        $coursecompletion = $DB->get_record('course_completions', array('course' => $course2->id, 'userid' => $user4->id));
        $coursecompletion->timestarted = $now;
        $coursecompletion->timecompleted = $now;
        $coursecompletion->reaggregate = 0;
        $coursecompletion->status = COMPLETION_STATUS_COMPLETE;
        $DB->update_record('course_completions', $coursecompletion);

        // User 6 is already missing their non-zero record.
        $sql = "DELETE FROM {prog_completion}
                 WHERE userid = :userid
                   AND coursesetid <> 0";
        $DB->execute($sql, array('userid' => $user6->id));

        // Make the non-zero records look like they were incorrectly created by pre-patch window open.
        $allparams = array(
            array('programid' => $prog1->id, 'userid' => $user1->id),
            array('programid' => $cert1->id, 'userid' => $user1->id),
            array('programid' => $cert1->id, 'userid' => $user2->id),
            array('programid' => $cert1->id, 'userid' => $user4->id),
            array('programid' => $cert2->id, 'userid' => $user5->id),
        );
        foreach ($allparams as $params) {
            $sql = "UPDATE {prog_completion}
                       SET timestarted = 0,
                           timedue = 0
                     WHERE programid = :programid
                       AND userid = :userid
                       AND coursesetid <> 0";
            $DB->execute($sql, $params);
        }

        // See the dodgy records before upgrading.
        $where = "coursesetid <> 0 AND status = " . STATUS_COURSESET_INCOMPLETE;
        $this->assertEquals(13, $DB->count_records('prog_completion'));
        $this->assertEquals(6, $DB->count_records_select('prog_completion', $where));
        $this->assertEquals(5, $DB->count_records_select('prog_completion', $where . " AND timestarted = 0"));
        $this->assertEquals(5, $DB->count_records_select('prog_completion', $where . " AND timedue = 0"));
        $this->assertEquals(5, $DB->count_records_select('prog_completion', $where . " AND timecompleted = 0"));

        // Save user1's program prog_completion record - it should be unaffected by the upgrade.
        $where = "userid = :userid AND coursesetid <> 0 AND programid = :programid";
        $params = array('userid' => $user1->id, 'programid' => $prog1->id);
        $user1prog1prenonzerocompletion = $DB->get_record_select('prog_completion', $where, $params);

        // Wait one second, so that the existing timestamps will all be older.
        sleep(1);

        // Run the upgrade.
        totara_certification_upgrade_non_zero_prog_completions();

        // Check the results immediately after upgrade.
        $this->assertEquals(9, $DB->count_records('prog_completion'));
        $this->assertEquals(7, $DB->count_records('prog_completion', array('coursesetid' => 0))); // Every user has a course set zero record.
        $where = "userid = :userid AND coursesetid <> 0 AND programid = :programid";
        $params = array('userid' => $user1->id, 'programid' => $prog1->id);
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, $params)); // User1's record for their program is still there.
        $params = array('userid' => $user3->id, 'programid' => $cert1->id);
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, $params)); // User3's record which is complete is still there.

        // Run the scheduled task which recreates missing non-zero course set group prog_completion records.
        $completiontask = new \totara_program\task\completions_task();
        $completiontask->execute();

        // Check the results after cron has run. This is the overkill bit.
        $this->assertEquals(13, $DB->count_records('prog_completion')); // All except user 2 has two records.
        $this->assertEquals(7, $DB->count_records('prog_completion', array('coursesetid' => 0))); // Every user has a course set zero record.

        // Check user 1.
        $this->assertEquals(4, $DB->count_records('prog_completion', array('userid' => $user1->id)));
        // Program hasn't been affected.
        $where = "userid = :userid AND coursesetid <> 0 AND programid = :programid";
        $params = array('userid' => $user1->id, 'programid' => $prog1->id);
        $user1prog1postnonzerocompletion = $DB->get_record_select('prog_completion', $where, $params);
        $this->assertEquals($user1prog1prenonzerocompletion, $user1prog1postnonzerocompletion);
        // Record has been created for the cert.
        $where = "userid = :userid AND coursesetid <> 0 AND programid = :programid";
        $params = array('userid' => $user1->id, 'programid' => $cert1->id);
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, $params));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where . " AND timestarted = 0", $params));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where . " AND timedue = 0", $params));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where . " AND timecompleted = 0", $params));

        // Check user 2 only has course set zero record.
        $this->assertEquals(1, $DB->count_records('prog_completion', array('userid' => $user2->id)));
        $this->assertEquals(1, $DB->count_records('prog_completion', array('userid' => $user2->id, 'coursesetid' => 0)));

        // Check user 3's record has not been touched.
        $where = "userid = :userid AND coursesetid <> 0";
        $user3postnonzerocompletion = $DB->get_record_select('prog_completion', $where, array('userid' => $user3->id));
        $this->assertEquals($user3prenonzerocompletion, $user3postnonzerocompletion);

        // Check user 4 has a new course set zero record and has been certified.
        $this->assertEquals(2, $DB->count_records('prog_completion', array('userid' => $user4->id)));
        $this->assertEquals(1, $DB->count_records('prog_completion', array('userid' => $user4->id, 'coursesetid' => 0)));
        $where = "userid = :userid AND coursesetid <> 0 AND programid = :programid AND status = :status";
        $params = array('userid' => $user4->id, 'programid' => $cert1->id, 'status' => STATUS_COURSESET_COMPLETE);
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, $params));
        list($certcompletion, $progcompletion) = certif_load_completion($cert1->id, $user4->id);
        $this->assertEquals(CERTIFCOMPLETIONSTATE_CERTIFIED, certif_get_completion_state($certcompletion));

        // Check user 5 record has been created.
        $where = "userid = :userid AND coursesetid <> 0 AND programid = :programid";
        $params = array('userid' => $user5->id, 'programid' => $cert2->id);
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, $params));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where . " AND timestarted = 0", $params));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where . " AND timedue = 0", $params));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where . " AND timecompleted = 0", $params));

        // Check user 6 record has been created.
        $where = "userid = :userid AND coursesetid <> 0 AND programid = :programid";
        $params = array('userid' => $user6->id, 'programid' => $cert1->id);
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, $params));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where . " AND timestarted = 0", $params));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where . " AND timedue = 0", $params));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where . " AND timecompleted = 0", $params));
    }
}
