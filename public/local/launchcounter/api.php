<?php

require_once('../../config.php');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

global $DB;

$result = [
    'authority' => 0,
    'deans'     => 0,
    'hods'      => 0,
    'pcs'       => 0,
    'faculty'   => 0,
    'visiting'  => 0,
    'lab'       => 0,
    'staff'     => 0,
    'students'  => 0
];

$records = $DB->get_records('local_launchcounter_counts');

foreach ($records as $record) {

    switch ($record->role) {

        case 'authority':
            $result['authority'] = (int)$record->count;
            break;

        case 'deans':
            $result['deans'] = (int)$record->count;
            break;

        case 'hods':
            $result['hods'] = (int)$record->count;
            break;

        case 'pcs':
            $result['pcs'] = (int)$record->count;
            break;

        case 'faculty':
            $result['faculty'] = (int)$record->count;
            break;

        case 'visiting':
            $result['visiting'] = (int)$record->count;
            break;

        case 'lab':
            $result['lab'] = (int)$record->count;
            break;

        case 'staff':
            $result['staff'] = (int)$record->count;
            break;

        case 'students':
            $result['students'] = (int)$record->count;
            break;
    }
}

echo json_encode($result);
exit;