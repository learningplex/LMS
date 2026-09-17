<?php

require_once('../../config.php');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

global $DB;

$now = time();

/*
 * Default result.
 */
$result = [
    'authority' => 0,
    'deans' => 0,
    'hods' => 0,
    'pcs' => 0,
    'faculty' => 0,
    'visiting' => 0,
    'lab' => 0,
    'staff' => 0,
    'students' => 0
];

/*
 * Get users from local_onlinestatus.
 *
 * IMPORTANT:
 *
 * We do NOT use user.lastaccess here.
 *
 * local_onlinestatus.status is the single source
 * of truth.
 *
 * If status = 1:
 *     User is online.
 *
 * If status = 1 and offlineat is still in the future:
 *     User is in the 5-minute logout grace period.
 *
 * If offlineat has expired:
 *     Mark the user offline.
 */
$sql = "SELECT u.id,
               os.status,
               os.offlineat
          FROM {user} u
          JOIN {local_onlinestatus} os
            ON os.userid = u.id
         WHERE u.id > 1
           AND u.deleted = 0
           AND os.status = 1
           AND (
                os.offlineat = 0
                OR os.offlineat > :now
           )";

$users = $DB->get_records_sql(
    $sql,
    [
        'now' => $now
    ]
);

$context = \context_system::instance();

foreach ($users as $user) {

    /*
     * Get the user's system-level roles.
     */
    $roles = get_user_roles(
        $context,
        $user->id
    );

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

    /*
     * Ignore users without a tracked dashboard role.
     */
    if (!$dashboardrole) {
        continue;
    }

    /*
     * Count online user.
     */
    $result[$dashboardrole]++;
}

/*
 * Return JSON response.
 */
echo json_encode($result);

exit;
