<?php

// This line protects the file from being accessed by a URL directly.                                                               
defined('MOODLE_INTERNAL') || die();

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

function theme_citricityxund_before_standard_html_head(): string {
    global $CFG;
    $kirourl = $CFG->wwwroot.'/theme/citricityxund/fonts/kirofont.css';
    return '<link rel="stylesheet" href="'.$kirourl.'">';
}