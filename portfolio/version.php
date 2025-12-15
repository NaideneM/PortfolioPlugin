<?php

defined('MOODLE_INTERNAL') || die();

/*
 * Version details for the Portfolio Submission plugin.
 * This file tells Moodle how to install/upgrade the plugin.
 */

$plugin->component = 'assignsubmission_portfolio';   // Full plugin name.
$plugin->version   = 2025021402;                     // YYYYMMDDVV.
$plugin->requires  = 2022041900;                     // Moodle 4.0 minimum.
$plugin->maturity  = MATURITY_ALPHA;                 // Development stage.
$plugin->release   = '0.1.0';                        // Human-readable version.
