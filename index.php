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
 * Course catalog main page.
 *
 * Fixes applied (VBS-353 to VBS-360):
 * - Enforces local/vbs_coursecatalog:view capability (VBS-353)
 * - Uses core_course_category::search_courses() which respects category visibility (VBS-354, VBS-359)
 * - Adds pagination via paging_bar (VBS-355)
 * - Renders via Mustache template through output renderer (VBS-360)
 *
 * @package     local_vbs_coursecatalog
 * @copyright   2026 VBS Đào tạo
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$page    = optional_param('page', 0, PARAM_INT);
$perpage = 24;

require_login();

$context = context_system::instance();

// VBS-353: enforce capability — not just login.
require_capability('local/vbs_coursecatalog:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/vbs_coursecatalog/index.php', ['page' => $page]));
$PAGE->set_title(get_string('pluginname', 'local_vbs_coursecatalog'));
$PAGE->set_heading(get_string('pluginname', 'local_vbs_coursecatalog'));
$PAGE->set_pagelayout('standard');

// VBS-354 + VBS-359: use search_courses() which filters by category visibility
// and user permission, replacing the legacy get_courses().
$searchoptions = [
    'offset'  => $page * $perpage,
    'limit'   => $perpage,
    'sort'    => ['fullname' => 1],
];
$courses = core_course_category::search_courses([], $searchoptions);
$total   = core_course_category::search_courses_count([]);

$coursedata = [];
foreach ($courses as $course) {
    if ($course->id == SITEID) {
        continue;
    }
    $coursedata[] = [
        'id'        => (int) $course->id,
        'fullname'  => format_string($course->get_formatted_name(), true, ['context' => $context]),
        'shortname' => format_string($course->shortname, true, ['context' => $context]),
        'startdate' => $course->startdate ? userdate($course->startdate, get_string('strftimedatefullshort')) : '',
        'courseurl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
    ];
}

// VBS-360: render via Mustache template through output renderer.
/** @var \local_vbs_coursecatalog\output\renderer $renderer */
$renderer = $PAGE->get_renderer('local_vbs_coursecatalog');
$catalogpage = new \local_vbs_coursecatalog\output\catalog_page($coursedata, $total, $page, $perpage);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_vbs_coursecatalog'));
echo $renderer->render_catalog_page($catalogpage);
echo $OUTPUT->footer();
