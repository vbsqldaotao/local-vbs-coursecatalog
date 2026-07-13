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
 * @package     local_vbs_coursecatalog
 * @copyright   2026 VBS Đào tạo
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

require_login();

$context = context_system::instance();

// VBS-353: enforce capability — not just login.
require_capability('local/vbs_coursecatalog:view', $context);

$search  = optional_param('search', '', PARAM_TEXT);
$status  = optional_param('status', '', PARAM_ALPHA);
$page    = optional_param('page', 0, PARAM_INT);
$perpage = 12;

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/vbs_coursecatalog/index.php'), [
    'search' => $search,
    'status' => $status,
    'page'   => $page,
]);
$PAGE->set_title(get_string('pluginname', 'local_vbs_coursecatalog'));
$PAGE->set_heading(get_string('pluginname', 'local_vbs_coursecatalog'));
$PAGE->set_pagelayout('standard');

// VBS-402: use locallib to apply enrolled+self-enrol business rules (BR-F01-01).
$result  = vbs_coursecatalog_get_courses($USER->id, $search, $status, $page, $perpage);
$courses = $result['courses'];
$total   = $result['total'];
$now     = time();

$status_options = [
    ['value' => '',           'label' => get_string('status_all',        'local_vbs_coursecatalog'), 'selected' => $status === ''],
    ['value' => 'upcoming',   'label' => get_string('status_upcoming',   'local_vbs_coursecatalog'), 'selected' => $status === 'upcoming'],
    ['value' => 'inprogress', 'label' => get_string('status_inprogress', 'local_vbs_coursecatalog'), 'selected' => $status === 'inprogress'],
    ['value' => 'past',       'label' => get_string('status_past',       'local_vbs_coursecatalog'), 'selected' => $status === 'past'],
];

$status_label_map = [
    'upcoming'   => get_string('status_upcoming',   'local_vbs_coursecatalog'),
    'inprogress' => get_string('status_inprogress', 'local_vbs_coursecatalog'),
    'past'       => get_string('status_past',       'local_vbs_coursecatalog'),
];

$course_data = [];
foreach ($courses as $c) {
    $s = vbs_coursecatalog_get_status($c, $now);
    $course_data[] = [
        'id'              => (int) $c->id,
        'fullname'        => format_string($c->fullname, true, ['context' => $context]),
        'shortname'       => format_string($c->shortname, true, ['context' => $context]),
        'statuslabel'     => $status_label_map[$s] ?? $s,
        'startdate'       => $c->startdate ? userdate($c->startdate, get_string('strftimedatefullshort')) : '',
        'startdatelabel'  => get_string('startdate', 'local_vbs_coursecatalog'),
        'viewcourselabel' => get_string('viewcourse', 'local_vbs_coursecatalog'),
        'courseurl'       => (new moodle_url('/course/view.php', ['id' => $c->id]))->out(false),
    ];
}

$paging_bar = $OUTPUT->paging_bar($total, $page, $perpage, $PAGE->url);

$template_context = [
    'searchvalue'       => s($search),
    'statusvalue'       => s($status),
    'status_options'    => $status_options,
    'hascourses'        => !empty($course_data),
    'courses'           => $course_data,
    'nocourses'         => get_string('nocourses', 'local_vbs_coursecatalog'),
    'paging'            => $paging_bar,
    'searchlabel'       => get_string('search_label',       'local_vbs_coursecatalog'),
    'searchplaceholder' => get_string('search_placeholder', 'local_vbs_coursecatalog'),
    'statuslabel'       => get_string('status',             'local_vbs_coursecatalog'),
    'filterlabel'       => get_string('filter',             'local_vbs_coursecatalog'),
];

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_vbs_coursecatalog'));
echo $OUTPUT->render_from_template('local_vbs_coursecatalog/coursecatalog', $template_context);
echo $OUTPUT->footer();
