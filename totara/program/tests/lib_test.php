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
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/program/lib.php');
require_once($CFG->dirroot . '/totara/certification/lib.php');

/**
 * Program module PHPUnit test class.
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_program_lib_testcase totara/program/tests/lib_test.php
 */
class totara_program_lib_testcase extends reportcache_advanced_testcase {

    public $fixusers = array();
    public $fixprograms = array();
    public $fixcertifications = array();
    public $numfixusers = 10;
    public $numfixcerts = 7;
    public $numfixprogs = 10;

    protected function tearDown() {
        $this->fixusers = null;
        $this->fixprograms = null;
        $this->fixcertifications = null;
        $this->numfixusers = null;
        $this->numfixcerts = null;
        $this->numfixprogs = null;
        parent::tearDown();
    }

    /**
     * Test that prog_update_completion handles programs and certs.
     */
    public function test_prog_update_completion_progs_and_certs() {
        global $DB;

        $this->resetAfterTest(true);

        // Set up some stuff.
        $user = $this->getDataGenerator()->create_user();
        $program = $this->getDataGenerator()->create_program();
        $certification = $this->getDataGenerator()->create_certification();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course();
        $course6 = $this->getDataGenerator()->create_course();

        // Add the courses to the program and certification.
        $this->getDataGenerator()->add_courseset_program($program->id,
            array($course1->id, $course2->id));
        $this->getDataGenerator()->add_courseset_program($certification->id,
            array($course3->id, $course4->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($certification->id,
            array($course5->id, $course6->id), CERTIFPATH_RECERT);

        // Assign the user to the program and cert as an individual.
        $this->getDataGenerator()->assign_to_program($program->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);

        // Mark all the courses complete, with traceable time completed.
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course1->id));
        $completion->mark_complete(1000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course2->id));
        $completion->mark_complete(2000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course3->id));
        $completion->mark_complete(3000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course4->id));
        $completion->mark_complete(4000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course5->id));
        $completion->mark_complete(6000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course6->id));
        $completion->mark_complete(5000);

        // Check the existing data.
        $this->assertEquals(2, $DB->count_records('prog_completion', array('coursesetid' => 0)));
        $this->assertEquals(1, $DB->count_records('certif_completion'));

        // Update the certification so that the user is expired.
        list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $user->id);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $certcompletion->status = CERTIFSTATUS_EXPIRED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_EXPIRED;
        $certcompletion->certifpath = CERTIFPATH_CERT;
        $certcompletion->timecompleted = 0;
        $certcompletion->timewindowopens = 0;
        $certcompletion->timeexpires = 0;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion)); // Contains data validation, so we don't need to check it here.

        // The program should currently be complete. Update the program so that the user is incomplete.
        $progcompletion = $DB->get_record('prog_completion',
            array('programid' => $program->id, 'userid' => $user->id, 'coursesetid' => 0));
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(2000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $DB->update_record('prog_completion', $progcompletion);

        // Call prog_update_completion, which should process all programs for the user.
        prog_update_completion($user->id);

        // Verify that the program is marked completed.
        $progcompletion = $DB->get_record('prog_completion',
            array('programid' => $program->id, 'userid' => $user->id, 'coursesetid' => 0));
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(2000, $progcompletion->timecompleted);

        // Verify the the user was marked complete using the dates in the primary cert path courses.
        list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $user->id);
        $this->assertEquals(4000, $certcompletion->timecompleted);
        $this->assertEquals(4000, $progcompletion->timecompleted);

        // Update the certification so that the recertification window is open.
        list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $user->id);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion)); // Contains data validation, so we don't need to check it here.

        // Update the course completions all courses. The recertification should have the new date, but the complete program
        // won't be effected.
        $DB->execute("UPDATE {course_completions} SET timecompleted = timecompleted + 10000");

        // Call prog_update_completion, which should process all programs for the user.
        prog_update_completion($user->id);

        // Verify that the program is marked completed (with the original completion date).
        $progcompletion = $DB->get_record('prog_completion',
            array('programid' => $program->id, 'userid' => $user->id, 'coursesetid' => 0));
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(2000, $progcompletion->timecompleted);

        // Verify the the user was marked complete using the (increased) dates in the recertification path courses.
        list($certcompletion, $progcompletion) = certif_load_completion($certification->id, $user->id);
        $this->assertEquals(16000, $certcompletion->timecompleted);
        $this->assertEquals(16000, $progcompletion->timecompleted);
    }

    /**
     * Test that prog_update_completion processes only the specified programs.
     */
    public function test_prog_update_completion_specific_prog() {
        global $DB;

        $this->resetAfterTest(true);

        // Set up users, programs, courses.
        $user = $this->getDataGenerator()->create_user();
        $program1 = $this->getDataGenerator()->create_program();
        $program2 = $this->getDataGenerator()->create_program();
        $program3 = $this->getDataGenerator()->create_program();
        $program4 = $this->getDataGenerator()->create_program();
        $program5 = $this->getDataGenerator()->create_program();
        $program6 = $this->getDataGenerator()->create_program();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course();
        $course6 = $this->getDataGenerator()->create_course();

        // Add the courses to the programs.
        $this->getDataGenerator()->add_courseset_program($program1->id,
            array($course1->id));
        $this->getDataGenerator()->add_courseset_program($program2->id,
            array($course2->id));
        $this->getDataGenerator()->add_courseset_program($program3->id,
            array($course2->id)); // Note that course2 is used in program2 and program3.
        $this->getDataGenerator()->add_courseset_program($program4->id,
            array($course4->id));
        $this->getDataGenerator()->add_courseset_program($program5->id,
            array($course5->id));
        $this->getDataGenerator()->add_courseset_program($program6->id,
            array($course6->id));

        // Reload the programs, because their content has changed.
        $program1 = new program($program1->id);
        $program2 = new program($program2->id);
        $program3 = new program($program3->id);
        $program4 = new program($program4->id);
        $program5 = new program($program5->id);
        $program6 = new program($program6->id);

        // Assign the user to the programs.
        $this->getDataGenerator()->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->getDataGenerator()->assign_to_program($program2->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->getDataGenerator()->assign_to_program($program3->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->getDataGenerator()->assign_to_program($program4->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->getDataGenerator()->assign_to_program($program5->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        $this->getDataGenerator()->assign_to_program($program6->id, ASSIGNTYPE_INDIVIDUAL, $user->id);

        // Mark all the courses complete, with traceable time completed.
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course1->id));
        $completion->mark_complete(1000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course2->id));
        $completion->mark_complete(2000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course3->id));
        $completion->mark_complete(3000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course4->id));
        $completion->mark_complete(4000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course5->id));
        $completion->mark_complete(5000);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course6->id));
        $completion->mark_complete(6000);

        // Check that all programs are marked complete, and change them back to incomplete.
        $progcompletion = prog_load_completion($program1->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(1000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($program2->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(2000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($program3->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(2000, $progcompletion->timecompleted); // Completed by course2!
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($program4->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(4000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($program5->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(5000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($program6->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(6000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        // Call prog_update_completion with program1 and check that only program1 was marked complete.
        prog_update_completion($user->id, $program1);
        $progcompletion = prog_load_completion($program1->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program2->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program3->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program4->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program5->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program6->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);

        // Call prog_update_completion with course2 and check that program2 and program3 were marked complete (and program1 above).
        prog_update_completion($user->id, null, $course2->id);
        $progcompletion = prog_load_completion($program1->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program2->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program3->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program4->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program5->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program6->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);

        // Call prog_update_completion with no program or course and see that all programs were marked complete.
        prog_update_completion($user->id);
        $progcompletion = prog_load_completion($program1->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program2->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program3->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program4->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program5->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program6->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);

        // Additionally, check that mark_complete is only calling prog_update_completion with the specified course.

        // Change the programs back to incomplete.
        $progcompletion = prog_load_completion($program1->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(1000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($program2->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(2000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($program3->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(2000, $progcompletion->timecompleted); // Completed by course2!
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($program4->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(4000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($program5->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(5000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        $progcompletion = prog_load_completion($program6->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $this->assertEquals(6000, $progcompletion->timecompleted);
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $progcompletion->timecompleted = 0;
        $this->assertTrue(prog_write_completion($progcompletion));

        // Mark just course2 incomplete. The others must not be marked incomplete, so that if mark_complete causes the
        // other programs to be reaggregated then they will also be marked complete and cause the assertions to fail.
        $DB->set_field('course_completions', 'timecompleted', 0, array('course' => $course2->id));

        // Run the funciton and check that only program2 and program3 were marked complete.
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course2->id));
        $completion->mark_complete();
        $progcompletion = prog_load_completion($program1->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program2->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program3->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program4->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program5->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $progcompletion = prog_load_completion($program6->id, $user->id);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
    }

    public function test_prog_reset_course_set_completions() {
        global $DB;

        $this->resetAfterTest(true);

        // Set up some stuff.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $users = array($user1, $user2, $user3, $user4);

        $prog1 = $this->getDataGenerator()->create_program();
        $prog2 = $this->getDataGenerator()->create_program();
        $cert1 = $this->getDataGenerator()->create_certification();
        $cert2 = $this->getDataGenerator()->create_certification();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course();
        $course6 = $this->getDataGenerator()->create_course();
        $courses = array($course1, $course2, $course3, $course4, $course5, $course6);

        // Add the courses to the programs and certifications.
        $this->getDataGenerator()->add_courseset_program($prog1->id, array($course1->id));
        $this->getDataGenerator()->add_courseset_program($prog2->id, array($course2->id));
        $this->getDataGenerator()->add_courseset_program($cert1->id, array($course3->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($cert1->id, array($course4->id), CERTIFPATH_RECERT);
        $this->getDataGenerator()->add_courseset_program($cert2->id, array($course5->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($cert2->id, array($course6->id), CERTIFPATH_RECERT);

        // Assign the users to the programs and certs as individuals.
        $startassigntime = time();
        foreach ($users as $user) {
            $this->getDataGenerator()->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
            $this->getDataGenerator()->assign_to_program($prog2->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
            $this->getDataGenerator()->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
            $this->getDataGenerator()->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
        }
        $endassigntime = time();
        sleep(1);

        // Mark all the users complete in all the courses, causing completion in all programs/certs.
        foreach ($users as $user) {
            foreach ($courses as $course) {
                $completion = new completion_completion(array('userid' => $user->id, 'course' => $course->id));
                $completion->mark_complete(1000);
            }
        }

        // Hack the timedue field - we can't confirm that 0 changes to 0, so put something else in there.
        $DB->set_field('prog_completion', 'timedue', 12345);

        // Check all the data starts out correct.
        $sql = "SELECT pc.*, pcs.certifpath
                  FROM {prog_completion} pc
                  JOIN {prog_courseset} pcs ON pc.coursesetid = pcs.id
                 WHERE pc.coursesetid != 0";
        $progcompletionnonzeropre = $DB->get_records_sql($sql);
        $this->assertEquals(16, count($progcompletionnonzeropre));

        $sql = "SELECT *
                  FROM {prog_completion}
                 WHERE coursesetid = 0";
        $progcompletionzeropre = $DB->get_records_sql($sql);
        $this->assertEquals(16, count($progcompletionzeropre));

        foreach ($progcompletionnonzeropre as $pcnonzero) {
            $this->assertEquals(STATUS_COURSESET_COMPLETE, $pcnonzero->status);
            $this->assertGreaterThanOrEqual($startassigntime, $pcnonzero->timestarted);
            $this->assertLessThanOrEqual($endassigntime, $pcnonzero->timestarted);
            $this->assertEquals(12345, $pcnonzero->timedue);
            $this->assertEquals(1000, $pcnonzero->timecompleted);

            if ($pcnonzero->programid == $prog1->id && $pcnonzero->userid == $user1->id ||
                $pcnonzero->programid == $cert1->id && $pcnonzero->userid == $user2->id && $pcnonzero->certifpath == CERTIFPATH_CERT ||
                $pcnonzero->programid == $cert2->id && $pcnonzero->userid == $user3->id && $pcnonzero->certifpath == CERTIFPATH_RECERT) {
                // Manually modify the data in memory to what we are expecting to happen automatically in the database.
                $pcnonzero->status = STATUS_COURSESET_INCOMPLETE;
                $pcnonzero->timestarted = 0;
                $pcnonzero->timedue = 0;
                $pcnonzero->timecompleted = 0;
            }
        }

        // Run the function that we're testing.
        prog_reset_course_set_completions($prog1->id, $user1->id);
        prog_reset_course_set_completions($cert1->id, $user2->id, CERTIFPATH_CERT);
        prog_reset_course_set_completions($cert2->id, $user3->id, CERTIFPATH_RECERT);

        // Check that modified data matches the expected.
        $sql = "SELECT pc.*, pcs.certifpath
                  FROM {prog_completion} pc
                  JOIN {prog_courseset} pcs ON pc.coursesetid = pcs.id
                 WHERE coursesetid != 0";
        $progcompletionnonzeropost = $DB->get_records_sql($sql);
        $this->assertEquals($progcompletionnonzeropre, $progcompletionnonzeropost);
        $this->assertEquals(16, count($progcompletionnonzeropost));

        // Course set 0 records are unaffected for all users.
        $sql = "SELECT *
                  FROM {prog_completion}
                 WHERE coursesetid = 0";
        $progcompletionzeropost = $DB->get_records_sql($sql);
        $this->assertEquals($progcompletionzeropre, $progcompletionzeropost);
        $this->assertEquals(16, count($progcompletionzeropost));
    }

    public function test_prog_update_available_enrolments_with_one_program() {
        global $DB;

        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        // Create some data.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();
        $user5 = $generator->create_user();
        $user6 = $generator->create_user();
        $user7 = $generator->create_user();
        $user8 = $generator->create_user();
        $course1 = $generator->create_course();
        $course2 = $generator->create_course();
        $prog1 = $generator->create_program();
        $prog2 = $generator->create_program();

        // Assign users to programs.
        $alluserids = array($user1->id, $user2->id, $user3->id, $user4->id, $user5->id, $user6->id, $user7->id, $user8->id);
        $generator->assign_program($prog1->id, $alluserids);
        $generator->assign_program($prog2->id, $alluserids);

        // Assign course to programs.
        $generator->add_courseset_program($prog1->id, array($course1->id));
        $generator->add_courseset_program($prog2->id, array($course2->id));

        // Enrol the users in the courses using the program enrolment plugin.
        $generator->enrol_user($user1->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user2->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user3->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user4->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user5->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user6->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user7->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user8->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user1->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user2->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user3->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user4->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user5->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user6->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user7->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user8->id, $course2->id, null, 'totara_program');

        // Check that the current data is as expected.
        $expecteduserenrolments = $DB->get_records('user_enrolments');
        $this->assertCount(16, $expecteduserenrolments);
        foreach ($expecteduserenrolments as $userenrolment) {
            // All users have active enrolments.
            $this->assertEquals(ENROL_USER_ACTIVE, $userenrolment->status);
        }

        // Set up several users in each state, to ensure that there's no crossover between user data.

        // 1) User1 and user2 are assigned and their enrolment is not suspended.
        // Nothing to do here - all users are already assigned.

        // 2) User3 and user4 are assigned but their enrolment is suspended.
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user3->id));
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user4->id));

        // 3) User5 and user6 are not assigned but their enrolment is not suspended.
        $DB->delete_records('prog_user_assignment', array('userid' => $user5->id));
        $DB->delete_records('prog_user_assignment', array('userid' => $user6->id));

        // 4) User7 and user8 are not assigned and their enrolment is suspended.
        $DB->delete_records('prog_user_assignment', array('userid' => $user7->id));
        $DB->delete_records('prog_user_assignment', array('userid' => $user8->id));
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user7->id));
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user8->id));

        // Load the current set of data.
        $expecteduserenrolments = $DB->get_records('user_enrolments');
        $this->assertCount(16, $expecteduserenrolments);

        // Run the function.
        /* @var enrol_totara_program_plugin $programplugin */
        $programplugin = enrol_get_plugin('totara_program');
        prog_update_available_enrolments($programplugin, $prog1->id);

        // Manually make the same change to the expected data.

        // 1) No change - the user's enrolment is still not suspended.
        // 2) The enrolment is unsuspended.
        // 3) The enrolment is suspended.
        // 4) No change - the user's enrolment is still suspended.
        $enrols = $DB->get_records('enrol', array('enrol' => 'totara_program'));
        foreach ($expecteduserenrolments as $key => $userenrolment) {
            if ($enrols[$userenrolment->enrolid]->courseid == $course1->id) {
                if (in_array($userenrolment->userid, array($user3->id, $user4->id))) {
                    // Users 3 and 4 will be unsuspended from course1.
                    $expecteduserenrolments[$key]->status = ENROL_USER_ACTIVE;
                } else if (in_array($userenrolment->userid, array($user5->id, $user6->id))) {
                    // Users 5 and 6 will be suspended from course1.
                    $expecteduserenrolments[$key]->status = ENROL_USER_SUSPENDED;
                }
            }
        }

        // And check that the expected records match the actual records.
        $actualuserenrolments = $DB->get_records('user_enrolments');
        $this->assertCount(16, $actualuserenrolments);
        foreach ($actualuserenrolments as $actualuserenrolment) {
            $expecteduserenrolment = $expecteduserenrolments[$actualuserenrolment->id];
            $this->assertEquals($expecteduserenrolment, $actualuserenrolment);
        }
    }

    public function test_prog_update_available_enrolments_with_all_programs() {
        global $DB;

        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        // Create some data.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();
        $user5 = $generator->create_user();
        $user6 = $generator->create_user();
        $user7 = $generator->create_user();
        $user8 = $generator->create_user();
        $course1 = $generator->create_course();
        $course2 = $generator->create_course();
        $prog1 = $generator->create_program();
        $prog2 = $generator->create_program();

        // Assign users to programs.
        $alluserids = array($user1->id, $user2->id, $user3->id, $user4->id, $user5->id, $user6->id, $user7->id, $user8->id);
        $generator->assign_program($prog1->id, $alluserids);
        $generator->assign_program($prog2->id, $alluserids);

        // Assign course to programs.
        $generator->add_courseset_program($prog1->id, array($course1->id));
        $generator->add_courseset_program($prog2->id, array($course2->id));

        // Enrol the users in the courses using the program enrolment plugin.
        $generator->enrol_user($user1->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user2->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user3->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user4->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user5->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user6->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user7->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user8->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user1->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user2->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user3->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user4->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user5->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user6->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user7->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user8->id, $course2->id, null, 'totara_program');

        // Check that the current data is as expected.
        $expecteduserenrolments = $DB->get_records('user_enrolments');
        $this->assertCount(16, $expecteduserenrolments);
        foreach ($expecteduserenrolments as $userenrolment) {
            // All users have active enrolments.
            $this->assertEquals(ENROL_USER_ACTIVE, $userenrolment->status);
        }

        // Set up several users in each state, to ensure that there's no crossover between user data.

        // 1) User1 and user2 are assigned and their enrolment is not suspended.
        // Nothing to do here - all users are already assigned.

        // 2) User3 and user4 are assigned but their enrolment is suspended.
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user3->id));
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user4->id));

        // 3) User5 and user6 are not assigned but their enrolment is not suspended.
        $DB->delete_records('prog_user_assignment', array('userid' => $user5->id));
        $DB->delete_records('prog_user_assignment', array('userid' => $user6->id));

        // 4) User7 and user8 are not assigned and their enrolment is suspended.
        $DB->delete_records('prog_user_assignment', array('userid' => $user7->id));
        $DB->delete_records('prog_user_assignment', array('userid' => $user8->id));
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user7->id));
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user8->id));

        // Load the current set of data.
        $expecteduserenrolments = $DB->get_records('user_enrolments');
        $this->assertCount(16, $expecteduserenrolments);

        // Run the function.
        /* @var enrol_totara_program_plugin $programplugin */
        $programplugin = enrol_get_plugin('totara_program');
        prog_update_available_enrolments($programplugin);

        // Manually make the same change to the expected data.

        // 1) No change - the user's enrolment is still not suspended.
        // 2) The enrolment is unsuspended.
        // 3) The enrolment is suspended.
        // 4) No change - the user's enrolment is still suspended.
        $enrols = $DB->get_records('enrol', array('enrol' => 'totara_program'));
        foreach ($expecteduserenrolments as $key => $userenrolment) {
            if (in_array($userenrolment->userid, array($user3->id, $user4->id))) {
                // Users 3 and 4 will be unsuspended from both courses.
                $expecteduserenrolments[$key]->status = ENROL_USER_ACTIVE;
            } else if (in_array($userenrolment->userid, array($user5->id, $user6->id))) {
                // Users 5 and 6 will be suspended from both courses.
                $expecteduserenrolments[$key]->status = ENROL_USER_SUSPENDED;
            }
        }

        // And check that the expected records match the actual records.
        $actualuserenrolments = $DB->get_records('user_enrolments');
        $this->assertCount(16, $actualuserenrolments);
        foreach ($actualuserenrolments as $actualuserenrolment) {
            $expecteduserenrolment = $expecteduserenrolments[$actualuserenrolment->id];
            $this->assertEquals($expecteduserenrolment, $actualuserenrolment);
        }
    }

    public function test_prog_update_available_enrolments_with_learning_plan() {
        global $DB;

        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();

        // Create some data.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();
        $user5 = $generator->create_user();
        $user6 = $generator->create_user();
        $user7 = $generator->create_user();
        $user8 = $generator->create_user();
        $course1 = $generator->create_course();
        $course2 = $generator->create_course();
        $prog1 = $generator->create_program();
        $prog2 = $generator->create_program();

        // Add programs to learning plans.
        $alluserids = array($user1->id, $user2->id, $user3->id, $user4->id, $user5->id, $user6->id, $user7->id, $user8->id);
        $plan = array();
        $component = array();
        foreach ($alluserids as $userid) {
            $plan[$userid] = $generator->create_plan($userid);
            $component[$userid] = $plan[$userid]->get_component('program');
            $component[$userid]->assign_new_item($prog1->id, false);
            $component[$userid]->assign_new_item($prog2->id, false);
        }

        // Assign course to programs.
        $generator->add_courseset_program($prog1->id, array($course1->id));
        $generator->add_courseset_program($prog2->id, array($course2->id));

        // Enrol the users in the courses using the program enrolment plugin.
        $generator->enrol_user($user1->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user2->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user3->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user4->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user5->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user6->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user7->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user8->id, $course1->id, null, 'totara_program');
        $generator->enrol_user($user1->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user2->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user3->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user4->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user5->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user6->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user7->id, $course2->id, null, 'totara_program');
        $generator->enrol_user($user8->id, $course2->id, null, 'totara_program');

        // Check that the current data is as expected.
        $expecteduserenrolments = $DB->get_records('user_enrolments');
        $this->assertCount(16, $expecteduserenrolments);
        foreach ($expecteduserenrolments as $userenrolment) {
            // All users have active enrolments.
            $this->assertEquals(ENROL_USER_ACTIVE, $userenrolment->status);
        }

        // Set up several users in each state, to ensure that there's no crossover between user data.

        // 1) User1 and user2 are assigned and their enrolment is not suspended.
        // Nothing to do here - all users are already assigned.

        // 2) User3 and user4 are assigned but their enrolment is suspended.
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user3->id));
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user4->id));

        // 3) User5 and user6 are not assigned but their enrolment is not suspended.
        $DB->delete_records('dp_plan_program_assign', array('planid' => $plan[$user5->id]->id));
        $DB->delete_records('dp_plan_program_assign', array('planid' => $plan[$user6->id]->id));

        // 4) User7 and user8 are not assigned and their enrolment is suspended.
        $DB->delete_records('dp_plan_program_assign', array('planid' => $plan[$user7->id]->id));
        $DB->delete_records('dp_plan_program_assign', array('planid' => $plan[$user8->id]->id));
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user7->id));
        $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED, array('userid' => $user8->id));

        // Load the current set of data.
        $expecteduserenrolments = $DB->get_records('user_enrolments');
        $this->assertCount(16, $expecteduserenrolments);

        // Run the function.
        /* @var enrol_totara_program_plugin $programplugin */
        $programplugin = enrol_get_plugin('totara_program');
        prog_update_available_enrolments($programplugin);

        // Manually make the same change to the expected data.

        // 1) No change - the user's enrolment is still not suspended.
        // 2) The enrolment is unsuspended.
        // 3) The enrolment is suspended.
        // 4) No change - the user's enrolment is still suspended.
        $enrols = $DB->get_records('enrol', array('enrol' => 'totara_program'));
        foreach ($expecteduserenrolments as $key => $userenrolment) {
            if (in_array($userenrolment->userid, array($user3->id, $user4->id))) {
                // Users 3 and 4 will be unsuspended from both courses.
                $expecteduserenrolments[$key]->status = ENROL_USER_ACTIVE;
            } else if (in_array($userenrolment->userid, array($user5->id, $user6->id))) {
                // Users 5 and 6 will be suspended from both courses.
                $expecteduserenrolments[$key]->status = ENROL_USER_SUSPENDED;
            }
        }

        // And check that the expected records match the actual records.
        $actualuserenrolments = $DB->get_records('user_enrolments');
        $this->assertCount(16, $actualuserenrolments);
        foreach ($actualuserenrolments as $actualuserenrolment) {
            $expecteduserenrolment = $expecteduserenrolments[$actualuserenrolment->id];
            $this->assertEquals($expecteduserenrolment, $actualuserenrolment);
        }
    }

    /**
     * Set up the users, programs and completions for testing the fixer.
     */
    public function setup_fix_completions() {
        $this->resetAfterTest(true);

        // Turn off programs. This is to test that it doesn't interfere with certification completion.
        set_config('enableprograms', TOTARA_DISABLEFEATURE);

        // Create users.
        for ($i = 1; $i <= $this->numfixusers; $i++) {
            $this->fixusers[$i] = $this->getDataGenerator()->create_user();
        }

        // Create programs.
        for ($i = 1; $i <= $this->numfixprogs; $i++) {
            $this->fixprograms[$i] = $this->getDataGenerator()->create_program();
        }

        // Create certifications, mostly so that we don't end up with coincidental success due to matching ids.
        for ($i = 1; $i <= $this->numfixcerts; $i++) {
            $this->fixcertifications[$i] = $this->getDataGenerator()->create_certification();
        }

        // Assign users to the program as individuals.
        foreach ($this->fixusers as $user) {
            foreach ($this->fixprograms as $prog) {
                $this->getDataGenerator()->assign_to_program($prog->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
            }
        }
    }

    /**
     * Test prog_fix_completions - ensure that the correct records are repaired.
     */
    public function test_prog_fix_completions_only_selected() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_fix_completions();

        // Break all the records.
        $sql = "UPDATE {prog_completion} SET timedue = :timedueunknown WHERE coursesetid = 0";
        $DB->execute($sql, array('timedueunknown' => COMPLETION_TIME_UNKNOWN));

        $expectederrors = array('error:timedueunknown' => 'timedue');

        // Check that all records are broken in the specified way.
        $progcompletions = $DB->get_records('prog_completion');
        foreach ($progcompletions as $progcompletion) {
            $errors = prog_get_completion_errors($progcompletion);
            $this->assertEquals($expectederrors, $errors);
        }
        $this->assertCount($this->numfixusers * $this->numfixprogs, $progcompletions);

        $prog = $this->fixprograms[8];
        $user = $this->fixusers[2];

        // Apply the fix to just one user/prog.
        prog_fix_completions('fixassignedtimedueunknown', $prog->id, $user->id);

        // Check that the correct records have been fixed.
        $progcompletions = $DB->get_records('prog_completion');
        foreach ($progcompletions as $progcompletion) {
            $errors = prog_get_completion_errors($progcompletion);
            if ($progcompletion->userid == $user->id && $progcompletion->programid == $prog->id) {
                $this->assertEquals(array(), $errors);
            } else {
                $this->assertEquals($expectederrors, $errors);
            }
        }
        $this->assertEquals($this->numfixusers * $this->numfixprogs, count($progcompletions));

        // Apply the fix to just one user, all progs (don't need to reset, just overlap).
        prog_fix_completions('fixassignedtimedueunknown', 0, $user->id);

        // Check that the correct records have been fixed.
        $progcompletions = $DB->get_records('prog_completion');
        foreach ($progcompletions as $progcompletion) {
            $errors = prog_get_completion_errors($progcompletion);
            if ($progcompletion->userid == $user->id) { // Overlap previous fixes.
                $this->assertEquals(array(), $errors);
            } else {
                $this->assertEquals($expectederrors, $errors);
            }
        }
        $this->assertEquals($this->numfixusers * $this->numfixprogs, count($progcompletions));

        // Apply the fix to just one prog, all users (don't need to reset, just overlap).
        prog_fix_completions('fixassignedtimedueunknown', $prog->id);

        // Check that the correct records have been fixed.
        $progcompletions = $DB->get_records('prog_completion');
        foreach ($progcompletions as $progcompletion) {
            $errors = prog_get_completion_errors($progcompletion);
            if ($progcompletion->userid == $user->id || $progcompletion->programid == $prog->id) { // Overlap previous fixes.
                $this->assertEquals(array(), $errors);
            } else {
                $this->assertEquals($expectederrors, $errors);
            }
        }
        $this->assertEquals($this->numfixusers * $this->numfixprogs, count($progcompletions));

        // Apply the fix to all records (overlaps previous fixes).
        prog_fix_completions('fixassignedtimedueunknown');

        // Check that the correct records have been fixed.
        $progcompletions = $DB->get_records('prog_completion');
        foreach ($progcompletions as $progcompletion) {
            $errors = prog_get_completion_errors($progcompletion);
            $this->assertEquals(array(), $errors);
        }
        $this->assertEquals($this->numfixusers * $this->numfixprogs, count($progcompletions));
    }

    /**
     * Test prog_fix_completions - ensure that records with the specified problem AND other problems are NOT fixed.
     *
     * We will use the fixassignedtimedueunknown fix key. This will set the due date to not set.
     */
    public function test_prog_fix_completions_only_if_isolated_problem() {
        global $DB;

        // Set up some data that is valid.
        $this->setup_fix_completions();
        $timecompleted = time();

        $windowopenprog = $this->fixprograms[8];
        $windowopenuser = $this->fixusers[2];

        // Break all the records - timedue.
        $sql = "UPDATE {prog_completion} SET timedue = 0 WHERE coursesetid = 0";
        $DB->execute($sql);

        // Break some records - timecompleted.
        $sql = "UPDATE {prog_completion}
                   SET timecompleted = 123456789
                 WHERE programid = :programid OR userid = :userid";
        $params = array('programid' => $windowopenprog->id, 'userid' => $windowopenuser->id);
        $DB->execute($sql, $params);

        $expectederrorstimedueonly = array(
            'error:timedueunknown' => 'timedue',
        );

        $expectederrorstimecompleted = array( // Same as above with one extra.
            'error:timedueunknown' => 'timedue',
            'error:stateincomplete-timecompletednotempty' => 'timecompleted',
        );

        // Check that all records are broken in the correct way.
        $progcompletions = $DB->get_records('prog_completion');
        foreach ($progcompletions as $progcompletion) {
            $errors = prog_get_completion_errors($progcompletion);
            if ($progcompletion->userid == $windowopenuser->id || $progcompletion->programid == $windowopenprog->id) {
                $this->assertEquals($expectederrorstimecompleted, $errors);
            } else {
                $this->assertEquals($expectederrorstimedueonly, $errors);
            }
        }
        $this->assertEquals($this->numfixusers * $this->numfixprogs, count($progcompletions));

        // Apply the fixassignedtimedueunknown fix to all users and progs, but won't affect those with timecompleted problem.
        prog_fix_completions('fixassignedtimedueunknown');

        // Check that the correct records have been fixed.
        $progcompletions = $DB->get_records('prog_completion');
        foreach ($progcompletions as $progcompletion) {
            $errors = prog_get_completion_errors($progcompletion);
            if ($progcompletion->userid == $windowopenuser->id || $progcompletion->programid == $windowopenprog->id) {
                $this->assertEquals($expectederrorstimecompleted, $errors);
            } else {
                $this->assertEquals(array(), $errors);
            }
        }
        $this->assertEquals($this->numfixusers * $this->numfixprogs, count($progcompletions));
    }

    /**
     * Test prog_fix_timedue. Set the program due date to COMPLETION_TIME_NOT_SET.
     */
    public function test_prog_fix_timedue() {
        // Expected record is incomplete.
        $expectedprogcompletion = new stdClass();
        $expectedprogcompletion->id = 1007;
        $expectedprogcompletion->programid = 1008;
        $expectedprogcompletion->userid = 1003;
        $expectedprogcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $expectedprogcompletion->timestarted = 0;
        $expectedprogcompletion->timedue = COMPLETION_TIME_NOT_SET;
        $expectedprogcompletion->timecompleted = 0;

        // Check that the expected test data is in a valid state.
        $errors = prog_get_completion_errors($expectedprogcompletion);
        $this->assertEquals(array(), $errors);

        $progcompletion = clone($expectedprogcompletion);

        // Change the record so that the program completion record is wrong.
        $progcompletion->timedue = COMPLETION_TIME_UNKNOWN;

        prog_fix_timedue($progcompletion);

        // Check that the record was changed as expected.
        $this->assertEquals($expectedprogcompletion, $progcompletion);
    }

    public function test_prog_format_log_date() {
        $this->assertEquals('Not set (null)', prog_format_log_date(null));
        $this->assertEquals('Not set ()', prog_format_log_date(''));
        $this->assertEquals('Not set ( )', prog_format_log_date(' '));
        $this->assertEquals('Not set (0)', prog_format_log_date(0));
        $this->assertEquals('Not set (0)', prog_format_log_date('0'));
        $this->assertEquals('Not set (-1)', prog_format_log_date(-1));
        $this->assertEquals('Not set (-1)', prog_format_log_date('-1'));
        $this->assertEquals('23 May 2017, 03:59 (1495511940)', prog_format_log_date(1495511940));
        $this->assertEquals('23 May 2017, 03:59 (1495511940)', prog_format_log_date('1495511940'));
    }
}
