<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Runs after plugin installation.
 */
function xmldb_local_launchcounter_install() {

    global $DB;

    $roles = [
        'authority',
        'deans',
        'hods',
        'pcs',
        'faculty',
        'visiting',
        'lab',
        'staff',
        'students'
    ];

    foreach ($roles as $role) {

        if (!$DB->record_exists('local_launchcounter_counts', ['role' => $role])) {

            $record = new stdClass();
            $record->role = $role;
            $record->count = 0;

            $DB->insert_record('local_launchcounter_counts', $record);
        }
    }

    return true;
}