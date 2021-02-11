<?php

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    if ($ADMIN->fulltree) {
    $settings = new admin_settingpage('mod_ojt', get_string('pluginname', 'mod_ojt'));
    $settings->add(new admin_setting_configcheckbox(
        'mod_ojt/topicitemappendtimestamps',
        get_string('setting:topicitemappendtimestamps', 'mod_ojt'),
        get_string('setting:topicitemappendtimestamps_desc', 'mod_ojt'),
        true
    ));
    $settings->add(new admin_setting_configtextarea(
        'mod_ojt/topicitemselectdefaults',
        get_string('setting:topicitemselectdefaults', 'mod_ojt'),
        get_string('setting:topicitemselectdefaults_desc', 'mod_ojt'),
        get_string('setting:topicitemselectdefaults_def', 'mod_ojt')
    ));
    }
}