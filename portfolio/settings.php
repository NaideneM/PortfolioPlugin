<?php

defined('MOODDLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // Enable/disable portfolio submission plugin.
    $settings->add(new admin_setting_configcheckbox(
        'assignsubmission_portfolio/default',
        get_string('pluginname', 'assignsubmission_portfolio'),
        get_string('enabled_help', 'assignsubmission_portfolio'),
        0
    ));
}
