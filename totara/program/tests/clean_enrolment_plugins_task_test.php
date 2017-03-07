<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_program
*/

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the clean enrolments plugin task.
 */
class totara_clean_enrolments_plugins_task_testcase extends advanced_testcase {

    /**
     * @var testing_data_generator
     */
    protected $generator;

    /**
     * @var totara_program_generator
     */
    protected $generator_program;

    protected $program, $course, $users;
    /**
     * Prepares the environment prior to each test case.
     */
    public function setUp() {
        // Make each generator more easily accessible.
        $this->generator = $this->getDataGenerator();
        $this->generator_program = $this->generator->get_plugin_generator('totara_program');

        $programplugin = enrol_get_plugin('totara_program');
        $programplugin->set_config('unenrolaction', ENROL_EXT_REMOVED_SUSPEND);

        // We have the following initial state.
        //  - 1x programs
        //  - 1x courseset in the program
        //  - 1x course in the courseset
        //  - 2x users enrolled in the program
        //  - 2x users in "in progress"
        $this->program = $this->generator_program->create_program();
        $this->course = $this->getDataGenerator()->create_course();
        $this->generator_program->add_courses_and_courseset_to_program($this->program, [ [$this->course] ]);
        $this->users = array();

        for ($i = 0; $i < 2; $i++) {
            $this->users[$i] = $this->getDataGenerator()->create_user();
            $userids[] = $this->users[$i]->id;
        }
        $this->generator_program->assign_program($this->program->id, $userids);

        // Enrol the users in the course via the program and verify that it was done
        foreach ($this->users as $user) {
            $result = prog_can_enter_course($user, $this->course);
            $this->assertObjectHasAttribute('enroled', $result);
            $this->assertTrue($result->enroled);
        }
    }

    /**
     * Test active user still enrolled in the program
     * No changes expected on user_enrolments
     */
    public function test_active_user_in_program() {
        global $CFG;

        $this->resetAfterTest();

        $CFG->debug = 0;

        foreach ($this->users as $user) {
            $this->verify_prog_assignment($this->program->id, $user->id);
            $this->verify_user_enrolment_status($this->course->id, $user->id, true, ENROL_USER_ACTIVE);
        }

        // Leave both users in the program, run clean_enrolment_plugins_task and ensure nothing changes
        $task = new \totara_program\task\clean_enrolment_plugins_task();
        $task->execute();

        foreach ($this->users as $user) {
            $this->verify_prog_assignment($this->program->id, $user->id);
            $this->verify_user_enrolment_status($this->course->id, $user->id, true, ENROL_USER_ACTIVE);
        }
    }

    /**
     * Test suspended user enrolled in the program
     */
    public function test_suspended_user_in_program() {
        global $CFG;

        $this->resetAfterTest();

        $CFG->debug = 0;

        // Change first user's user_enrolment status to suspended
        $this->update_user_enrolment_status($this->course->id, $this->users[0]->id, ENROL_USER_SUSPENDED);
        $this->verify_prog_assignment($this->program->id, $this->users[0]->id, true);
        $this->verify_user_enrolment_status($this->course->id, $this->users[0]->id, true, ENROL_USER_SUSPENDED);
        $this->verify_prog_assignment($this->program->id, $this->users[1]->id, true);
        $this->verify_user_enrolment_status($this->course->id, $this->users[1]->id, true, ENROL_USER_ACTIVE);

        $task = new \totara_program\task\clean_enrolment_plugins_task();
        $task->execute();

        // Suspended user enrolment should be reverted to active in cron
        foreach ($this->users as $user) {
            $this->verify_prog_assignment($this->program->id, $user->id);
            $this->verify_user_enrolment_status($this->course->id, $user->id, true, ENROL_USER_ACTIVE);
        }
    }


    /**
     * Active user not in program anymore
     */
    public function test_active_user_not_in_program() {
        global $CFG;

        $this->resetAfterTest();

        $CFG->debug = 0;

        // Remove first user from program assignment
        $this->remove_prog_assignment($this->program->id, $this->users[0]->id);
        $this->verify_prog_assignment($this->program->id, $this->users[0]->id, false);
        $this->verify_user_enrolment_status($this->course->id, $this->users[0]->id, true, ENROL_USER_ACTIVE);
        $this->verify_prog_assignment($this->program->id, $this->users[1]->id, true);
        $this->verify_user_enrolment_status($this->course->id, $this->users[1]->id, true, ENROL_USER_ACTIVE);

        $task = new \totara_program\task\clean_enrolment_plugins_task();
        $task->execute();

        // First user should be suspended from course
        $this->verify_prog_assignment($this->program->id, $this->users[0]->id, false);
        $this->verify_user_enrolment_status($this->course->id, $this->users[0]->id, true, ENROL_USER_SUSPENDED);
        $this->verify_prog_assignment($this->program->id, $this->users[1]->id, true);
        $this->verify_user_enrolment_status($this->course->id, $this->users[1]->id, true, ENROL_USER_ACTIVE);
    }

