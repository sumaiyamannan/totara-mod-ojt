@totara @core_course
Feature: Test we can manually archive course completion.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | course1   | 1                |
    And the following "course enrolments" exist:
      | user     | course  | role    |
      | learner1 | course1 | student |
      | learner2 | course1 | student |

  @javascript
  Scenario: Test completion can be archived with manually enrolled courses
    Given I log in as "admin"
    And I follow "Course 1"
    And I turn editing mode on
    And I add the "Self completion" block
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I click on "criteria_self_value" "checkbox"
    And I press "Save changes"
    And I log out
    And I log in as "learner1"
    And I follow "Course 1"
    And I click on "Complete course" "link"
    And I press "Yes"
    And I log out
    And I log in as "admin"
    And I follow "Course 1"
    And I navigate to "Completions archive" node in "Course administration"
    And I should see "Are you sure you want to archive all completion records"
    And I should see "1 users will be affected"
    And I press "Continue"
    And I should see "1 users completion records have been successfully archived"
    And I press "Continue"
    And I navigate to "Completions archive" node in "Course administration"
    Then I should see "There are no users that have completed this course"


  @javascript
    Scenario: Test completion cannot be archived for program enrolled courses
    Given the following "programs" exist in "totara_program" plugin:
      | fullname                | shortname |
      | Completion archive test | compltest |
    And the following "program assignments" exist in "totara_program" plugin:
      | program   | user     |
      | compltest | learner1 |
    And I log in as "admin"
    And I follow "Course 1"
    And I turn editing mode on
    And I add the "Self completion" block
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I click on "criteria_self_value" "checkbox"
    And I press "Save changes"
    And I click on "Programs" in the totara menu
    And I click on "Completion archive test" "link"
    And I click on "Edit program details" "button"
    And I click on "Content" "link"
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I press "Save changes"
    And I click on "Save all changes" "button"
    And I log out
    And I log in as "learner1"
    And I click on "Courses" in the totara menu
    And I click on "Course 1" "link"
    And I click on "Complete course" "link"
    And I press "Yes"
    And I log out
    And I log in as "admin"
    And I follow "Course 1"
    And I navigate to "Completions archive" node in "Course administration"
    Then I should see "Courses which are a part of a Program or Certification can not be manually archived."
    And I should see "Completion archive test"
    And I should not see "Are you sure you want to archive all completion records"

