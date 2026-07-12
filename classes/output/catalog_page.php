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
 * Renderable for the course catalog page.
 *
 * @package     local_vbs_coursecatalog
 * @copyright   2026 VBS Đào tạo
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_vbs_coursecatalog\output;

use renderable;
use templatable;
use renderer_base;
use moodle_url;
use context_system;

/**
 * Encapsulates all data for the catalog page template.
 */
class catalog_page implements renderable, templatable {

    /** @var array Processed course data rows. */
    private array $courses;

    /** @var int Total matching courses (for paging). */
    private int $total;

    /** @var int Current page index. */
    private int $page;

    /** @var int Courses per page. */
    private int $perpage;

    /**
     * @param array $courses   Processed course rows.
     * @param int   $total     Total course count (for paging).
     * @param int   $page      Current page (0-based).
     * @param int   $perpage   Rows per page.
     */
    public function __construct(array $courses, int $total, int $page, int $perpage) {
        $this->courses = $courses;
        $this->total   = $total;
        $this->page    = $page;
        $this->perpage = $perpage;
    }

    /**
     * Export data for the Mustache template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $pageurl = new moodle_url('/local/vbs_coursecatalog/index.php');
        $paging  = new \paging_bar($this->total, $this->page, $this->perpage, $pageurl);

        $startdatelabel = get_string('startdate', 'local_vbs_coursecatalog');
        $viewcourse     = get_string('viewcourse', 'local_vbs_coursecatalog');

        $rows = [];
        foreach ($this->courses as $c) {
            $rows[] = [
                'id'            => $c['id'],
                'fullname'      => $c['fullname'],
                'shortname'     => $c['shortname'],
                'startdate'     => $c['startdate'],
                'courseurl'     => $c['courseurl'],
                'startdatelabel' => $startdatelabel,
                'viewcourse'    => $viewcourse,
            ];
        }

        return [
            'courses'    => $rows,
            'hascourses' => !empty($rows),
            'nocourses'  => get_string('nocourses', 'local_vbs_coursecatalog'),
            'paging'     => $output->render($paging),
        ];
    }
}
