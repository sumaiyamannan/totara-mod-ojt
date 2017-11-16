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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_appraisal
 */

global $CFG;
require_once($CFG->dirroot.'/totara/appraisal/db/upgradelib.php');
require_once($CFG->dirroot.'/totara/appraisal/tests/appraisal_testcase.php');

/**
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_appraisal_upgradelib_test totara/appraisal/tests/upgradelib_test.php
 */
class totara_appraisal_upgradelib_test extends appraisal_testcase {

    public function test_totara_appraisal_upgrade_update_team_leaders() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $teamlead = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(); // Has job assignment, has role assignment, mismatch userid.
        $user2 = $this->getDataGenerator()->create_user(); // Has job assignment, no role assignment.
        $user3 = $this->getDataGenerator()->create_user(); // No job assignment, has role assignment.
        $user4 = $this->getDataGenerator()->create_user(); // Has job assignment, has role assignment, match.
        $user5 = $this->getDataGenerator()->create_user(); // No job assignment, no role assignment (test with and without empty ja).

        // Set up the job assignments.
        $teamleadja = \totara_job\job_assignment::create_default($teamlead->id);
        $managerja = \totara_job\job_assignment::create_default($manager->id, array('managerjaid' => $teamleadja->id));
        $user1ja = \totara_job\job_assignment::create_default($user1->id, array('managerjaid' => $managerja->id));
        $user2ja = \totara_job\job_assignment::create_default($user2->id, array('managerjaid' => $managerja->id));
        $user3ja = \totara_job\job_assignment::create_default($user3->id, array('managerjaid' => $managerja->id));
        $user4ja = \totara_job\job_assignment::create_default($user4->id, array('managerjaid' => $managerja->id));
        $user5ja = \totara_job\job_assignment::create_default($user5->id);

