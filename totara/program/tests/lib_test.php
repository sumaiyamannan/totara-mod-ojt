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
}
