<?php

namespace theme_citricityxund\output;

use context_course;
use core_auth\output\login;

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
}