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
 * Library functions for the Citricity XUND theme.
 *
 * @package    theme_citricityxund
 * @copyright  2026 Citricity Ltd <http://citr.city>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Returns the combined main SCSS (pre.scss + main.scss + post.scss) for the theme.
 *
 * @param \theme_config $theme The theme config object.
 * @return string The combined SCSS source.
 */
function theme_citricityxund_get_main_scss_content($theme) {
    global $CFG;

    $scss = [];

    // Pre CSS - this is loaded AFTER any prescss from the setting but before the main scss.
    $scss[] = file_get_contents($CFG->dirroot . '/theme/citricityxund/scss/pre.scss');

    $scss[] = file_get_contents($CFG->dirroot . '/theme/citricityxund/scss/main.scss');

    // Post CSS - this is loaded AFTER the main scss but before the extra scss from the setting.
    $scss[] = file_get_contents($CFG->dirroot . '/theme/citricityxund/scss/post.scss');

    // Combine them together.
    return implode("\n", $scss);
}
