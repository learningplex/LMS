<?php

defined('MOODLE_INTERNAL') || die();

$observers = [

    [
        'eventname' => '\core\event\user_loggedin',
        'callback'  => '\local_onlinestatus\observer::user_loggedin',
        'priority'  => 9999,
        'internal'  => false,
    ],

    [
        'eventname' => '\core\event\user_loggedout',
        'callback'  => '\local_onlinestatus\observer::user_loggedout',
        'priority'  => 9999,
        'internal'  => false,
    ],

];