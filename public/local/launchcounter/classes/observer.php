<?php

namespace local_launchcounter;

defined('MOODLE_INTERNAL') || die();

class observer {

    /**
     * Triggered after a successful user login.
     *
     * @param \core\event\user_loggedin $event
     */
    public static function user_loggedin(\core\event\user_loggedin $event) {

        global $DB;

        $userid = $event->objectid;

        // Ignore guest user.
        if ($userid <= 1) {
            return;
        }

        // Already counted?
        if ($DB->record_exists('local_launchcounter_users', ['userid' => $userid])) {
            return;
        }

        // Get user context.
        $context = \context_system::instance();

        // Get all user roles.
        $roles = get_user_roles($context, $userid);

        $dashboardrole = null;

        foreach ($roles as $role) {

    switch ($role->shortname) {

        case 'authority':
            $dashboardrole = 'authority';
            break 2;

        case 'deans':
            $dashboardrole = 'deans';
            break 2;

        case 'hods':
            $dashboardrole = 'hods';
            break 2;

        case 'pcs':
            $dashboardrole = 'pcs';
            break 2;

        case 'faculty':
            $dashboardrole = 'faculty';
            break 2;

        case 'visiting':
            $dashboardrole = 'visiting';
            break 2;

        case 'lab':
            $dashboardrole = 'lab';
            break 2;

        case 'staff':
            $dashboardrole = 'staff';
            break 2;

        case 'students':
            $dashboardrole = 'students';
            break 2;
    }
}

        // User is not one of the tracked roles.
        if (!$dashboardrole) {
            return;
        }

        // Get current counter.
        $counter = $DB->get_record(
            'local_launchcounter_counts',
            ['role' => $dashboardrole],
            '*',
            MUST_EXIST
        );

        // Increment.
        $counter->count++;

        $DB->update_record(
            'local_launchcounter_counts',
            $counter
        );

        // Save user as counted.
        $record = new \stdClass();

        $record->userid = $userid;
        $record->timecounted = time();

        $DB->insert_record(
            'local_launchcounter_users',
            $record
        );
    }
}