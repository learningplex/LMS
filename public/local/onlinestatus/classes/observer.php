<?php

namespace local_onlinestatus;

defined('MOODLE_INTERNAL') || die();

class observer {

    /**
     * Runs when a user logs in.
     *
     * Login means the user is online.
     *
     * @param \core\event\user_loggedin $event
     */
    public static function user_loggedin(\core\event\user_loggedin $event) {

        global $DB;

        $userid = $event->userid;

        // Ignore guest user.
        if ($userid <= 1) {
            return;
        }

        $user = $DB->get_record(
            'user',
            ['id' => $userid],
            'id,email,deleted'
        );

        if (!$user || $user->deleted || empty($user->email)) {
            return;
        }

        $now = time();

        $record = $DB->get_record(
            'local_onlinestatus',
            ['userid' => $userid]
        );

        if ($record) {

            /*
             * User has logged in.
             *
             * Keep them online until an explicit logout.
             */
            $record->status = 1;
            $record->lastaccess = $now;

            /*
             * Clear any previous logout grace period.
             */
            $record->offlineat = 0;

            $DB->update_record(
                'local_onlinestatus',
                $record
            );

        } else {

            /*
             * First login.
             */
            $record = new \stdClass();

            $record->userid = $userid;
            $record->email = $user->email;
            $record->firstlogin = $now;
            $record->lastaccess = $now;
            $record->status = 1;
            $record->offlineat = 0;

            $DB->insert_record(
                'local_onlinestatus',
                $record
            );
        }
    }

    /**
     * Runs when a user logs out.
     *
     * Keep the user online for exactly 5 minutes after logout.
     *
     * @param \core\event\user_loggedout $event
     */
    public static function user_loggedout(\core\event\user_loggedout $event) {

        global $DB;

        $userid = $event->userid;

        // Ignore guest user.
        if ($userid <= 1) {
            return;
        }

        $record = $DB->get_record(
            'local_onlinestatus',
            ['userid' => $userid]
        );

        if (!$record) {
            return;
        }

        $now = time();

        /*
         * User has logged out.
         *
         * Keep status = 1 for another 5 minutes.
         */
        $record->status = 1;
        $record->lastaccess = $now;
        $record->offlineat = $now + (5 * 60);

        $DB->update_record(
            'local_onlinestatus',
            $record
        );
    }
}

