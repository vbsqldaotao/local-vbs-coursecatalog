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
 * Core library functions for local_vbs_coursecatalog.
 *
 * @package     local_vbs_coursecatalog
 * @copyright   2026 VBS Đào tạo
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Determine the status of a course based on its start/end dates.
 *
 * @param stdClass $course Course object with startdate and enddate fields.
 * @param int      $now    Unix timestamp to compare against (use time() in production).
 * @return string  One of: 'upcoming', 'inprogress', 'past'.
 */
function vbs_coursecatalog_get_status(stdClass $course, int $now): string {
    if ($course->startdate > $now) {
        return 'upcoming';
    }
    if ($course->enddate != 0 && $course->enddate < $now) {
        return 'past';
    }
    return 'inprogress';
}

/**
 * Get paginated courses visible to a user: enrolled courses + open self-enrol courses.
 *
 * Business rules applied:
 *   BR-F01-01(a): enrolled via mdl_user_enrolments (status IN 0,1), course visible=1, id!=1
 *   BR-F01-01(b): self-enrol open (enrol='self', status=0, dates valid), not already enrolled
 *   BR-F01-03: case-insensitive search on fullname or shortname
 *   BR-F01-04: 12 cards/page by default, base-0 page param
 *
 * @param int    $userid   User ID.
 * @param string $search   Keyword to search in fullname or shortname (empty = no filter).
 * @param string $status   Status filter: 'upcoming'|'inprogress'|'past'|'' (empty = all).
 * @param int    $page     Page number, base-0.
 * @param int    $perpage  Records per page.
 * @return array ['courses' => stdClass[], 'total' => int]
 */
function vbs_coursecatalog_get_courses(int $userid, string $search, string $status, int $page, int $perpage): array {
    global $DB;

    // Query A: enrolled courses.
    $sql_a = "SELECT DISTINCT c.id, c.fullname, c.shortname, c.startdate, c.enddate
                FROM {course} c
                JOIN {enrol} e ON e.courseid = c.id
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
               WHERE ue.userid = :userid_a
                 AND ue.status IN (0, 1)
                 AND c.visible = 1
                 AND c.id != 1";

    $now = time();
    $params_a = ['userid_a' => $userid];

    // Query B: open self-enrol courses the user is NOT yet enrolled in.
    $sql_b = "SELECT DISTINCT c.id, c.fullname, c.shortname, c.startdate, c.enddate
                FROM {course} c
                JOIN {enrol} e ON e.courseid = c.id
                          AND e.enrol = 'self'
                          AND e.status = 0
                          AND (e.enrolstartdate = 0 OR e.enrolstartdate <= :now1)
                          AND (e.enrolenddate = 0   OR e.enrolenddate   >= :now2)
               WHERE c.visible = 1
                 AND c.id != 1
                 AND c.id NOT IN (
                     SELECT c2.id
                       FROM {course} c2
                       JOIN {enrol} e2 ON e2.courseid = c2.id
                       JOIN {user_enrolments} ue2 ON ue2.enrolid = e2.id
                      WHERE ue2.userid = :userid_b
                        AND ue2.status IN (0, 1)
                 )";

    $params_b = [
        'now1'     => $now,
        'now2'     => $now,
        'userid_b' => $userid,
    ];

    $records_a = $DB->get_records_sql($sql_a, $params_a);
    $records_b = $DB->get_records_sql($sql_b, $params_b);

    // Merge, deduplicate by course id.
    $merged = [];
    foreach ($records_a as $r) {
        $merged[$r->id] = $r;
    }
    foreach ($records_b as $r) {
        if (!isset($merged[$r->id])) {
            $merged[$r->id] = $r;
        }
    }

    // Apply search filter (BR-F01-03: case-insensitive on fullname OR shortname).
    if ($search !== '') {
        $search_lower = core_text::strtolower($search);
        $merged = array_filter($merged, function($c) use ($search_lower) {
            return str_contains(core_text::strtolower($c->fullname), $search_lower)
                || str_contains(core_text::strtolower($c->shortname), $search_lower);
        });
    }

    // Apply status filter (BR-F01-02).
    if ($status !== '') {
        $merged = array_filter($merged, function($c) use ($status, $now) {
            return vbs_coursecatalog_get_status($c, $now) === $status;
        });
    }

    $total = count($merged);

    // Paginate.
    $paged = array_slice(array_values($merged), $page * $perpage, $perpage);

    return ['courses' => $paged, 'total' => $total];
}
