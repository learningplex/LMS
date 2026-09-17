<?php

require_once(__DIR__ . '/../../config.php');

global $DB;

header('Content-Type: application/json');

date_default_timezone_set('Asia/Kolkata');

$now = time();

$records = $DB->get_records('local_onlinestatus');

$response = [];

foreach ($records as $record) {

    /*
     * Start with the stored status.
     */
    $status = (int)$record->status;

    /*
     * If the user has logged out and the 5-minute
     * grace period has expired, mark them offline.
     */
    if (
        $status == 1 &&
        !empty($record->offlineat) &&
        $now >= $record->offlineat
    ) {

        $status = 0;

        $record->status = 0;
        $record->offlineat = 0;

        $DB->update_record(
            'local_onlinestatus',
            $record
        );
    }

    /*
     * Format last access.
     */
    $lastaccess = '';

    if (!empty($record->lastaccess)) {
        $lastaccess = date(
            'd M Y | h:i:s A',
            $record->lastaccess
        );
    }

    $response[] = [
        'email' => $record->email,
        'lastAccess' => $lastaccess,
        'status' => $status
    ];
}

echo json_encode($response);

exit;
