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
 * Renderer for local_vbs_coursecatalog.
 *
 * @package     local_vbs_coursecatalog
 * @copyright   2026 VBS Đào tạo
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_vbs_coursecatalog\output;

use plugin_renderer_base;
use moodle_url;
use paging_bar;

/**
 * Plugin renderer.
 */
class renderer extends plugin_renderer_base {

    /**
     * Render the course catalog page.
     *
     * @param catalog_page $page
     * @return string HTML
     */
    public function render_catalog_page(catalog_page $page): string {
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_vbs_coursecatalog/course_catalog', $data);
    }
}