        // Set up an appraisal and activate it to create the applicable role assignments.
        $roles = array();
        $roles[appraisal::ROLE_TEAM_LEAD] = 6;
        $def = array('name' => 'Appraisal', 'stages' => array(
            array('name' => 'Stage', 'timedue' => time() + 86400, 'pages' => array(
                array('name' => 'Page', 'questions' => array(
                    array('name' => 'Text', 'type' => 'text', 'roles' => $roles)
                ))
            ))
        ));
        $appraisal = appraisal::build($def);

        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $user1->id);
        cohort_add_member($cohort->id, $user2->id);
        cohort_add_member($cohort->id, $user3->id);
        cohort_add_member($cohort->id, $user4->id);
        cohort_add_member($cohort->id, $user5->id);
        $assign = new totara_assign_appraisal('appraisal', $appraisal);
        /** @var totara_assign_appraisal_grouptype_cohort $grouptypeobj */
        $grouptypeobj = $assign->load_grouptype('cohort');
        $grouptypeobj->handle_item_selector(array('includechildren' => false, 'listofvalues' => array($cohort->id)));

        list($errors, $warnings) = $appraisal->validate();
        $this->assertEmpty($errors);
        $this->assertEmpty($warnings);
        $appraisal->activate();
        $this->update_job_assignments($appraisal);
        $this->assertEquals(5, $DB->count_records('appraisal_user_assignment'));
        $this->assertEquals(0, $DB->count_records('appraisal_user_assignment', array('jobassignmentlastmodified' => 0)));

        // Hack user1 so the appraisal team leader doesn't match their job assignment team leader.
        $auamistmatch = $DB->get_record('appraisal_user_assignment', array('userid' => $user1->id));
        $this->assertEquals($teamlead->id, $DB->get_field('appraisal_role_assignment', 'userid',
            array('appraisaluserassignmentid' => $auamistmatch->id, 'appraisalrole' => appraisal::ROLE_TEAM_LEAD)));
        $DB->set_field('appraisal_role_assignment', 'userid', '123', array('appraisaluserassignmentid' => $auamistmatch->id));

        // Run the function, which updates user1.
        totara_appraisal_upgrade_update_team_leaders();

        // See that the correct records were modified.
        $this->assertEquals(5, $DB->count_records('appraisal_user_assignment'));
        $this->assertEquals(1, $DB->count_records('appraisal_user_assignment', array('jobassignmentlastmodified' => 0)));
        $this->assertEquals(1, $DB->count_records('appraisal_user_assignment', array('jobassignmentlastmodified' => 0, 'userid' => $user1->id)));

        // Reset assignments.
        $appraisal->check_assignment_changes();
        $this->assertEquals(5, $DB->count_records('appraisal_user_assignment'));
        $this->assertEquals(0, $DB->count_records('appraisal_user_assignment', array('jobassignmentlastmodified' => 0)));

        // Hack user2 so they have no role assignment.
        $auamissingra = $DB->get_record('appraisal_user_assignment', array('userid' => $user2->id));
        $DB->delete_records('appraisal_role_assignment', array('appraisaluserassignmentid' => $auamissingra->id));

        // Run the function, which updates user2.
        totara_appraisal_upgrade_update_team_leaders();

        // See that the correct records were modified.
        $this->assertEquals(5, $DB->count_records('appraisal_user_assignment'));
        $this->assertEquals(1, $DB->count_records('appraisal_user_assignment', array('jobassignmentlastmodified' => 0)));
        $this->assertEquals(1, $DB->count_records('appraisal_user_assignment', array('jobassignmentlastmodified' => 0, 'userid' => $user2->id)));

        // Reset assignments.
        $appraisal->check_assignment_changes();
        $this->assertEquals(5, $DB->count_records('appraisal_user_assignment'));
        $this->assertEquals(0, $DB->count_records('appraisal_user_assignment', array('jobassignmentlastmodified' => 0)));

        // Hack user3 so they have no job assignment.
        \totara_job\job_assignment::delete($user3ja);

        // Run the function, which updates user3.
        totara_appraisal_upgrade_update_team_leaders();

        // See that the correct records were modified.
        $this->assertEquals(5, $DB->count_records('appraisal_user_assignment'));
        $this->assertEquals(1, $DB->count_records('appraisal_user_assignment', array('jobassignmentlastmodified' => 0)));
        $this->assertEquals(1, $DB->count_records('appraisal_user_assignment', array('jobassignmentlastmodified' => 0, 'userid' => $user3->id)));

        // Reset assignments.
        $appraisal->check_assignment_changes();
        $this->assertEquals(5, $DB->count_records('appraisal_user_assignment'));
        $this->assertEquals(0, $DB->count_records('appraisal_user_assignment', array('jobassignmentlastmodified' => 0)));

        // Hack user5 so they have no job assignment.
        \totara_job\job_assignment::delete($user5ja);

        // Run the function, which updates nothing.
        totara_appraisal_upgrade_update_team_leaders();

        // See that the correct records were modified.
        $this->assertEquals(5, $DB->count_records('appraisal_user_assignment'));
        $this->assertEquals(0, $DB->count_records('appraisal_user_assignment', array('jobassignmentlastmodified' => 0)));
    }

    public function test_totara_appraisal_upgrade_fix_inconsistent_multichoice_param1() {
        $this->resetAfterTest();

        global $DB;

        // Insert some fake data.
        $question = new stdClass();
        $question->appraisalstagepageid = 123;
        $question->name = '1 fix me 1';
        $question->sortorder = 234;
        $question->datatype = 'multichoicemulti';
        $question->requried = 0;
        $question->param1 = '"345"';
        $question->param2 = '"456"';
        $question->param3 = '"567"';
        $question->param4 = '"678"';
        $question->param5 = '"789"';
        $DB->insert_record('appraisal_quest_field', $question);

        $question->name = '2 fix me 2';
        $question->datatype = 'multichoicesingle';
        $question->param1 = '"346"';
        $question->param2 = '456';
        $question->param3 = '{"1":"2","3":"4"}';
        $question->param4 = '[]';
        $question->param5 = null;
        $DB->insert_record('appraisal_quest_field', $question);

        $question->name = '3 leave me type';
        $question->datatype = 'someothertype';
        $question->param1 = '"347"';
        $DB->insert_record('appraisal_quest_field', $question);

        $question->name = '4 leave me int';
        $question->datatype = 'multichoicesingle';
        $question->param1 = '348';
        $DB->insert_record('appraisal_quest_field', $question);

        $question->name = '5 leave me null';
        $question->datatype = 'multichoicemulti';
        $question->param1 = null;
        $DB->insert_record('appraisal_quest_field', $question);

        $question->name = '6 leave me empty array';
        $question->datatype = 'multichoicemulti';
        $question->param1 = '[]';
        $DB->insert_record('appraisal_quest_field', $question);

        $question->name = '7 leave me array';
        $question->datatype = 'multichoicemulti';
        $question->param1 = '{"1":"2","3":"4"}';
        $DB->insert_record('appraisal_quest_field', $question);

        // Construct the expected results.
        $expectedresults = $DB->get_records('appraisal_quest_field', array(), 'name');
        $expectedfixme1 = reset($expectedresults);
        $expectedfixme1->param1 = '345';
        $expectedfixme2 = next($expectedresults);
        $expectedfixme2->param1 = '346';

        // Run the function.
        totara_appraisal_upgrade_fix_inconsistent_multichoice_param1();

        // Check the results.
        $actualresults = $DB->get_records('appraisal_quest_field', array(), 'name');
        $this->assertEquals($expectedresults, $actualresults);
    }
}