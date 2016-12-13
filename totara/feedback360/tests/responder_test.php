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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara
 * @subpackage feedback360
 */
global $CFG;
require_once($CFG->dirroot.'/totara/feedback360/tests/feedback360_testcase.php');

/**
 * Class feedback360_responder_test
 *
 * Tests methods from the feedback360_repsonder class.
 */
class feedback360_responder_test extends feedback360_testcase {

    /**
     * @var testing_data_generator
     */
    private $data_generator;

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);

        $this->data_generator = $this->getDataGenerator();
    }

    public function test_edit() {
        global $DB;
        $this->resetAfterTest();
        list($fdbck, $users) = $this->prepare_feedback_with_users();
        $fdbck->activate();
        $user = current($users);
        $time = time();
        $userassignment = $DB->get_record('feedback360_user_assignment', array('feedback360id' => $fdbck->id,
            'userid' => $user->id));
        $respuser = $this->getDataGenerator()->create_user();
        // Get current time to test timedue against.
        $this->setCurrentTimeStart();
        $response = $this->assign_resp($fdbck, $user->id, $respuser->id);
        $response->viewed = true;
        $response->timeassigned = $time;
        $response->timecompleted = $time + 1;
        $response->save();
        $respid = $response->id;
        unset($response);

        $resptest = new feedback360_responder($respid);
        $this->assertEquals(true, $resptest->viewed);
        $this->assertEquals($time, $resptest->timeassigned);
        $this->assertEquals($time + 1, $resptest->timecompleted);
        $this->assertEquals($fdbck->id, $resptest->feedback360id);
        $this->assertEquals($userassignment->id, $resptest->feedback360userassignmentid);
        $this->assertEquals($respuser->id, $resptest->userid);
        $this->assertEquals(feedback360_responder::TYPE_USER, $resptest->type);
        $this->assertEquals($user->id, $resptest->subjectid);
        $this->assertTimeCurrent($resptest->timedue);
    }

    public function test_by_preview() {
        $this->resetAfterTest();
        list($fdbck) = $this->prepare_feedback_with_users();
        $preview = feedback360_responder::by_preview($fdbck->id);
        $this->assertEquals($fdbck->id, $preview->feedback360id);
        $this->assertTrue($preview->is_fake());
        $this->assertFalse($preview->is_email());
        // Preview simulates user response.
        $this->assertTrue($preview->is_user());
    }

    public function test_by_user() {
        $this->resetAfterTest();
        list($fdbck, $users) = $this->prepare_feedback_with_users();
        $user = current($users);
        $respuser = $this->getDataGenerator()->create_user();
        $response = $this->assign_resp($fdbck, $user->id, $respuser->id);
        $respid = $response->id;
        unset($response);

        $byuser = feedback360_responder::by_user($respuser->id, $fdbck->id, $user->id);
        $this->assertEquals($fdbck->id, $byuser->feedback360id);
        $this->assertFalse($byuser->is_fake());
        $this->assertFalse($byuser->is_email());
        $this->assertTrue($byuser->is_user());
        $this->assertEquals($respid, $byuser->id);
        $this->assertEquals($respuser->id, $byuser->userid);
        $this->assertEquals(feedback360_responder::TYPE_USER, $byuser->type);
        $this->assertEquals($user->id, $byuser->subjectid);
    }

    public function test_by_email() {
        global $CFG, $DB;
        $this->preventResetByRollback();
        $this->resetAfterTest();

        $oldlog = ini_get('error_log');
        ini_set('error_log', "$CFG->dataroot/testlog.log"); // Prevent standard logging.
        unset_config('noemailever');

        list($fdbck, $users) = $this->prepare_feedback_with_users();
        $fdbck->activate();
        $user = current($users);
        $time = time();
        $email = 'somebody@example.com';
        $userassignment = $DB->get_record('feedback360_user_assignment', array('feedback360id' => $fdbck->id,
            'userid' => $user->id));

        // Make sure we are redirecting emails.
        $sink = $this->redirectEmails();
        $this->assertTrue(phpunit_util::is_redirecting_phpmailer());

        feedback360_responder::update_external_assignments(array($email), array(), $userassignment->id, $time);

        // Get the email that we just sent.
        $emails = $sink->get_messages();
        $this->assertCount(1, $sink->get_messages());
        $sink->close();

        $emailassignmentrecord = $DB->get_record('feedback360_email_assignment', array('email' => $email), '*', MUST_EXIST);
        $byemail = feedback360_responder::by_email($email, $emailassignmentrecord->token);
        $this->assertEquals($fdbck->id, $byemail->feedback360id);
        $this->assertFalse($byemail->is_fake());
        $this->assertTrue($byemail->is_email());
        $this->assertFalse($byemail->is_user());
        $this->assertEmpty($byemail->userid);
        $this->assertEquals(feedback360_responder::TYPE_EMAIL, $byemail->type);
        $this->assertEquals($user->id, $byemail->subjectid);
        $this->assertEquals($email, $byemail->email);

        ini_set('error_log', $oldlog);
    }

    public function test_complete() {
        $this->resetAfterTest();
        list($fdbck, $users) = $this->prepare_feedback_with_users();
        $user = current($users);
        $response = $this->assign_resp($fdbck, $user->id);
        $time = time();

        $this->assertFalse($response->is_completed());
        $response->complete($time);
        $this->assertTrue($response->is_completed());
        $this->assertEquals($time, $response->timecompleted);

        $respid = $response->id;
        unset($response);
        $respload = new feedback360_responder($respid);
        $this->assertTrue($respload->is_completed());
        $this->assertEquals($time, $respload->timecompleted);
    }

    public function test_update_timedue() {
        global $DB;

        $this->resetAfterTest();
        list($fdbck, $users) = $this->prepare_feedback_with_users();
        $user = current($users);
        $this->setCurrentTimeStart();
        $response = $this->assign_resp($fdbck, $user->id);
        $this->assertTimeCurrent($response->timedue);

        $respid = $response->id;
        unset($response);
        $userassignment = $DB->get_record('feedback360_user_assignment', array('feedback360id' => $fdbck->id,
            'userid' => $user->id));
        $newtimedue = time() + 86400;
        feedback360_responder::update_timedue($newtimedue, $userassignment->id);
        $resptest = new feedback360_responder($respid);
        $this->assertEquals($newtimedue, $resptest->timedue);
    }

    /**
     * Tests the load() method with system users (users selected based on their Totara user records,
     * rather than by email).
     */
    public function test_load_systemusers() {
        global $DB;

        // Create feedback360 and assign a user for requesting feedback and users for responding.
        list($feedback360, $requesters, $questions) = $this->prepare_feedback_with_users();
        $requester = reset($requesters);

        $this->setCurrentTimeStart();

        // Creating 2 system user assignments.
        $user1 = $this->data_generator->create_user();
        $user2 = $this->data_generator->create_user();
        $systemresponder1 = $this->assign_resp($feedback360, $requester->id, $user1->id);
        $systemresponder2 = $this->assign_resp($feedback360, $requester->id, $user2->id);

        $userassignment = $DB->get_record('feedback360_user_assignment', array('feedback360id' => $feedback360->id,
            'userid' => $requester->id));

        // Creating 2 email user assignments.
        feedback360_responder::update_external_assignments(
            array('email1@example.com', 'email2@example.com'),
            array(),
            $userassignment->id,
            0
        );

        // Grab db records for system users assigned to be responders to feedback.
        $dbrecords = $DB->get_records_select('feedback360_resp_assignment', 'feedback360emailassignmentid IS NULL');
        // We'll just use the first for our comparisons.
        $dbrecord = array_pop($dbrecords);

        $result = new feedback360_responder();
        $result->load($dbrecord->id);

        // Confirm data loaded is correct.
        $this->assertEquals($dbrecord->id, $result->id);
        $this->assertEquals($feedback360->id, $result->feedback360id);
        $this->assertEquals($userassignment->id, $result->feedback360userassignmentid);
        $this->assertEquals($requester->id, $result->subjectid);
        $this->assertEquals(0, $result->viewed);
        $this->assertTimeCurrent($result->timeassigned);
        $this->assertEquals(0, $result->timecompleted);
        $this->assertTimeCurrent($result->timedue);
        $this->assertNull($result->feedback360emailassignmentid);
        $this->assertEquals('', $result->get_email());
        $this->assertEquals('', $result->token);
        $this->assertEquals(feedback360_responder::TYPE_USER, $result->type);
        $this->assertEquals($dbrecord->userid, $result->userid);
    }

    /**
     * Tests the load() method with email-based responders. No user in Totara is specified with these
     * users, instead requests for feedback are based on email addresses only.
     */
    public function test_load_emailresponders() {
        global $DB;

        // Create feedback360 and assign a user for requesting feedback and users for responding.
        list($feedback360, $requesters, $questions) = $this->prepare_feedback_with_users();
        $requester = reset($requesters);

        $this->setCurrentTimeStart();

        // Creating 2 system user assignments.
        $user1 = $this->data_generator->create_user();
        $user2 = $this->data_generator->create_user();
        $systemresponder1 = $this->assign_resp($feedback360, $requester->id, $user1->id);
        $systemresponder2 = $this->assign_resp($feedback360, $requester->id, $user2->id);

        $userassignment = $DB->get_record('feedback360_user_assignment', array('feedback360id' => $feedback360->id,
            'userid' => $requester->id));

        // Creating 2 email user assignments.
        feedback360_responder::update_external_assignments(
            array('email1@example.com', 'email2@example.com'),
            array(),
            $userassignment->id,
            0
        );

        // Grab db records for an email user assigned to be responder to feedback.
        $email1assignmentrecord =  $DB->get_record('feedback360_email_assignment', array('email' => 'email1@example.com'));
        $dbrecord = $DB->get_record('feedback360_resp_assignment', array('feedback360emailassignmentid' => $email1assignmentrecord->id));

        $result = new feedback360_responder();
        $result->load($dbrecord->id);

        // Confirm data loaded is correct.
        $this->assertEquals($dbrecord->id, $result->id);
        $this->assertEquals($feedback360->id, $result->feedback360id);
        $this->assertEquals($userassignment->id, $result->feedback360userassignmentid);
        $this->assertEquals($requester->id, $result->subjectid);
        $this->assertEquals(0, $result->viewed);
        $this->assertTimeCurrent($result->timeassigned);
        $this->assertEquals(0, $result->timecompleted);
        $this->assertTimeCurrent($result->timedue);
        $this->assertNull($result->feedback360emailassignmentid);
        $this->assertEquals('email1@example.com', $result->get_email());
        $this->assertEquals($email1assignmentrecord->token, $result->token);
        $this->assertEquals(feedback360_responder::TYPE_EMAIL, $result->type);
        $this->assertEquals(0, $result->userid);
    }
}
