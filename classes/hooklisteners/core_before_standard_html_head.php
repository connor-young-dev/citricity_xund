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

namespace theme_citricityxund\hooklisteners;

use core\hook\output\before_standard_head_html_generation;

/**
 * Hook listener for adding custom head HTML content.
 *
 * @package    theme_citricityxund
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_before_standard_html_head {
    /**
     * Hook callback to add custom CSS to the page head.
     *
     * @param before_standard_head_html_generation $hook
     */
    public static function execute(before_standard_head_html_generation $hook): void {
        global $CFG;

        $kirourl = $CFG->wwwroot . '/theme/citricityxund/fonts/kirofont.css';
        $html = '<link rel="stylesheet" href="' . s($kirourl) . '">';
        $hook->add_html($html);
    }
}
