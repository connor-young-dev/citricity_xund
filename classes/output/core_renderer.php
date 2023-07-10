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

    // Inherited - overridden to exclude $CFG->additionalhtmlfooter as we include this in the footer
    // as a column.
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

    /**
     * Renders the header bar.
     *
     * @param context_header $contextheader Header bar object.
     * @return string HTML for the header bar.
     */
    protected function render_context_header(\context_header $contextheader) {

        // Generate the heading first and before everything else as we might have to do an early return.
        if (!isset($contextheader->heading)) {
            $heading = $this->heading($this->page->heading, $contextheader->headinglevel, 'h1');
        } else {
            $heading = $this->heading($contextheader->heading, $contextheader->headinglevel, 'h1');
        }

        // All the html stuff goes here.
        $html = html_writer::start_div('page-context-header');

        // Image data.
        if (isset($contextheader->imagedata)) {
            // Header specific image.
            $html .= html_writer::div($contextheader->imagedata, 'page-header-image mr-2');
        }

        // Headings.
        if (isset($contextheader->prefix)) {
            $prefix = html_writer::div($contextheader->prefix, 'text-muted text-uppercase small line-height-3');
            $heading = $prefix . $heading;
        }
        $html .= html_writer::tag('div', $heading, array('class' => 'page-header-headings'));

        // Buttons.
        if (isset($contextheader->additionalbuttons)) {
            $html .= html_writer::start_div('btn-group header-button-group');
            foreach ($contextheader->additionalbuttons as $button) {
                if (!isset($button->page)) {
                    // Include js for messaging.
                    if ($button['buttontype'] === 'togglecontact') {
                        \core_message\helper::togglecontact_requirejs();
                    }
                    if ($button['buttontype'] === 'message') {
                        \core_message\helper::messageuser_requirejs();
                    }
                    $image = $this->pix_icon($button['formattedimage'], $button['title'], 'moodle', array(
                        'class' => 'iconsmall',
                        'role' => 'presentation'
                    ));
                    $image .= html_writer::span($button['title'], 'header-button-title');
                } else {
                    $image = html_writer::empty_tag('img', array(
                        'src' => $button['formattedimage'],
                        'role' => 'presentation'
                    ));
                }
                $html .= html_writer::link($button['url'], html_writer::tag('span', $image), $button['linkattributes']);
            }
            $html .= html_writer::end_div();
        }
        $html .= html_writer::end_div();

        return $html;
    }
}