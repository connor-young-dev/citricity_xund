<?php

defined('MOODLE_INTERNAL') || die();

$bodyattributes = $OUTPUT->body_attributes();

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'logourl' => $OUTPUT->image_url('loginlogo', 'theme_citricityxund')
];

echo $OUTPUT->render_from_template('theme_citricityxund/login', $templatecontext);