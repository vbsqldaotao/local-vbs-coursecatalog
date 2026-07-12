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

require_login();

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/vbs_coursecatalog/index.php'));
$PAGE->set_title(get_string('pluginname', 'local_vbs_coursecatalog'));
$PAGE->set_heading(get_string('pluginname', 'local_vbs_coursecatalog'));
$PAGE->set_pagelayout('standard');

// Fetch courses respecting category-level visibility for the current user.
$courses = core_course_category::search_courses([], ['sort' => ['sortorder' => 1]]);
$catalogdata = [];
foreach ($courses as $course) {
    if ($course->id == SITEID) {
        continue;
    }
    if (!$course->visible) {
        continue;
    }
    $catalogdata[] = [
        'id'        => (int) $course->id,
        'fullname'  => format_string($course->fullname, true, ['context' => $context]),
        'shortname' => format_string($course->shortname, true, ['context' => $context]),
        'summary'   => format_text($course->summary, FORMAT_HTML, ['context' => $context]),
        'startdate' => $course->startdate ? userdate($course->startdate, get_string('strftimedatefullshort')) : '',
        'enddate'   => $course->enddate ? userdate($course->enddate, get_string('strftimedatefullshort')) : '',
        'courseurl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_vbs_coursecatalog'));

if (empty($catalogdata)) {
    echo $OUTPUT->notification(get_string('nocourses', 'local_vbs_coursecatalog'), 'info');
} else {
    echo html_writer::start_tag('div', ['class' => 'vbs-coursecatalog container-fluid py-3']);
    echo html_writer::start_tag('div', ['class' => 'row row-cols-1 row-cols-md-3 g-4']);
    foreach ($catalogdata as $c) {
        echo html_writer::start_tag('div', ['class' => 'col']);
        echo html_writer::start_tag('div', ['class' => 'card h-100']);
        echo html_writer::start_tag('div', ['class' => 'card-body']);
        echo html_writer::tag('h5', html_writer::link($c['courseurl'], $c['fullname']), ['class' => 'card-title']);
        echo html_writer::tag('p', $c['shortname'], ['class' => 'card-subtitle mb-2 text-muted']);
        if ($c['startdate']) {
            echo html_writer::tag('p', get_string('startdate', 'local_vbs_coursecatalog') . ': ' . $c['startdate'], ['class' => 'card-text small']);
        }
        echo html_writer::end_tag('div');
        echo html_writer::start_tag('div', ['class' => 'card-footer']);
        echo html_writer::link($c['courseurl'], get_string('viewcourse', 'local_vbs_coursecatalog'), ['class' => 'btn btn-primary btn-sm']);
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
    }
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
}

echo $OUTPUT->footer();
