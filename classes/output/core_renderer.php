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

namespace theme_citricityxund\output;

use core\context\course as context_course;
use core_auth\output\login;
use core_course_list_element;
use core\output\html_writer;
use core\url;
use stdClass;

/**
 * Citricity XUND core renderer overrides (login, navbar, header, footer, course banner).
 *
 * @package    theme_citricityxund
 * @copyright  2026 Citricity Ltd <http://citr.city>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \theme_boost\output\core_renderer {
    /**
     * Renders the login form.
     *
     * @param \core_auth\output\login $form The renderable.
     * @return string
     */
    public function render_login(login $form): string {
        global $SITE;
        $context = $form->export_for_template($this);
        $context->sitename = format_string(
            $SITE->fullname,
            true,
            ['context' => context_course::instance(SITEID), "escape" => false]
        );
        return $this->render_from_template('core/loginform', $context);
    }

    /**
     * Renders the "breadcrumb" for all pages in citricityxund.
     *
     * @return string the HTML for the navbar.
     */
    public function navbar(): string {
        $newnav = new \theme_citricityxund\boostnavbar($this->page);
        return $this->render_from_template('core/navbar', $newnav);
    }

    /**
     * Return the url for course header image or one of the defaults.
     *
     * @param  Object $course  - optional course, otherwise, this course.
     * @return string header image url.
     */
    public function get_course_header_image_url($course = false) {
        global $COURSE;

        // If no course is sent, use the current course.
        if (!$course) {
            $course = $COURSE;
        }

        // Default theme pix folder header image.
        $courseimage = $this->image_url('defaultcourseicon', 'theme');

        // Course image - overrides all others.
        $course = new core_course_list_element($course);
        foreach ($course->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                $courseimage = url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    null,
                    $file->get_filepath(),
                    $file->get_filename()
                )->out(false);
            }
        }
        return $courseimage;
    }

    /**
     * Wrapper for header elements.
     *
     * @return string HTML to display the main header.
     */
    public function full_header() {

        global $COURSE;

        if (
            $this->page->include_region_main_settings_in_header_actions() &&
                !$this->page->blocks->is_block_present('settings')
        ) {
            // Only include the region main settings if the page has requested it and it doesn't already have
            // the settings block on it. The region main settings are included in the settings block and
            // duplicating the content causes behat failures.
            $this->page->add_header_action(html_writer::div(
                $this->region_main_settings_menu(),
                'd-print-none',
                ['id' => 'region-main-settings-menu']
            ));
        }

        $header = new stdClass();
        $header->settingsmenu = $this->context_header_settings_menu();
        $header->contextheader = $this->context_header();
        $header->hasnavbar = empty($this->page->layout_options['nonavbar']);
        $header->navbar = $this->navbar();
        $header->pageheadingbutton = $this->page_heading_button();
        $header->courseheader = $this->course_header();
        $header->headeractions = $this->page->get_header_actions();

        if (isset($COURSE->id) && $COURSE->id != 1) {
            $header->headerimage = $this->get_course_header_image_url();
        }

        return $this->render_from_template('core/full_header', $header);
    }

    /**
     * Standard end-of-body HTML.
     *
     * Overridden to exclude $CFG->additionalhtmlfooter, which this theme renders as a footer
     * column via {@see self::additional_html_footer()} instead.
     *
     * @return string
     */
    public function standard_end_of_body_html() {
        return $this->unique_end_html_token;
    }

    /**
     * The additionalhtmlfooter has been split into its own renderer method.
     * This allows us to place it in a column.
     *
     * @return string
     */
    public function additional_html_footer() {
        global $CFG;
        if ($this->page->pagelayout !== 'embedded' && !empty($CFG->additionalhtmlfooter)) {
            return $CFG->additionalhtmlfooter;
        }
        return '';
    }
}
