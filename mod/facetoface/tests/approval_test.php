<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * facetoface module PHPUnit archive test class
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit mod_facetoface_notifications_testcase mod/facetoface/tests/notifications_test.php
 *
 * @author     David Curry <david.curry@totaralms.com>
 * @package    mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/position/lib.php');

class mod_facetoface_approvals_testcase extends advanced_testcase {

    /**
     * Intercept emails and stores them locally for later verification.
     */
    private $emailsink = null;


    /**
     * Original configuration value to enable sending emails.
     */
    private $cfgemail = null;

    /**
     * PhpUnit fixture method that runs before the test method executes.
     */
    public function setUp() {
        global $CFG;

        parent::setUp();

        $this->preventResetByRollback();
        $this->resetAfterTest();

        $this->emailsink = $this->redirectEmails();
        $this->assertTrue(phpunit_util::is_redirecting_phpmailer());

        $this->cfgemail = isset($CFG->noemailever) ? $CFG->noemailever : null;
        $CFG->noemailever = false;
    }

    // TODO - manager, role, admin notification checks
    public function test_cancellation_send_delete_session() {
/*
        $session = $this->f2f_generate_data();

        // Call facetoface_delete_session function for session1.
        $this->emailsink = $this->redirectEmails();
        facetoface_delete_session($session);
        $this->emailsink->close();

        $emails = $this->get_emails();
        $this->assertCount(4, $emails, 'Wrong no of cancellation notifications sent out.');
 */
    }
}