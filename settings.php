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
 * Admin settings for the Citricity XUND theme.
 *
 * @package    theme_citricityxund
 * @copyright  2026 Citricity Ltd <http://citr.city>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// This line protects the file from being accessed by a URL directly.
defined('MOODLE_INTERNAL') || die();

// This is used for performance, we don't need to know about these settings on every page in Moodle, only when
// we are looking at the admin settings pages.
if ($ADMIN->fulltree) {
    // Boost provides a nice setting page which splits settings onto separate tabs. We want to use it here.
    $settings = new theme_boost_admin_settingspage_tabs(
        'themesettingcitricityxund',
        get_string('configtitle', 'theme_citricityxund')
    );

    // Each page is a tab - the first is the "General" tab.
    $page = new admin_settingpage('theme_citricityxund_general', get_string('generalsettings', 'theme_citricityxund'));

    // Must add the page after definiting all the settings!
    $settings->add($page);

    // Advanced settings.
    $page = new admin_settingpage('theme_citricityxund_advanced', get_string('advancedsettings', 'theme_citricityxund'));

    // Raw SCSS to include before the content.
    $setting = new admin_setting_configtextarea(
        'theme_citricityxund/scsspre',
        get_string('rawscsspre', 'theme_citricityxund'),
        get_string('rawscsspre_desc', 'theme_citricityxund'),
        '',
        PARAM_RAW
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Raw SCSS to include after the content.
    $setting = new admin_setting_configtextarea(
        'theme_citricityxund/scss',
        get_string('rawscss', 'theme_citricityxund'),
        get_string('rawscss_desc', 'theme_citricityxund'),
        '',
        PARAM_RAW
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);
}
