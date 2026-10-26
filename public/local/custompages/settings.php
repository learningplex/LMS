<?php
// This file is part of Moodle - https://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add(
        'localplugins',
        new admin_externalpage(
            'local_custompages_manage',
            new lang_string('managepages', 'local_custompages'),
            new moodle_url('/local/custompages/index.php'),
            'local/custompages:manage'
        )
    );
}
