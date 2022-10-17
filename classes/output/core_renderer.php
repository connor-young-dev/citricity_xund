<?php

namespace theme_citricityxund\output;

use context_course;
use core_auth\output\login;
use core_course_list_element;
use stdClass;
use html_writer;

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
        $context->sitename = format_string($SITE->fullname, true, ['context' => context_course::instance(SITEID), "escape" => false]);
        return $this->render_from_template('core/loginform', $context);
    }

    /**
     * Return the url for course header image or one of the defaults.
     *
     * @param  Object $course  - optional course, otherwise, this course.
     * @return string header image url.
     */
    public function get_course_header_image_url($course = false) {
        global $CFG, $COURSE, $OUTPUT;

        // If no course is sent, use the current course.
        if (!$course) {
            $course = $COURSE;
        }

        // Default theme pix folder header image.
        $courseimage = $OUTPUT->image_url('defaultcourseicon', 'theme');

        // Course image - overides all others.
        $course = new core_course_list_element($course);
        $context = context_course::instance($course->id);
        foreach ($course->get_course_overviewfiles() as $file) {
            if ($isimage = $file->is_valid_image()) {
                $courseimage = file_encode_url("$CFG->wwwroot/pluginfile.php", '/' . $file->get_contextid() . '/' . $file->get_component() . '/' . $file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
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

        if ($this->page->include_region_main_settings_in_header_actions() &&
                !$this->page->blocks->is_block_present('settings')) {
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
}