@totara_cohort @totara @javascript
Feature: Verify self registration updates audience membership correctly.
  In order to compute the members of a cohort with dynamic membership
  As an admin
  I should be able to use menu custom field values for filter rules

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | city       | country |
      | manager   | fnameman  | lnameman | Sydney     | AU      |
      | manual001 | fname001  | lname001 | Wellington | NZ      |
      | manual002 | fname002  | lname002 | Wellington | NZ      |
      | manual003 | fname003  | lname003 | Wellington | NZ      |
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname               | idnumber  |
      | Organisation Framework | oframe    |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | fullname         | idnumber  | org_framework |
      | Organisation One | org1      | oframe        |
      | Organisation Two | org2      | oframe        |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname           | idnumber  |
      | Position Framework | pframe    |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | fullname     | idnumber  | pos_framework |
      | Position One | pos1      | pframe        |
      | Position Two | pos2      | pframe        |
    And the following job assignments exist:
      | user      | fullname        | organisation | position | manager |
      | manager   | General Manager | org1           | pos1   | admin   |
      | manual001 | General User    | org1           | pos1   | manager |
      | manual002 | General User    | org1           | pos1   | manager |
      | manual003 | General User    | org1           | pos1   | manager |
    And the following "cohorts" exist:
      | name                | idnumber | cohorttype |
      | Username - manual   | A1       | 2          |
      | Username - selfie   | A2       | 2          |
      | City - Wellington   | A3       | 2          |
      | City - Wellywood    | A4       | 2          |
      | Country - NZ        | A5       | 2          |
      | Manager - man       | A6       | 2          |
      | Position - pos2     | A7       | 2          |
      | Organisation - org2 | A8       | 2          |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
      | Course 2 | C2        | 0        |
    And I log in as "admin"

    # Set rules for the A1(Username - manual) audience.
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Username - manual"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Username"
    And I set the field "equal" to "starts with"
    And I set the field "listofvalues" to "man"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "fnameman lnameman" in the "#cohort_members" "css_element"
    And I should see "fname001 lname001" in the "#cohort_members" "css_element"
    And I should see "fname002 lname002" in the "#cohort_members" "css_element"
    And I should see "fname003 lname003" in the "#cohort_members" "css_element"

    # Set rules for the A2(Username - selfie) audience.
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Username - selfie"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Username"
    And I set the field "equal" to "starts with"
    And I set the field "listofvalues" to "self"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "There are no records in this report" in the "#region-main" "css_element"

    # Set rules for the A3(City - Wellington) audience.
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "City - Wellington"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "City"
    And I set the field "listofvalues" to "Wellington"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "fname001 lname001" in the "#cohort_members" "css_element"
    And I should see "fname002 lname002" in the "#cohort_members" "css_element"
    And I should see "fname003 lname003" in the "#cohort_members" "css_element"

    # Set rules for the A4(City - Wellywood) audience.
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "City - Wellywood"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "City"
    And I set the field "listofvalues" to "Wellywood"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "There are no records in this report" in the "#region-main" "css_element"

    # Set rules for the A5(Country - NZ) audience.
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Country - NZ"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Country"
    And I set the field "listofvalues[]" to "New Zealand"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "fname001 lname001" in the "#cohort_members" "css_element"
    And I should see "fname002 lname002" in the "#cohort_members" "css_element"
    And I should see "fname003 lname003" in the "#cohort_members" "css_element"

    # Set rules for the A6(Manager - man) audience.
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Manager - man"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Managers"
    And I click on "fnameman lnameman" "link" in the "Add rule" "totaradialogue"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "fname001 lname001" in the "#cohort_members" "css_element"
    And I should see "fname002 lname002" in the "#cohort_members" "css_element"
    And I should see "fname003 lname003" in the "#cohort_members" "css_element"

    # Set rules for the A7(Position - pos2) audience.
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Position - pos2"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Positions"
    And I click on "Position Two" "link" in the "Add rule" "totaradialogue"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "There are no records in this report" in the "#region-main" "css_element"

    # Set rules for the A8(organisation - org2) audience.
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Organisation - org2"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Organisations"
    And I click on "Organisation Two" "link" in the "Add rule" "totaradialogue"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "There are no records in this report" in the "#region-main" "css_element"

    # Turn on email-based self-registration
    And I navigate to "Email-based self-registration" node in "Site administration > Plugins > Authentication"
    And I click on "Yes" "option" in the "#menuallowsignupposition" "css_element"
    And I click on "Yes" "option" in the "#menuallowsignuporganisation" "css_element"
    And I click on "Yes" "option" in the "#menuallowsignupmanager" "css_element"
    And I press "Save changes"
    And the following config values are set as admin:
      | registerauth    | email |
      | passwordpolicy  | 0     |
    And I log out

  Scenario: Verify self registered users are added to audiences instantly when confirmed.
    When I click on "Create new account" "button"
    And I set the field "Username" to "selfie001"
    And I set the field "Password" to "selfie001"
    And I set the field "Email address" to "selfie001@totaratest.com"
    And I set the field "Email (again)" to "selfie001@totaratest.com"
    And I set the field "First name" to "Selfie"
    And I set the field "Surname" to "ZeroZeroOne"
    And I set the field "City" to "Wellywood"
    And I set the field "Country" to "New Zealand"
    And I click on "Choose position" "button"
    And I click on "Position Two" "link" in the "Choose position" "totaradialogue"
    And I click on "OK" "button" in the "Choose position" "totaradialogue"
    And I click on "Choose organisation" "button"
    And I click on "Organisation Two" "link" in the "Choose organisation" "totaradialogue"
    And I click on "OK" "button" in the "Choose organisation" "totaradialogue"
    And I click on "Choose manager" "button"
    And I click on "fnameman lnameman - General Manager" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I click on "Create my new account" "button"
    Then I should see "An email should have been sent to your address at selfie001@totaratest.com"

    # Create a second self auth user for negative testing.
    When I click on "Continue" "button"
    And I click on "Create new account" "button"
    And I set the field "Username" to "selfie002"
    And I set the field "Password" to "selfie002"
    And I set the field "Email address" to "selfie002@totaratest.com"
    And I set the field "Email (again)" to "selfie002@totaratest.com"
    And I set the field "First name" to "Selfie"
    And I set the field "Surname" to "ZeroZeroTwo"
    And I set the field "City" to "Wellywood"
    And I set the field "Country" to "New Zealand"
    And I click on "Choose position" "button"
    And I click on "Position Two" "link" in the "Choose position" "totaradialogue"
    And I click on "OK" "button" in the "Choose position" "totaradialogue"
    And I click on "Choose organisation" "button"
    And I click on "Organisation Two" "link" in the "Choose organisation" "totaradialogue"
    And I click on "OK" "button" in the "Choose organisation" "totaradialogue"
    And I click on "Choose manager" "button"
    And I click on "fnameman lnameman - General Manager" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I click on "Create my new account" "button"
    Then I should see "An email should have been sent to your address at selfie002@totaratest.com"

    # Check audience membership pre-confirmation.
    When I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Username - manual"
    And I switch to "Members" tab
    Then I should not see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Username - selfie"
    And I switch to "Members" tab
    Then I should not see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "City - Wellington"
    And I switch to "Members" tab
    Then I should not see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "City - Wellywood"
    And I switch to "Members" tab
    Then I should not see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Country - NZ"
    And I switch to "Members" tab
    Then I should not see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Manager - man"
    And I switch to "Members" tab
    Then I should not see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Position - pos2"
    And I switch to "Members" tab
    Then I should not see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Organisation - org2"
    And I switch to "Members" tab
    Then I should not see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"

    # Check audience membership post-confirmation.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "Confirm" "link" in the "Selfie ZeroZeroOne" "table_row"
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Username - manual"
    And I switch to "Members" tab
    Then I should not see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Username - selfie"
    And I switch to "Members" tab
    Then I should see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "City - Wellington"
    And I switch to "Members" tab
    Then I should not see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "City - Wellywood"
    And I switch to "Members" tab
    Then I should see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Country - NZ"
    And I switch to "Members" tab
    Then I should see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Manager - man"
    And I switch to "Members" tab
    Then I should see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Position - pos2"
    And I switch to "Members" tab
    Then I should see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Organisation - org2"
    And I switch to "Members" tab
    Then I should see "Selfie ZeroZeroOne" in the "#region-main" "css_element"
    And I should not see "Selfie ZeroZeroTwo" in the "#region-main" "css_element"
