<?php

// This line protects the file from being accessed by a URL directly.                                                               
defined('MOODLE_INTERNAL') || die();                                                                                                
                                                                                                                                    
// This is used for performance, we don't need to know about these settings on every page in Moodle, only when                      
// we are looking at the admin settings pages.                                                                                      
if ($ADMIN->fulltree) {                                                                                                             
                                                                                                                                    
    // Boost provides a nice setting page which splits settings onto separate tabs. We want to use it here.                         
    $settings = new theme_boost_admin_settingspage_tabs('themesettingcitricityxund', get_string('configtitle', 'theme_citricityxund'));             
                                                                                                                                    
    // Each page is a tab - the first is the "General" tab.                                                                         
    $page = new admin_settingpage('theme_citricityxund_general', get_string('generalsettings', 'theme_citricityxund'));                                                                                                                                 

    // Variable $brand-color.                                                                                                       
    // We use an empty default value because the default colour should come from the preset.                                        
    $name = 'theme_citricityxund/brandcolor';                                                                                               
    $title = get_string('brandcolor', 'theme_citricityxund');                                                                               
    $description = get_string('brandcolor_desc', 'theme_citricityxund');                                                                    
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '');                                               
    $setting->set_updatedcallback('theme_reset_all_caches');                                                                        
    $page->add($setting);
    
    // Login page background setting.                                                                                               
    // We use variables for readability.                                                                                            
    $name = 'theme_citricityxund/loginbackgroundimage';                                                                                     
    $title = get_string('loginbackgroundimage', 'theme_citricityxund');                                                                     
    $description = get_string('loginbackgroundimage_desc', 'theme_citricityxund');                                                                                                                                                      
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'loginbackgroundimage');                                                                
    $setting->set_updatedcallback('theme_citricityxund_update_settings_images');                                                                                                                             
    $page->add($setting);
                                                                                                                                    
    // Must add the page after definiting all the settings!                                                                         
    $settings->add($page);                                                                                                          
                                                                                                                                    
    // Advanced settings.                                                                                                           
    $page = new admin_settingpage('theme_citricityxund_advanced', get_string('advancedsettings', 'theme_citricityxund'));                           
                                                                                                                                    
    // Raw SCSS to include before the content.                                                                                      
    $setting = new admin_setting_configtextarea('theme_citricityxund/scsspre',                                                              
        get_string('rawscsspre', 'theme_citricityxund'), get_string('rawscsspre_desc', 'theme_citricityxund'), '', PARAM_RAW);                      
    $setting->set_updatedcallback('theme_reset_all_caches');                                                                        
    $page->add($setting);                                                                                                           
                                                                                                                                    
    // Raw SCSS to include after the content.                                                                                       
    $setting = new admin_setting_configtextarea('theme_citricityxund/scss', get_string('rawscss', 'theme_citricityxund'),                           
        get_string('rawscss_desc', 'theme_citricityxund'), '', PARAM_RAW);                                                                  
    $setting->set_updatedcallback('theme_reset_all_caches');                                                                        
    $page->add($setting);                                                                                                           
                                                                                                                                    
    $settings->add($page);                                                                                                          
}