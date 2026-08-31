<?php

require_once(__DIR__ . '/../../config.php');

global $DB;

header('Content-Type: application/json');

date_default_timezone_set('Asia/Kolkata');

$records = $DB->get_records('local_onlinestatus');

$response = [];

foreach ($records as $record) {

    // Example: 17 Aug 2026 | 10:42:31 AM
    $lastaccess = date('d M Y | h:i:s A', $record->lastaccess);

    $response[] = [
        "email" => $record->email,
        "lastAccess" => $lastaccess
    ];
}

echo json_encode($response);
exit;