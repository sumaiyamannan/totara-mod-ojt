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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_certification
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');

/**
 * Test messages in certifications.
 *
 * Resending enrolment and unenrolment messages is tested in the programs message test.
 *
 * Includes:
 * program completed
 * program due
 * program overdue
 * course set completed
 * course set due
 * course set overdue
 * recertification window open
 * recertification window due to close
 * failure to recertify
 * learner follow-up
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_certification_messages_testcase
 *
 */
class totara_certification_messages_testcase extends reportcache_advanced_testcase {

    private $cert1, $cert2;
    private $user1, $user2;
    private $sink;

    public function setUp() {
        global $DB;

        parent::setUp();

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Turn off programs. This is to test that it doesn't interfere with certification completion.
        set_config('enableprograms', TOTARA_DISABLEFEATURE);

        // Create users.
        $this->assertEquals(2, $DB->count_records('user'));
        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();
        $this->assertEquals(2 + 2, $DB->count_records('user'));

        // Create two certs.
        $this->assertEquals(0, $DB->count_records('certif'));
        $data = array(
            'cert_activeperiod' => '1 year',
            'cert_windowperiod' => '6 month',
        );
        $this->cert1 = $this->getDataGenerator()->create_certification($data);
        $this->cert2 = $this->getDataGenerator()->create_certification($data);
        $this->assertEquals(2, $DB->count_records('certif'));

        unset_config('noemailever');
        $this->sink = $this->redirectMessages();

        // Make sure the mail is redirecting and the sink is clear.
        $this->assertTrue(phpunit_util::is_redirecting_phpmailer());
        $this->sink->clear();
    }

