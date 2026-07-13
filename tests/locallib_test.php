<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * PHPUnit tests for locallib.php functions.
 *
 * @package     local_vbs_coursecatalog
 * @copyright   2026 VBS Đào tạo
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/vbs_coursecatalog/locallib.php');

/**
 * Tests for vbs_coursecatalog_get_courses() and vbs_coursecatalog_get_status().
 */
class local_vbs_coursecatalog_locallib_testcase extends advanced_testcase {

    protected function setUp(): void {
        $this->resetAfterTest(true);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Enrol a user into a course using manual enrolment.
     */
    private function enrol_user(object $user, object $course, int $status = 0): void {
        global $DB;
        $enrol = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $DB->insert_record('user_enrolments', (object)[
            'status'    => $status,
            'enrolid'   => $enrol->id,
            'userid'    => $user->id,
            'timestart' => 0,
            'timeend'   => 0,
            'modifierid' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * Add a self-enrol instance to a course with optional date window.
     */
    private function add_self_enrol(object $course, int $enrolstartdate = 0, int $enrolenddate = 0): void {
        global $DB;
        $DB->insert_record('enrol', (object)[
            'enrol'          => 'self',
            'status'         => 0,
            'courseid'       => $course->id,
            'sortorder'      => 1,
            'enrolstartdate' => $enrolstartdate,
            'enrolenddate'   => $enrolenddate,
            'timecreated'    => time(),
            'timemodified'   => time(),
        ]);
    }

    // -------------------------------------------------------------------------
    // TC-PU-01: Enrolled course appears
    // -------------------------------------------------------------------------
    public function test_enrolled_course_appears(): void {
        $gen   = $this->getDataGenerator();
        $user  = $gen->create_user();
        $course = $gen->create_course(['visible' => 1, 'startdate' => time() - DAYSECS, 'enddate' => 0]);
        $gen->enrol_user($user->id, $course->id);

        $result = vbs_coursecatalog_get_courses($user->id, '', '', 0, 12);
        $ids = array_column($result['courses'], 'id');
        $this->assertContains((int)$course->id, $ids);
        $this->assertGreaterThanOrEqual(1, $result['total']);
    }

    // -------------------------------------------------------------------------
    // TC-PU-02: Open self-enrol course appears for unenrolled user
    // -------------------------------------------------------------------------
    public function test_self_enrol_open_course_appears(): void {
        $gen    = $this->getDataGenerator();
        $user   = $gen->create_user();
        $course = $gen->create_course(['visible' => 1]);
        $this->add_self_enrol($course, 0, 0);

        $result = vbs_coursecatalog_get_courses($user->id, '', '', 0, 12);
        $ids = array_column($result['courses'], 'id');
        $this->assertContains((int)$course->id, $ids);
    }

    // -------------------------------------------------------------------------
    // TC-PU-03: Hidden course does not appear even if enrolled
    // -------------------------------------------------------------------------
    public function test_hidden_course_excluded(): void {
        $gen    = $this->getDataGenerator();
        $user   = $gen->create_user();
        $course = $gen->create_course(['visible' => 0]);
        $gen->enrol_user($user->id, $course->id);

        $result = vbs_coursecatalog_get_courses($user->id, '', '', 0, 12);
        $ids = array_column($result['courses'], 'id');
        $this->assertNotContains((int)$course->id, $ids);
    }

    // -------------------------------------------------------------------------
    // TC-PU-04: Site course (id=1) does not appear
    // -------------------------------------------------------------------------
    public function test_site_course_excluded(): void {
        $gen  = $this->getDataGenerator();
        $user = $gen->create_user();

        $result = vbs_coursecatalog_get_courses($user->id, '', '', 0, 12);
        $ids = array_column($result['courses'], 'id');
        $this->assertNotContains(1, $ids);
    }

    // -------------------------------------------------------------------------
    // TC-PU-05: Search by fullname keyword
    // -------------------------------------------------------------------------
    public function test_search_by_fullname(): void {
        $gen    = $this->getDataGenerator();
        $user   = $gen->create_user();
        $course = $gen->create_course(['fullname' => 'Kế Toán Doanh Nghiệp', 'visible' => 1]);
        $gen->enrol_user($user->id, $course->id);
        $other  = $gen->create_course(['fullname' => 'Quản Lý Dự Án', 'visible' => 1]);
        $gen->enrol_user($user->id, $other->id);

        $result = vbs_coursecatalog_get_courses($user->id, 'kế toán', '', 0, 12);
        $ids = array_column($result['courses'], 'id');
        $this->assertContains((int)$course->id, $ids);
        $this->assertNotContains((int)$other->id, $ids);
    }

    // -------------------------------------------------------------------------
    // TC-PU-06: Search is case-insensitive
    // -------------------------------------------------------------------------
    public function test_search_case_insensitive(): void {
        $gen    = $this->getDataGenerator();
        $user   = $gen->create_user();
        $course = $gen->create_course(['shortname' => 'KT001', 'visible' => 1]);
        $gen->enrol_user($user->id, $course->id);

        $result = vbs_coursecatalog_get_courses($user->id, 'kt001', '', 0, 12);
        $ids = array_column($result['courses'], 'id');
        $this->assertContains((int)$course->id, $ids);
    }

    // -------------------------------------------------------------------------
    // TC-PU-07: Status filter = upcoming
    // -------------------------------------------------------------------------
    public function test_filter_status_upcoming(): void {
        $gen  = $this->getDataGenerator();
        $user = $gen->create_user();
        $now  = time();

        $upcoming  = $gen->create_course(['startdate' => $now + 7 * DAYSECS, 'enddate' => 0, 'visible' => 1]);
        $inprog    = $gen->create_course(['startdate' => $now - 3 * DAYSECS, 'enddate' => 0, 'visible' => 1]);
        $past      = $gen->create_course(['startdate' => $now - 10 * DAYSECS, 'enddate' => $now - DAYSECS, 'visible' => 1]);
        $gen->enrol_user($user->id, $upcoming->id);
        $gen->enrol_user($user->id, $inprog->id);
        $gen->enrol_user($user->id, $past->id);

        $result = vbs_coursecatalog_get_courses($user->id, '', 'upcoming', 0, 12);
        $ids = array_column($result['courses'], 'id');
        $this->assertContains((int)$upcoming->id, $ids);
        $this->assertNotContains((int)$inprog->id, $ids);
        $this->assertNotContains((int)$past->id, $ids);
    }

    // -------------------------------------------------------------------------
    // TC-PU-08: Status filter = inprogress (enddate=0)
    // -------------------------------------------------------------------------
    public function test_filter_status_inprogress_no_enddate(): void {
        $gen  = $this->getDataGenerator();
        $user = $gen->create_user();
        $now  = time();

        $inprog   = $gen->create_course(['startdate' => $now - 3 * DAYSECS, 'enddate' => 0, 'visible' => 1]);
        $upcoming = $gen->create_course(['startdate' => $now + DAYSECS, 'enddate' => 0, 'visible' => 1]);
        $gen->enrol_user($user->id, $inprog->id);
        $gen->enrol_user($user->id, $upcoming->id);

        $result = vbs_coursecatalog_get_courses($user->id, '', 'inprogress', 0, 12);
        $ids = array_column($result['courses'], 'id');
        $this->assertContains((int)$inprog->id, $ids);
        $this->assertNotContains((int)$upcoming->id, $ids);
    }

    // -------------------------------------------------------------------------
    // TC-PU-09: Status filter = past
    // -------------------------------------------------------------------------
    public function test_filter_status_past(): void {
        $gen  = $this->getDataGenerator();
        $user = $gen->create_user();
        $now  = time();

        $past   = $gen->create_course(['startdate' => $now - 10 * DAYSECS, 'enddate' => $now - DAYSECS, 'visible' => 1]);
        $inprog = $gen->create_course(['startdate' => $now - 3 * DAYSECS, 'enddate' => 0, 'visible' => 1]);
        $gen->enrol_user($user->id, $past->id);
        $gen->enrol_user($user->id, $inprog->id);

        $result = vbs_coursecatalog_get_courses($user->id, '', 'past', 0, 12);
        $ids = array_column($result['courses'], 'id');
        $this->assertContains((int)$past->id, $ids);
        $this->assertNotContains((int)$inprog->id, $ids);
    }

    // -------------------------------------------------------------------------
    // TC-PU-10: Combined search + status filter
    // -------------------------------------------------------------------------
    public function test_search_and_status_combined(): void {
        $gen  = $this->getDataGenerator();
        $user = $gen->create_user();
        $now  = time();

        $match = $gen->create_course([
            'fullname'  => 'Kế Toán Doanh Nghiệp',
            'startdate' => $now - 3 * DAYSECS,
            'enddate'   => 0,
            'visible'   => 1,
        ]);
        $other = $gen->create_course([
            'fullname'  => 'Kế Toán Thuế',
            'startdate' => $now + DAYSECS,
            'enddate'   => 0,
            'visible'   => 1,
        ]);
        $gen->enrol_user($user->id, $match->id);
        $gen->enrol_user($user->id, $other->id);

        $result = vbs_coursecatalog_get_courses($user->id, 'kế toán', 'inprogress', 0, 12);
        $ids = array_column($result['courses'], 'id');
        $this->assertContains((int)$match->id, $ids);
        $this->assertNotContains((int)$other->id, $ids);
    }

    // -------------------------------------------------------------------------
    // TC-PU-11: Pagination — page 0 returns 12 records
    // -------------------------------------------------------------------------
    public function test_pagination_page_zero(): void {
        $gen  = $this->getDataGenerator();
        $user = $gen->create_user();

        for ($i = 0; $i < 25; $i++) {
            $c = $gen->create_course(['visible' => 1]);
            $gen->enrol_user($user->id, $c->id);
        }

        $result = vbs_coursecatalog_get_courses($user->id, '', '', 0, 12);
        $this->assertCount(12, $result['courses']);
        $this->assertEquals(25, $result['total']);
    }

    // -------------------------------------------------------------------------
    // TC-PU-12: Pagination — page 1 returns 12 records
    // -------------------------------------------------------------------------
    public function test_pagination_page_one(): void {
        $gen  = $this->getDataGenerator();
        $user = $gen->create_user();

        for ($i = 0; $i < 25; $i++) {
            $c = $gen->create_course(['visible' => 1]);
            $gen->enrol_user($user->id, $c->id);
        }

        $result = vbs_coursecatalog_get_courses($user->id, '', '', 1, 12);
        $this->assertCount(12, $result['courses']);
        $this->assertEquals(25, $result['total']);
    }

    // -------------------------------------------------------------------------
    // TC-PU-13: Pagination — last page has 1 course (25 total, page 2)
    // -------------------------------------------------------------------------
    public function test_pagination_last_page(): void {
        $gen  = $this->getDataGenerator();
        $user = $gen->create_user();

        for ($i = 0; $i < 25; $i++) {
            $c = $gen->create_course(['visible' => 1]);
            $gen->enrol_user($user->id, $c->id);
        }

        $result = vbs_coursecatalog_get_courses($user->id, '', '', 2, 12);
        $this->assertCount(1, $result['courses']);
    }

    // -------------------------------------------------------------------------
    // TC-PU-14: Empty result when user has no courses
    // -------------------------------------------------------------------------
    public function test_empty_result_for_new_user(): void {
        $gen  = $this->getDataGenerator();
        $user = $gen->create_user();

        $result = vbs_coursecatalog_get_courses($user->id, '', '', 0, 12);
        $this->assertEmpty($result['courses']);
        $this->assertEquals(0, $result['total']);
    }

    // -------------------------------------------------------------------------
    // TC-PU-15: get_status() — upcoming
    // -------------------------------------------------------------------------
    public function test_get_status_upcoming(): void {
        $now    = time();
        $course = (object)['startdate' => $now + DAYSECS, 'enddate' => 0];
        $this->assertEquals('upcoming', vbs_coursecatalog_get_status($course, $now));
    }

    // -------------------------------------------------------------------------
    // TC-PU-16: get_status() — inprogress (enddate=0)
    // -------------------------------------------------------------------------
    public function test_get_status_inprogress_no_enddate(): void {
        $now    = time();
        $course = (object)['startdate' => $now - DAYSECS, 'enddate' => 0];
        $this->assertEquals('inprogress', vbs_coursecatalog_get_status($course, $now));
    }

    // -------------------------------------------------------------------------
    // TC-PU-17: get_status() — inprogress (enddate in future)
    // -------------------------------------------------------------------------
    public function test_get_status_inprogress_future_enddate(): void {
        $now    = time();
        $course = (object)['startdate' => $now - 5 * DAYSECS, 'enddate' => $now + 10 * DAYSECS];
        $this->assertEquals('inprogress', vbs_coursecatalog_get_status($course, $now));
    }

    // -------------------------------------------------------------------------
    // TC-PU-18: get_status() — past
    // -------------------------------------------------------------------------
    public function test_get_status_past(): void {
        $now    = time();
        $course = (object)['startdate' => $now - 10 * DAYSECS, 'enddate' => $now - DAYSECS];
        $this->assertEquals('past', vbs_coursecatalog_get_status($course, $now));
    }

    // -------------------------------------------------------------------------
    // TC-PU-19: Expired self-enrol does not appear
    // -------------------------------------------------------------------------
    public function test_expired_self_enrol_excluded(): void {
        $gen    = $this->getDataGenerator();
        $user   = $gen->create_user();
        $now    = time();
        $course = $gen->create_course(['visible' => 1]);
        $this->add_self_enrol($course, 0, $now - DAYSECS); // expired yesterday

        $result = vbs_coursecatalog_get_courses($user->id, '', '', 0, 12);
        $ids = array_column($result['courses'], 'id');
        $this->assertNotContains((int)$course->id, $ids);
    }

    // -------------------------------------------------------------------------
    // TC-PU-20: Course enrolled via self-enrol after already enrolled — no duplicate
    // -------------------------------------------------------------------------
    public function test_no_duplicate_for_enrolled_self_enrol_course(): void {
        $gen    = $this->getDataGenerator();
        $user   = $gen->create_user();
        $course = $gen->create_course(['visible' => 1]);
        $gen->enrol_user($user->id, $course->id);
        $this->add_self_enrol($course, 0, 0);

        $result = vbs_coursecatalog_get_courses($user->id, '', '', 0, 12);
        $ids = array_column($result['courses'], 'id');
        $occurrences = array_count_values($ids);
        $this->assertEquals(1, $occurrences[(int)$course->id] ?? 0);
    }
}
