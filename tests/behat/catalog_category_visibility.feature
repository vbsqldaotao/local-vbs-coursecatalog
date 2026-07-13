@local_vbs_coursecatalog
Feature: Course catalog respects category visibility
  As any user of the catalog
  When I visit the course catalog
  I should only see courses whose category (and therefore course) is visible

  Background:
    Given the following "categories" exist:
      | name       | category | idnumber | visible |
      | Cat Public | 0        | catpub   | 1       |
      | Cat Hidden | 0        | cathid   | 0       |
    And the following "courses" exist:
      | fullname        | shortname | category |
      | Public Course 1 | PC1       | catpub   |
      | Hidden Course 1 | HC1       | cathid   |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
    # The catalog lists a user's enrolled + open self-enrol courses (BR-F01-01) filtered by
    # course.visible. A course created inside a hidden category inherits course.visible = 0
    # (Moodle create_course()), so it is excluded for EVERY user — there is no capability-based
    # bypass in this plugin. student1 is enrolled in BOTH courses so the assertion below is
    # driven by category/course visibility, not by a missing enrolment.
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | PC1    | student |
      | student1 | HC1    | student |

  @TC-02
  Scenario: Student does not see courses in a hidden category on the catalog page
    Given I log in as "student1"
    When I visit "/local/vbs_coursecatalog/index.php"
    Then I should see "Public Course 1"
    And I should not see "Hidden Course 1"

  Scenario: Hidden-category courses are filtered uniformly, with no privileged bypass
    Given the following "course enrolments" exist:
      | user  | course | role           |
      | admin | PC1    | editingteacher |
      | admin | HC1    | editingteacher |
    And I log in as "admin"
    When I visit "/local/vbs_coursecatalog/index.php"
    Then I should see "Public Course 1"
    And I should not see "Hidden Course 1"
