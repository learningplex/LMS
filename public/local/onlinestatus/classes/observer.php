<?php
namespace local_onlinestatus;

defined('MOODLE_INTERNAL') || die();

class observer {

    /**
     * Runs whenever a user logs in.
     *
     * @param \core\event\user_loggedin $event
     */
    public static function user_loggedin(\core\event\user_loggedin $event) {

        global $DB;

        // Logged in user's ID.
        $userid = $event->userid;

        // Get the Moodle user record.
        $user = $DB->get_record('user', ['id' => $userid]);

        // Stop if no user or no email.
        if (!$user || empty($user->email)) {
            return;
        }

        // Check if this email already exists.
        $record = $DB->get_record(
            'local_onlinestatus',
            ['email' => $user->email]
        );

        // Existing user -> update last login time.
        if ($record) {

            $record->lastaccess = time();

            $DB->update_record(
                'local_onlinestatus',
                $record
            );

        } else {

            // First login -> insert new record.
            $newrecord = new \stdClass();

            $newrecord->email = $user->email;
            $newrecord->firstlogin = time();
            $newrecord->lastaccess = time();

            $DB->insert_record(
                'local_onlinestatus',
                $newrecord
            );
        }
    }
}