    /**
     * Suspended user not in program anymore
     */
    public function test_suspended_user_not_in_program() {
        global $CFG;

        $this->resetAfterTest();

        $CFG->debug = 0;

        // Change first user's user_enrolment status to suspended
        $this->update_user_enrolment_status($this->course->id, $this->users[0]->id, ENROL_USER_SUSPENDED);
        // Remove first user from program assignment
        $this->remove_prog_assignment($this->program->id, $this->users[0]->id);

        $this->verify_prog_assignment($this->program->id, $this->users[0]->id, false);
        $this->verify_user_enrolment_status($this->course->id, $this->users[0]->id, true, ENROL_USER_SUSPENDED);
        $this->verify_prog_assignment($this->program->id, $this->users[1]->id, true);
        $this->verify_user_enrolment_status($this->course->id, $this->users[1]->id, true, ENROL_USER_ACTIVE);

        $task = new \totara_program\task\clean_enrolment_plugins_task();
        $task->execute();

        // First user should be suspended from course
        $this->verify_prog_assignment($this->program->id, $this->users[0]->id, false);
        $this->verify_user_enrolment_status($this->course->id, $this->users[0]->id, true, ENROL_USER_SUSPENDED);
        $this->verify_prog_assignment($this->program->id, $this->users[1]->id, true);
        $this->verify_user_enrolment_status($this->course->id, $this->users[1]->id, true, ENROL_USER_ACTIVE);
    }

    /**
     * Verify the existence (or not) of the user assignment to the program
     */
    private function verify_prog_assignment($programid, $userid, $expected=true) {
        global $DB;

        $recordexists = $DB->record_exists('prog_user_assignment',
            array('programid' => $programid, 'userid' => $userid));
        $this->assertEquals($expected, $recordexists);
    }

    /**
     * Remove program assignment
     */
    private function remove_prog_assignment($programid, $userid) {
        global $DB;

        $sql = "DELETE FROM {prog_user_assignment}
                 WHERE programid = :programid
                   AND userid = :userid";
        $params = array ('programid' => $programid, 'userid' => $userid);

        $this->assertTrue($DB->execute($sql, $params));
    }

    /**
     * Remove courseset from the program
     */
    private function remove_prog_courseset($programid) {
        global $DB;

        $sql = "DELETE FROM {prog_courseset_course}
                WHERE coursesetid IN (
                    SELECT id
                      FROM {prog_courseset} pc
                     WHERE pc.programid = :programid)";
        $params = array ('programid' => $programid);

        $this->assertTrue($DB->execute($sql, $params));

        $sql = "DELETE FROM {prog_courseset}
                 WHERE programid = :programid";
        $params = array ('programid' => $programid);

        $this->assertTrue($DB->execute($sql, $params));
    }

    /**
     * Verify the existence (or not) of the user's enrolment in the course and also
     * verify the current enrolment status
     */
    private function verify_user_enrolment_status($courseid, $userid, $expected=true, $status=ENROL_USER_ACTIVE) {
        global $DB;

        $sql = "SELECT ue.*
                  FROM {user_enrolments} ue
                  JOIN {enrol} e on ue.enrolid = e.id
                 WHERE ue.userid = :userid
                   AND e.enrol = 'totara_program'
                   AND e.courseid = :courseid";
        $params = array ('userid' => $userid, 'courseid' => $courseid);

        if (!$expected) {
            $this->assertEquals(0, $DB->count_records_sql($sql, $params));
        } else {
            $row = $DB->get_record_sql($sql, $params, IGNORE_MISSING);

            $this->assertEquals($status, $row->status);
        }
    }

    /**
     * Update user enrolment status
     */
    private function update_user_enrolment_status($courseid, $userid, $status) {
        global $DB;

        $sql = "UPDATE {user_enrolments}
                   SET status = :status
                 WHERE userid = :userid
                   AND enrolid IN (
                       SELECT e.id
                         FROM {enrol} e
                        WHERE e.enrol = 'totara_program'
                          AND e.courseid = :courseid)";
        $params = array ('status' => $status, 'userid' => $userid, 'courseid' => $courseid);

        $this->assertTrue($DB->execute($sql, $params));
    }
}
