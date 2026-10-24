<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Login Layout
 *
 * @package    theme_academi
 * @copyright  2015 onwards LMSACE Dev Team (http://www.lmsace.com)
 * @author     LMSACE Dev Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
global $DB, $CFG, $SITE;

$bodyattributes = $OUTPUT->body_attributes();
require_once(dirname(__FILE__) . '/includes/layoutdata.php');

$theme = theme_config::load('boost');
$leftinstructions = !empty($CFG->auth_instructions)
    ? format_text($CFG->auth_instructions, FORMAT_MOODLE, ['context' => context_system::instance()])
    : null;

// Check if custom login background image is set.
$hasloginbackgroundimage = !empty($theme->setting_file_url('loginbackgroundimage', 'loginbackgroundimage'));
$showdefaultloginpanel = theme_academi_get_setting('showdefaultloginpanel') ?? false;

$showleftpanel = !empty($leftinstructions) || $hasloginbackgroundimage || $showdefaultloginpanel;

// Sag iros LMS live login statistics.
$registeredusers = $DB->count_records_select(
    'user',
    'deleted = 0 AND confirmed = 1 AND id <> :guestid',
    ['guestid' => $CFG->siteguest]
);

$totalstudents = $DB->count_records_sql("
    SELECT COUNT(DISTINCT ra.userid)
      FROM {role_assignments} ra
      JOIN {context} ctx ON ctx.id = ra.contextid
      JOIN {role} r ON r.id = ra.roleid
      JOIN {user} u ON u.id = ra.userid
     WHERE ctx.contextlevel = :systemlevel
       AND r.shortname = :studentrole
       AND u.deleted = 0
       AND u.confirmed = 1
       AND u.id <> :guestid
", [
    'systemlevel' => CONTEXT_SYSTEM,
    'studentrole' => 'students',
    'guestid' => $CFG->siteguest,
]);
$visiblecourses = $DB->count_records_sql("
    SELECT COUNT(*)
      FROM (
            SELECT c.id
              FROM {course} c
              JOIN {enrol} e
                ON e.courseid = c.id
               AND e.status = 0
              JOIN {user_enrolments} ue
                ON ue.enrolid = e.id
               AND ue.status = 0
              JOIN {user} u
                ON u.id = ue.userid
               AND u.deleted = 0
               AND u.confirmed = 1
               AND u.id <> :guestid
              JOIN {role_assignments} ra
                ON ra.userid = u.id
              JOIN {context} sysctx
                ON sysctx.id = ra.contextid
               AND sysctx.contextlevel = :systemlevel
              JOIN {role} r
                ON r.id = ra.roleid
               AND r.shortname = :studentrole
             WHERE c.visible = 1
               AND c.id <> :siteid
             GROUP BY c.id
            HAVING COUNT(DISTINCT u.id) >= 5
      ) eligiblecourses
", [
    'guestid' => $CFG->siteguest,
    'systemlevel' => CONTEXT_SYSTEM,
    'studentrole' => 'students',
    'siteid' => SITEID,
]);



$sql = "SELECT COUNT(DISTINCT ra.userid)
          FROM {role_assignments} ra
          JOIN {context} ctx ON ctx.id = ra.contextid
          JOIN {role} r ON r.id = ra.roleid
         WHERE ctx.contextlevel = :systemlevel
           AND r.shortname IN (:teacher, :editingteacher)";

$teachers = $DB->count_records_sql($sql, [
    'systemlevel' => CONTEXT_SYSTEM,
    'teacher' => 'faculty',
    'editingteacher' => 'editingteacher',
]);
$visitingfaculty = $DB->count_records_sql("
    SELECT COUNT(DISTINCT ra.userid)
      FROM {role_assignments} ra
      JOIN {context} ctx ON ctx.id = ra.contextid
      JOIN {role} r ON r.id = ra.roleid
      JOIN {user} u ON u.id = ra.userid
     WHERE ctx.contextlevel = :systemlevel
       AND r.shortname = :visitingrole
       AND u.deleted = 0
       AND u.confirmed = 1
       AND u.id <> :guestid
", [
    'systemlevel' => CONTEXT_SYSTEM,
    'visitingrole' => 'visiting',
    'guestid' => $CFG->siteguest,
]);
// Total courses in the LMS, irrespective of enrolled users.
$totalcourses = $DB->count_records_select(
    'course',
    'id <> :siteid AND id <> :frontpageid',
    [
        'siteid' => SITEID,
        'frontpageid' => 1,
    ]
);
$templatecontext += [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'leftinstructions' => $leftinstructions,
    'showleftpanel' => $showleftpanel,
    'showdefaultloginpanel' => $showdefaultloginpanel,
    'learningplex_registeredusers' => $registeredusers,
'learningplex_visiblecourses' => $visiblecourses,
'learningplex_teachers' => $teachers,
'learningplex_totalstudents' => $totalstudents,
'learningplex_visitingfaculty' => $visitingfaculty,
'learningplex_totalcourses' => $totalcourses,
];
echo $OUTPUT->render_from_template('theme_academi/login', $templatecontext);

