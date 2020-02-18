<?php

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'auth_catadmin\task\metadata_refresh',
        'blocking' => 0,
        'minute' => 0,
        'hour' => 0,
        'day' => '*',
        'dayoftheweek' => '*',
        'month' => '*',
    ],
    [
        'classname' => 'auth_catadmin\task\suspend_users',
        'blocking' => 0,
        'minute' => 0,
        'hour' => 0,
        'day' => '*',
        'dayoftheweek' => '*',
        'month' => '*'
    ],
];
