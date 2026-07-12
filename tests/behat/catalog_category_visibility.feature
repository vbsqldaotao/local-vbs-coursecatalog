@local_vbs_coursecatalog
Feature: Course catalog respects category visibility
  As a student with no special capabilities
  When I visit the course catalog
  I should only see courses from visible categories

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

  @TC-02
  Scenario: Student does not see courses in a hidden category on the catalog page
    Given I log in as "student1"
    When I visit "/local/vbs_coursecatalog/index.php"
    Then I should see "Public Course 1"
    And I should not see "Hidden Course 1"

  Scenario: Admin can see all courses including those in hidden categories
    Given I log in as "admin"
    When I visit "/local/vbs_coursecatalog/index.php"
    Then I should see "Public Course 1"
    And I should see "Hidden Course 1"