    /**
     * Make sure that certification completed messages are resent when a user recertifies.
     */
    public function test_certification_completed_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->cert1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_PROGRAM_COMPLETED);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->cert1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->cert1->id, 'messagetype' => MESSAGETYPE_PROGRAM_COMPLETED));

        // Create course.
        $course1 = $this->getDataGenerator()->create_course();

        // Assign courses to cert.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_RECERT,
                'courses' => array($course1)
            ),
        );
        $this->getDataGenerator()->create_coursesets_in_program($this->cert1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id);
        $this->getDataGenerator()->get_plugin_generator('totara_program')->assign_program($this->cert1->id, $usersprogram);

        // Complete courses.
        $this->getDataGenerator()->enrol_user($this->user1->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user2->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Check that both users were sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(2, $emails);
        $this->sink->clear();

        // Check that both message logs exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Reopen the window for user1 - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that only the log for user2 is still there - the log for user1 was deleted when their window opened.
        $this->assertEquals(1, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Recertify user1.
        $this->getDataGenerator()->enrol_user($this->user1->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Check that just user1 received a new message.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // Check both users now have message logs (user2's is old, user1's is new).
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
    }

    /**
     * Make sure that certification due messages are resent when a user's due date is nearly reached.
     */
    public function test_certification_due_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->cert1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_PROGRAM_DUE);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->cert1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->cert1->id, 'messagetype' => MESSAGETYPE_PROGRAM_DUE));
        // Hack the message record to be triggered 100 days before due.
        $DB->set_field('prog_message', 'triggertime', DAYSECS * 100, array('id' => $messageid));

        // Create course.
        $course1 = $this->getDataGenerator()->create_course();

        // Assign courses to cert.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_RECERT,
                'courses' => array($course1)
            ),
        );
        $this->getDataGenerator()->create_coursesets_in_program($this->cert1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id);
        $this->getDataGenerator()->get_plugin_generator('totara_program')->assign_program($this->cert1->id, $usersprogram);

        // Hack the due dates, 10 days from now.
        $duedate = time() + DAYSECS * 10;
        list($certcompl1, $progcompl1) = certif_load_completion($this->cert1->id, $this->user1->id);
        $progcompl1->timedue = $duedate;
        $this->assertTrue(certif_write_completion($certcompl1, $progcompl1));
        list($certcompl2, $progcompl2) = certif_load_completion($this->cert1->id, $this->user2->id);
        $progcompl2->timedue = $duedate;
        $this->assertTrue(certif_write_completion($certcompl2, $progcompl2));

        // Attempt to send any program messages.
        sleep(1); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that both users were sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(2, $emails);
        $this->sink->clear();

        // Check that both message logs exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Mark user1 certified.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Attempt to send any program messages.
        sleep(1); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that both message logs still exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Reopen the window for user1 - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that only the log for user2 is still there - the log for user1 was deleted when their window opened.
        $this->assertEquals(1, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Attempt to send any program messages.
        sleep(1); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that just user1 received a new message.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // Check both users now have message logs (user2's is old, user1's is new).
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
    }

    /**
     * Make sure that certification overdue messages are resent when a user's due date has been passed.
     */
    public function test_certification_overdue_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->cert1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_PROGRAM_OVERDUE);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->cert1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->cert1->id, 'messagetype' => MESSAGETYPE_PROGRAM_OVERDUE));
        // Hack the message record to be triggered 10 days after overdue.
        $DB->set_field('prog_message', 'triggertime', DAYSECS * 10, array('id' => $messageid));

        // Create course.
        $course1 = $this->getDataGenerator()->create_course();

        // Assign courses to cert.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_RECERT,
                'courses' => array($course1)
            ),
        );
        $this->getDataGenerator()->create_coursesets_in_program($this->cert1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id);
        $this->getDataGenerator()->get_plugin_generator('totara_program')->assign_program($this->cert1->id, $usersprogram);

        // Hack the due dates, 20 days ago.
        $duedate = time() - DAYSECS * 20;
        list($certcompl1, $progcompl1) = certif_load_completion($this->cert1->id, $this->user1->id);
        $progcompl1->timedue = $duedate;
        $this->assertTrue(certif_write_completion($certcompl1, $progcompl1));
        list($certcompl2, $progcompl2) = certif_load_completion($this->cert1->id, $this->user2->id);
        $progcompl2->timedue = $duedate;
        $this->assertTrue(certif_write_completion($certcompl2, $progcompl2));

        // Attempt to send any program messages.
        sleep(1); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that both users were sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(2, $emails);
        $this->sink->clear();

        // Check that both message logs exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Mark user1 certified.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Attempt to send any program messages.
        sleep(1); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that both message logs still exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Reopen the window for user1 - move all dates back fifteen months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 15;
        $certcompletion->timeexpires -= DAYSECS * 30 * 15;
        $certcompletion->timecompleted -= DAYSECS * 30 * 15;
        $progcompletion->timedue -= DAYSECS * 30 * 15;
        $progcompletion->timecompleted -= DAYSECS * 30 * 15;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that only the log for user2 is still there - the log for user1 was deleted when their window opened.
        $this->assertEquals(1, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Attempt to send any program messages.
        sleep(1); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that just user1 received a new message.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // Check both users now have message logs (user2's is old, user1's is new).
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
    }

    /**
     * Make sure that courseset completed messages are resent when a user completes the content.
     *
     * This test works with just one course set, so it's pretty much the same as the program complete test.
     * The program tests make sure that multiple courseset messages are sent within a single program.
     */
    public function test_certification_courseset_completed_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->cert1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_COURSESET_COMPLETED);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->cert1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->cert1->id, 'messagetype' => MESSAGETYPE_COURSESET_COMPLETED));

        // Create course.
        $course1 = $this->getDataGenerator()->create_course();

        // Assign courses to cert.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_RECERT,
                'courses' => array($course1)
            ),
        );
        $this->getDataGenerator()->create_coursesets_in_program($this->cert1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id);
        $this->getDataGenerator()->get_plugin_generator('totara_program')->assign_program($this->cert1->id, $usersprogram);

        // Complete courses.
        $this->getDataGenerator()->enrol_user($this->user1->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user2->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Check that both users were sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(2, $emails);
        $this->sink->clear();

        // Check that both message logs exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Reopen the window for user1 - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that only the log for user2 is still there - the log for user1 was deleted when their window opened.
        $this->assertEquals(1, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Recertify user1.
        $this->getDataGenerator()->enrol_user($this->user1->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Check that just user1 received a new message.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // Check both users now have message logs (user2's is old, user1's is new).
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
    }

    /**
     * Make sure that courseset due messages are resent when a user's due date is nearly reached.
     *
     * This test works with just one course set, so it's pretty much the same as the program due test.
     * The program tests make sure that multiple courseset messages are sent within a single program.
     */
    public function test_certification_courseset_due_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->cert1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_COURSESET_DUE);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->cert1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->cert1->id, 'messagetype' => MESSAGETYPE_COURSESET_DUE));
        // Hack the message record to be triggered 100 days before due.
        $DB->set_field('prog_message', 'triggertime', DAYSECS * 100, array('id' => $messageid));

        // Create course.
        $course1 = $this->getDataGenerator()->create_course();

        // Assign courses to cert.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'timeallowed' => DAYSECS * 100,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_RECERT,
                'timeallowed' => DAYSECS * 100,
                'courses' => array($course1)
            ),
        );
        $this->getDataGenerator()->create_coursesets_in_program($this->cert1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id);
        $this->getDataGenerator()->get_plugin_generator('totara_program')->assign_program($this->cert1->id, $usersprogram);

        // Hack the course set due dates, 10 days from now.
        $duedate = time() + DAYSECS * 10;
        $DB->set_field('prog_completion', 'timedue', $duedate,
            array('programid' => $this->cert1->id, 'userid' => $this->user1->id));
        $DB->set_field('prog_completion', 'timedue', $duedate,
            array('programid' => $this->cert1->id, 'userid' => $this->user2->id));

        // Attempt to send any program messages.
        sleep(1); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that both users were sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(2, $emails);
        $this->sink->clear();

        // Check that both message logs exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Mark user1 certified.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Attempt to send any program messages.
        sleep(1); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that both message logs still exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Reopen the window for user1 - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Hack the course set due dates again, 10 days from now.
        $duedate = time() + DAYSECS * 10;
        $DB->set_field('prog_completion', 'timedue', $duedate,
            array('programid' => $this->cert1->id, 'userid' => $this->user1->id));

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that only the log for user2 is still there - the log for user1 was deleted when their window opened.
        $this->assertEquals(1, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Attempt to send any program messages.
        sleep(1); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that just user1 received a new message.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // Check both users now have message logs (user2's is old, user1's is new).
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
    }

    /**
     * Make sure that courseset overdue messages are resent when a user's due date is nearly reached.
     *
     * This test works with just one course set, so it's pretty much the same as the program overdue test.
     * The program tests make sure that multiple courseset messages are sent within a single program.
     */
    public function test_certification_courseset_overdue_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->cert1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_COURSESET_OVERDUE);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->cert1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->cert1->id, 'messagetype' => MESSAGETYPE_COURSESET_OVERDUE));
        // Hack the message record to be triggered 10 days after overdue.
        $DB->set_field('prog_message', 'triggertime', DAYSECS * 10, array('id' => $messageid));

        // Create course.
        $course1 = $this->getDataGenerator()->create_course();

        // Assign courses to cert.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'timeallowed' => DAYSECS * 100,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_RECERT,
                'timeallowed' => DAYSECS * 100,
                'courses' => array($course1)
            ),
        );
        $this->getDataGenerator()->create_coursesets_in_program($this->cert1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id);
        $this->getDataGenerator()->get_plugin_generator('totara_program')->assign_program($this->cert1->id, $usersprogram);

        // Hack the course set due dates, 30 days ago.
        $duedate = time() - DAYSECS * 30;
        $DB->set_field('prog_completion', 'timedue', $duedate,
            array('programid' => $this->cert1->id, 'userid' => $this->user1->id));
        $DB->set_field('prog_completion', 'timedue', $duedate,
            array('programid' => $this->cert1->id, 'userid' => $this->user2->id));

        // Attempt to send any program messages.
        sleep(1); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that both users were sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(2, $emails);
        $this->sink->clear();

        // Check that both message logs exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Mark user1 certified.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Attempt to send any program messages.
        sleep(1); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that both message logs still exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Reopen the window for user1 - move all dates back fifteen months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 15;
        $certcompletion->timeexpires -= DAYSECS * 30 * 15;
        $certcompletion->timecompleted -= DAYSECS * 30 * 15;
        $progcompletion->timedue -= DAYSECS * 30 * 15;
        $progcompletion->timecompleted -= DAYSECS * 30 * 15;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Hack the course set due dates again, 30 days ago.
        $duedate = time() - DAYSECS * 30;
        $DB->set_field('prog_completion', 'timedue', $duedate,
            array('programid' => $this->cert1->id, 'userid' => $this->user1->id));

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that only the log for user2 is still there - the log for user1 was deleted when their window opened.
        $this->assertEquals(1, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Attempt to send any program messages.
        sleep(1); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that just user1 received a new message.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // Check both users now have message logs (user2's is old, user1's is new).
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
    }

    /**
     * Make sure that recertification window open messages are resent when a user recertifies.
     */
    public function test_recertification_window_open_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->cert1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_RECERT_WINDOWOPEN);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->cert1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->cert1->id, 'messagetype' => MESSAGETYPE_RECERT_WINDOWOPEN));

        // Create course.
        $course1 = $this->getDataGenerator()->create_course();

        // Assign courses to cert.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_RECERT,
                'courses' => array($course1)
            ),
        );
        $this->getDataGenerator()->create_coursesets_in_program($this->cert1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id);
        $this->getDataGenerator()->get_plugin_generator('totara_program')->assign_program($this->cert1->id, $usersprogram);

        // Complete courses.
        $this->getDataGenerator()->enrol_user($this->user1->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user2->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Check that neither user was sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that no message logs exist.
        $this->assertEquals(0, $DB->count_records('prog_messagelog'));

        // Reopen the window for both users - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user2->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that two new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(2, $emails);
        $this->sink->clear();

        // Check that there is one each.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Recertify user1.
        $this->getDataGenerator()->enrol_user($this->user1->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Check that neither user was sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that there is still one each.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Reopen the window for user1 - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that one message was received.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // Check that there is still one each (user2's is old, user1's is new).
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
    }

    /**
     * Make sure that recertification window due to close messages are resent when a user recertifies.
     */
    public function test_recertification_window_due_to_close_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->cert1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_RECERT_WINDOWDUECLOSE);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->cert1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->cert1->id, 'messagetype' => MESSAGETYPE_RECERT_WINDOWDUECLOSE));
        // Hack the message record to be triggered 100 days before due.
        $DB->set_field('prog_message', 'triggertime', DAYSECS * 100, array('id' => $messageid));

        // Create course.
        $course1 = $this->getDataGenerator()->create_course();

        // Assign courses to cert.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_RECERT,
                'courses' => array($course1)
            ),
        );
        $this->getDataGenerator()->create_coursesets_in_program($this->cert1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id);
        $this->getDataGenerator()->get_plugin_generator('totara_program')->assign_program($this->cert1->id, $usersprogram);

        // Complete courses.
        $this->getDataGenerator()->enrol_user($this->user1->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user2->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Check that neither user was sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that no message logs exist.
        $this->assertEquals(0, $DB->count_records('prog_messagelog'));

        // Reopen the window for both users - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user2->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that both users were sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(2, $emails);
        $this->sink->clear();

        // Check that both message logs exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Mark user1 certified.
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Attempt to send any program messages.
        sleep(1); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that both message logs still exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Reopen the window for user1 - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that one message was received.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // Check that there is still one each (user2's is old, user1's is new).
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
    }

    /**
     * Make sure that recertification failure to recertify messages are resent on subsequent recertifications.
     *
     * Sequence is: assign, certify, fail to recertify on time (message #1), recertify, fail to recertify on time (message #2)
     */
    public function test_recertification_failure_to_recertify_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->cert1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_RECERT_FAILRECERT);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->cert1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->cert1->id, 'messagetype' => MESSAGETYPE_RECERT_FAILRECERT));

        // Create course.
        $course1 = $this->getDataGenerator()->create_course();

        // Assign courses to cert.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_RECERT,
                'courses' => array($course1)
            ),
        );
        $this->getDataGenerator()->create_coursesets_in_program($this->cert1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id);
        $this->getDataGenerator()->get_plugin_generator('totara_program')->assign_program($this->cert1->id, $usersprogram);

        // Complete courses.
        $this->getDataGenerator()->enrol_user($this->user1->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user2->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Check that neither user was sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that no message logs exist.
        $this->assertEquals(0, $DB->count_records('prog_messagelog'));

        // Reopen the window for both users - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user2->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that neither user was sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that no message logs exist.
        $this->assertEquals(0, $DB->count_records('prog_messagelog'));

        // Fail to recertify on time for both users - move all dates back another six months (total 15).
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 6;
        $certcompletion->timeexpires -= DAYSECS * 30 * 6;
        $certcompletion->timecompleted -= DAYSECS * 30 * 6;
        $progcompletion->timedue -= DAYSECS * 30 * 6;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user2->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 6;
        $certcompletion->timeexpires -= DAYSECS * 30 * 6;
        $certcompletion->timecompleted -= DAYSECS * 30 * 6;
        $progcompletion->timedue -= DAYSECS * 30 * 6;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that two new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(2, $emails);
        $this->sink->clear();

        // Check that there is one each.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Recertify user1.
        $this->getDataGenerator()->enrol_user($this->user1->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Check that neither user was sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that there is still one each.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Reopen the window for user1 - move all dates back nine months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 9;
        $certcompletion->timeexpires -= DAYSECS * 30 * 9;
        $certcompletion->timecompleted -= DAYSECS * 30 * 9;
        $progcompletion->timedue -= DAYSECS * 30 * 9;
        $progcompletion->timecompleted -= DAYSECS * 30 * 9;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that neither user was sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that user2 still has their message log, but user1's was deleted.
        $this->assertEquals(1, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Fail to recertify for user1 - move all dates back another six months (total 15).
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 6;
        $certcompletion->timeexpires -= DAYSECS * 30 * 6;
        $certcompletion->timecompleted -= DAYSECS * 30 * 6;
        $progcompletion->timedue -= DAYSECS * 30 * 6;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Check that one message was received.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // Check that there is still one each (user2's is old, user1's is new).
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
    }

    /**
     * Make sure that certification learner followup messages are resent after learner has certified and time has passed.
     */
    public function test_certification_learner_followup_messages() {
        global $DB;

        // Set up the messages.
        $programmessagemanager = $this->cert1->get_messagesmanager();
        $programmessagemanager->delete();
        $programmessagemanager->add_message(MESSAGETYPE_LEARNER_FOLLOWUP);
        $programmessagemanager->save_messages();
        prog_messages_manager::get_program_messages_manager($this->cert1->id, true); // Causes static cache to be reset.
        $messageid = $DB->get_field('prog_message', 'id',
            array('programid' => $this->cert1->id, 'messagetype' => MESSAGETYPE_LEARNER_FOLLOWUP));
        // Hack the message record to be triggered 10 days after completion.
        $DB->set_field('prog_message', 'triggertime', DAYSECS * 10, array('id' => $messageid));

        // Create course.
        $course1 = $this->getDataGenerator()->create_course();

        // Assign courses to cert.
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => array($course1)
            ),
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_RECERT,
                'courses' => array($course1)
            ),
        );
        $this->getDataGenerator()->create_coursesets_in_program($this->cert1, $coursesetdata);

        // Assign users to program.
        $usersprogram = array($this->user1->id, $this->user2->id);
        $this->getDataGenerator()->get_plugin_generator('totara_program')->assign_program($this->cert1->id, $usersprogram);

        // Assign users and complete some courses.
        $this->getDataGenerator()->enrol_user($this->user1->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();
        $this->getDataGenerator()->enrol_user($this->user2->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Attempt to send any program messages.
        sleep(1); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that no users were sent messages.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that no message logs exist.
        $this->assertEquals(0, $DB->count_records('prog_messagelog'));

        // Move all dates one month back to trigger the follow-up message.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 1;
        $certcompletion->timeexpires -= DAYSECS * 30 * 1;
        $certcompletion->timecompleted -= DAYSECS * 30 * 1;
        $progcompletion->timedue -= DAYSECS * 30 * 1;
        $progcompletion->timecompleted -= DAYSECS * 30 * 1;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user2->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 1;
        $certcompletion->timeexpires -= DAYSECS * 30 * 1;
        $certcompletion->timecompleted -= DAYSECS * 30 * 1;
        $progcompletion->timedue -= DAYSECS * 30 * 1;
        $progcompletion->timecompleted -= DAYSECS * 30 * 1;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Attempt to send any program messages.
        sleep(1); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that both messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(2, $emails);
        $this->sink->clear();

        // Check that both message logs exist.
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user1->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Reopen the window for user1 - move all dates back eight months.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 8;
        $certcompletion->timeexpires -= DAYSECS * 30 * 8;
        $certcompletion->timecompleted -= DAYSECS * 30 * 8;
        $progcompletion->timedue -= DAYSECS * 30 * 8;
        $progcompletion->timecompleted -= DAYSECS * 30 * 8;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Attempt to send any program messages.
        sleep(1); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that only the log for user2 is still there - the log for user1 was deleted when their window opened.
        $this->assertEquals(1, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Complete the cert again for user1.
        $this->getDataGenerator()->enrol_user($this->user1->id, $course1->id);
        $completion = new completion_completion(array('userid' => $this->user1->id, 'course' => $course1->id));
        $completion->mark_complete();

        // Attempt to send any program messages.
        sleep(1); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that no new messages were sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(0, $emails);
        $this->sink->clear();

        // Check that just user2 still has the message log.
        $this->assertEquals(1, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));

        // Move all dates one month back to trigger the follow-up message.
        list($certcompletion, $progcompletion) = certif_load_completion($this->cert1->id, $this->user1->id);
        $certcompletion->timewindowopens -= DAYSECS * 30 * 1;
        $certcompletion->timeexpires -= DAYSECS * 30 * 1;
        $certcompletion->timecompleted -= DAYSECS * 30 * 1;
        $progcompletion->timedue -= DAYSECS * 30 * 1;
        $progcompletion->timecompleted -= DAYSECS * 30 * 1;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();

        // Attempt to send any program messages.
        sleep(1); // Messages are only sent if they were created before "now", so we need to wait one second.
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task = new \totara_program\task\send_messages_task();
        $task->execute();
        ob_end_clean(); // Throw away the buffer content.

        // Check that one new message was sent.
        $emails = $this->sink->get_messages();
        $this->assertCount(1, $emails);
        $this->sink->clear();

        // Check that there is still one each (user2's is old, user1's is new).
        $this->assertEquals(2, $DB->count_records('prog_messagelog'));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
        $this->assertEquals(1, $DB->count_records('prog_messagelog',
            array('userid' => $this->user2->id, 'messageid' => $messageid)));
    }
}
