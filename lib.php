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
 * Library functions for local_vbs_coursecatalog.
 *
 * @package     local_vbs_coursecatalog
 * @copyright   2026 VBS Đào tạo
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add a link to the global navigation tree.
 *
 * @param global_navigation $nav
 */
function local_vbs_coursecatalog_extend_navigation(global_navigation $nav): void {
    if (!isloggedin() || isguestuser()) {
        return;
    }
    $context = context_system::instance();
    if (!has_capability('local/vbs_coursecatalog:view', $context)) {
        return;
    }
    $url  = new moodle_url('/local/vbs_coursecatalog/index.php');
    $node = navigation_node::create(
        get_string('pluginname', 'local_vbs_coursecatalog'),
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'local_vbs_coursecatalog',
        new pix_icon('i/course', '')
    );
    $node->showinflatnavigation = true;
    $nav->add_node($node);
}
