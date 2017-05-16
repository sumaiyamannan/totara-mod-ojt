@mod @mod_facetoface @totara @totara_reportbuilder @javascript
Feature: Sign up to a seminar
  In order to attend a seminar
  As a student
  I need to sign up to a seminar session

  # This background requires JS as such it has been added to the Feature tags.
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Terry1    | Teacher1 | teacher1@moodle.com |
      | student1 | Sam1      | Student1 | student1@moodle.com |
      | student2 | Sam2      | Student2 | student2@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And I log in as "teacher1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Label" to section "1" and I fill the form with:
      | Label text | Course view page |
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit date" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 0    |
    And I press "OK"
    And I set the following fields to these values:
      | capacity              | 1    |
    And I press "Save changes"
    And I log out

  Scenario: Sign up to a session and unable to sign up to a full session from the course page
    When I log in as "student1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I should see "Sign-up"
    And I follow "Sign-up"
    And I press "Sign-up"
    And I should see "Your booking has been completed."
    # Check the user is back on the course page.
    And I should see "Course view page"
    And I should not see "All events in Test seminar name"
    And I log out
    And I log in as "student2"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I should not see "Sign-up"

  Scenario: Sign up to a session and unable to sign up to a full session for within the activity
    When I log in as "student1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I should see "Test seminar name"
    And I follow "Test seminar name"
    And I should see "Sign-up"
    And I follow "Sign-up"
    And I press "Sign-up"
    And I should see "Your booking has been completed."
    # Check the user is back on the all events page.
    And I should not see "Course view page"
    And I should see "All events in Test seminar name"
    And I log out
    And I log in as "student2"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I should not see "Sign-up"

  Scenario: Sign up with note and manage it by Editing Teacher
    When I log in as "student1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I should see "Sign-up"
    And I follow "Sign-up"
    And I set the following fields to these values:
     | Requests for session organiser | My test |
    And I press "Sign-up"
    And I should see "Your booking has been completed."
    And I log out

    And I log in as "teacher1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "Attendees"
    When I click on "Edit" "link" in the "Sam1" "table_row"
    Then I should see "Sam1 Student1 - update note"

  @totara_customfield
  Scenario: Sign up with note and ensure that other reports do not have manage button
    When I log in as "student1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I should see "Sign-up"
    And I follow "Sign-up"
    And I set the following fields to these values:
     | Requests for session organiser | My test |
    And I press "Sign-up"
    And I should see "Your booking has been completed."
    And I log out

    And I log in as "admin"
    And I navigate to "Manage reports" node in "Site administration > Reports > Report builder"
    And I set the following fields to these values:
      | Report Name | Other sign-ups   |
      | Source      | Seminar Sign-ups |
    And I press "Create report"
    And I click on "Columns" "link"
    And I set the field "newcolumns" to "All sign up custom fields"
    And I press "Add"
    And I press "Save changes"
    And I click on "Reports" in the totara menu
    When I click on "Other sign-ups" "link"
    Then I should not see "edit" in the "Sam1 Student1" "table_row"

  @totara_customfield
  Scenario: Sign up and cancellation with custom field instances
    When I log in as "admin"
    And I navigate to "Custom fields" node in "Site administration > Seminars"

    # Add signup custom fields.
    And I click on "Sign-up" "link"

    # Add a checkbox
    And I set the field "datatype" to "Checkbox"
    And I set the following fields to these values:
      | fullname  | Signup checkbox |
      | shortname | signupcheckbox |
    And I press "Save changes"
    Then I should see "Signup checkbox"

    # Add a date/time
    When I set the field "datatype" to "Date/time"
    And I set the following fields to these values:
      | fullname  | Signup datetime |
      | shortname | signupdatetime |
    And I press "Save changes"
    Then I should see "Signup datetime"

    # Add a file.
    When I set the field "datatype" to "File"
    And I set the following fields to these values:
      | fullname  | Signup file |
      | shortname | signupfile |
    And I press "Save changes"
    Then I should see "Signup file"

    # Add a location
    When I set the field "datatype" to "Location"
    And I set the following fields to these values:
      | fullname  | Signup location |
      | shortname | signuplocation |
    And I press "Save changes"
    Then I should see "Signup location"

    # Add a menu
    When I set the field "datatype" to "Menu of choices"
    And I set the following fields to these values:
      | fullname    | Signup menu |
      | shortname   | signupmenu |
      | defaultdata | Ja         |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Ja
      Nein
      """
    And I press "Save changes"
    Then I should see "Signup menu"

    # Add a multi-select
    When I set the field "datatype" to "Multi-select"
    And I set the following fields to these values:
      | fullname                   | Signup multi |
      | shortname                  | signupmulti |
      | multiselectitem[0][option] | Aye   |
      | multiselectitem[1][option] | Nay   |
    And I press "Save changes"
    Then I should see "Signup multi"

    # Add a textarea
    When I set the field "datatype" to "Text area"
    And I set the following fields to these values:
      | fullname           | Signup textarea |
      | shortname          | signuptextarea |
    And I press "Save changes"
    Then I should see "Signup textarea"

    # Add a text input
    When I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname           | Signup input |
      | shortname          | signupinput |
    And I press "Save changes"
    Then I should see "Signup input"

    # Add a URL
    When I set the field "datatype" to "URL"
    And I set the following fields to these values:
      | fullname           | Signup URL |
      | shortname          | signupurl |
    And I press "Save changes"
    Then I should see "Signup URL"
    And I should see "Signup input"
    And I should see "Signup textarea"
    And I should see "Signup menu"
    And I should see "Signup location"
    And I should see "Signup file"
    And I should see "Signup datetime"
    And I should see "Signup checkbox"

    # Add signup cancellation custom fields.
    When I click on "User cancellation" "link"
    # Add a checkbox
    And I set the field "datatype" to "Checkbox"
    And I set the following fields to these values:
      | fullname  | User cancellation checkbox |
      | shortname | usercancellationcheckbox |
    And I press "Save changes"
    Then I should see "User cancellation checkbox"

    # Add a date/time
    When I set the field "datatype" to "Date/time"
    And I set the following fields to these values:
      | fullname  | User cancellation datetime |
      | shortname | usercancellationdatetime |
    And I press "Save changes"
    Then I should see "User cancellation datetime"

    # Add a file.
    When I set the field "datatype" to "File"
    And I set the following fields to these values:
      | fullname  | User cancellation file |
      | shortname | usercancellationfile |
    And I press "Save changes"
    Then I should see "User cancellation file"

    # Add a location
    When I set the field "datatype" to "Location"
    And I set the following fields to these values:
      | fullname  | User cancellation location |
      | shortname | usercancellationlocation |
    And I press "Save changes"
    Then I should see "User cancellation location"

    # Add a menu
    When I set the field "datatype" to "Menu of choices"
    And I set the following fields to these values:
      | fullname    | User cancellation menu |
      | shortname   | usercancellationmenu |
      | defaultdata | Ja         |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Ja
      Nein
      """
    And I press "Save changes"
    Then I should see "User cancellation menu"

    # Add a multi-select
    When I set the field "datatype" to "Multi-select"
    And I set the following fields to these values:
      | fullname                   | User cancellation multi |
      | shortname                  | usercancellationmulti |
      | multiselectitem[0][option] | Aye   |
      | multiselectitem[1][option] | Nay   |
    And I press "Save changes"
    Then I should see "User cancellation multi"

    # Add a textarea
    When I set the field "datatype" to "Text area"
    And I set the following fields to these values:
      | fullname           | User cancellation textarea |
      | shortname          | usercancellationtextarea |
    And I press "Save changes"
    Then I should see "User cancellation textarea"

    # Add a text input
    When I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname           | User cancellation input |
      | shortname          | usercancellationinput |
    And I press "Save changes"
    Then I should see "User cancellation input"

    # Add a URL
    When I set the field "datatype" to "URL"
    And I set the following fields to these values:
      | fullname           | User cancellation URL |
      | shortname          | usercancellationurl |
    And I press "Save changes"
    Then I should see "User cancellation URL"
    And I should see "User cancellation input"
    And I should see "User cancellation textarea"
    And I should see "User cancellation menu"
    And I should see "User cancellation location"
    And I should see "User cancellation file"
    And I should see "User cancellation datetime"
    And I should see "User cancellation checkbox"

    When I log out
    And I log in as "student1"

    # Add images to the private files block to use later
    And I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I select "Private files" from the "Add a block" singleselect
    And I follow "Manage private files..."
    And I upload "mod/facetoface/tests/fixtures/test.jpg" file to "Files" filemanager
    And I upload "mod/facetoface/tests/fixtures/leaves-green.png" file to "Files" filemanager
    Then I should see "test.jpg"
    And I should see "leaves-green.png"

    # As the user signup.
    When I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I should see "Sign-up"
    And I follow "Sign-up"
    And I set the following fields to these values:
      | customfield_signupcheckbox        | 1                  |
      | customfield_signupdatetime[day]   | 1                  |
      | customfield_signupdatetime[month] | December           |
      | customfield_signupdatetime[year]  | 2030               |
      | customfield_signupmenu            | Nein               |
      | customfield_signupmulti[0]        | 1                  |
      | customfield_signupmulti[1]        | 1                  |
      | customfield_signupinput           | hi                 |
      | customfield_signupurl[url]        | http://example.org |

    # Add a file to the file custom field.
    And I click on "//div[@id='fitem_id_customfield_signupfile_filemanager']//a[@title='Add...']" "xpath_element"
    And I click on "test.jpg" "link" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I click on "Select this file" "button" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"

    # Image in the textarea custom field
    And I click on "//button[@class='atto_image_button']" "xpath_element" in the "//div[@id='fitem_id_customfield_signuptextarea_editor']" "xpath_element"
    And I click on "Browse repositories..." "button"
    And I click on "leaves-green.png" "link" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I click on "Select this file" "button" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I set the field "Describe this image for someone who cannot see it" to "Green leaves on customfield text area"
    And I click on "Save image" "button"
    And I press "Sign-up"
    Then I should see "Your booking has been completed."

    # As the trainer confirm I can see the details of the signup.
    When I log out
    And I log in as "teacher1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "Test seminar name"
    And I follow "Attendees"
    Then "Sam1 Student1" row "Signup URL" column of "facetoface_sessions" table should contain "http://example.org"
    And "Sam1 Student1" row "Signup checkbox" column of "facetoface_sessions" table should contain "Yes"
    And "Sam1 Student1" row "Signup file" column of "facetoface_sessions" table should contain "test.jpg"
    And "Sam1 Student1" row "Signup menu" column of "facetoface_sessions" table should contain "Nein"
    And "Sam1 Student1" row "Signup multi (text)" column of "facetoface_sessions" table should contain "Aye, Nay"
    And "Sam1 Student1" row "Signup input" column of "facetoface_sessions" table should contain "hi"
    And I should see the "Green leaves on customfield text area" image in the "//table[@id='facetoface_sessions']/tbody/tr" "xpath_element"

    When I log out
    And I log in as "student1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "Test seminar name"
    And I follow "Cancel booking"
    And I set the following fields to these values:
      | User cancellation checkbox                  | 1                    |
      | customfield_usercancellationdatetime[day]   | 15                   |
      | customfield_usercancellationdatetime[month] | October              |
      | customfield_usercancellationdatetime[year]  | 2020                 |
      | User cancellation menu                      | Ja                   |
      | customfield_usercancellationmulti[1]        | 1                    |
      | User cancellation input                     | Monkey               |
      | customfield_usercancellationurl[url]        | http://totaralms.com |
    # Add a file to the file custom field.
    And I click on "//div[@id='fitem_id_customfield_usercancellationfile_filemanager']//a[@title='Add...']" "xpath_element"
    And I click on "test.jpg" "link" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I click on "Select this file" "button" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"

    # Image in the textarea custom field
    And I click on "//button[@class='atto_image_button']" "xpath_element" in the "//div[@id='fitem_id_customfield_usercancellationtextarea_editor']" "xpath_element"
    And I click on "Browse repositories..." "button"
    And I click on "leaves-green.png" "link" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I click on "Select this file" "button" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I set the field "Describe this image for someone who cannot see it" to "Green leaves on customfield text area"
    And I click on "Save image" "button"
    And I press "Yes"
    Then I should see "Your booking has been cancelled."

    When I log out
    And I log in as "teacher1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "Test seminar name"
    And I follow "Attendees"
    And I follow "Cancellations"
    And I follow "Show cancellation reason"
    Then I should see "15 October 2020" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I should see "test.jpg" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I should see "Ja" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I should see "Nay" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I should see "Monkey" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I should see "http://totaralms.com" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I should see the "Green leaves on customfield text area" image in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"