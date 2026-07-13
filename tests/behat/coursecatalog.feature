@local_vbs_coursecatalog
Feature: Course Catalog for students
  In order to find my courses
  As a student
  I need to see a list of enrolled and open courses

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email          |
      | student1 | Nguyen    | Van A    | s1@example.com |
    And the following "courses" exist:
      | fullname              | shortname | startdate | enddate   | visible |
      | Kế Toán Doanh Nghiệp | KT001     | -3 days   | 0         | 1       |
      | Quản Lý Dự Án        | QLDA001   | +7 days   | 0         | 1       |
      | Khoá Đã Kết Thúc     | PAST001   | -30 days  | -1 days   | 1       |
      | Khóa Ẩn              | HIDDEN001 | -3 days   | 0         | 0       |
    And the following "course enrolments" exist:
      | user     | course    | role    |
      | student1 | KT001     | student |
      | student1 | QLDA001   | student |
      | student1 | PAST001   | student |
      | student1 | HIDDEN001 | student |

  @AC-F01-001
  Scenario: Student sees enrolled courses in card grid
    Given I log in as "student1"
    When I am on site homepage
    And I navigate to "/local/vbs_coursecatalog/index.php" in current site
    Then I should see "Kế Toán Doanh Nghiệp"
    And I should see "Quản Lý Dự Án"

  @AC-F01-002
  Scenario: Empty state message when no courses match
    Given the following "users" exist:
      | username | firstname | lastname | email          |
      | student2 | Le        | Thi B    | s2@example.com |
    And I log in as "student2"
    When I navigate to "/local/vbs_coursecatalog/index.php" in current site
    Then I should see "Không có khóa học nào"

  @AC-F01-003
  Scenario: Search by keyword filters courses
    Given I log in as "student1"
    And I navigate to "/local/vbs_coursecatalog/index.php" in current site
    When I set the field "search" to "quản lý"
    And I press "Lọc"
    Then I should see "Quản Lý Dự Án"
    And I should not see "Kế Toán Doanh Nghiệp"

  @AC-F01-004
  Scenario: Filter by status inprogress
    Given I log in as "student1"
    And I navigate to "/local/vbs_coursecatalog/index.php" in current site
    When I set the field "status" to "inprogress"
    And I press "Lọc"
    Then I should see "Kế Toán Doanh Nghiệp"
    And I should not see "Quản Lý Dự Án"

  @AC-F01-005
  Scenario: Combined search and status filter
    Given I log in as "student1"
    And I navigate to "/local/vbs_coursecatalog/index.php" in current site
    When I set the field "search" to "kế toán"
    And I set the field "status" to "inprogress"
    And I press "Lọc"
    Then I should see "Kế Toán Doanh Nghiệp"
    And I should not see "Quản Lý Dự Án"
    And I should not see "Khoá Đã Kết Thúc"

  @AC-F01-007
  Scenario: Hidden course does not appear even if enrolled
    Given I log in as "student1"
    When I navigate to "/local/vbs_coursecatalog/index.php" in current site
    Then I should not see "Khóa Ẩn"

  @AC-F01-008
  Scenario: Guest is redirected to login page
    Given I am not logged in
    When I navigate to "/local/vbs_coursecatalog/index.php" in current site
    Then I should see "Log in"
