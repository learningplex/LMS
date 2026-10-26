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
 * Columns 2 Layout
 * @package    theme_academi
 * @copyright  2015 onwards LMSACE Dev Team (http://www.lmsace.com)
 * @author    LMSACE Dev Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once(dirname(__FILE__) . '/themedata.php');

$preset = optional_param('preset', 0, PARAM_TEXT);
if (!empty($preset) && isset($preset)) {
    set_config('preset', $preset, 'theme_academi');
    // Purge the theme cache to show the old icons in the GUI.
    theme_reset_all_caches();
}

// Add block button in editing mode.
$addblockbutton = $OUTPUT->addblockbutton();
// Add block button in editing mode.
$addblockbutton = $OUTPUT->addblockbutton();


if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
} else {
    $courseindexopen = false;
    $blockdraweropen = false;
}

if (defined('BEHAT_SITE_RUNNING')) {
    $blockdraweropen = true;
}

$extraclasses = ['uses-drawers'];
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if (!$hasblocks) {
    $blockdraweropen = false;
}
$courseindex = core_course_drawer();
if (!$courseindex) {
    $courseindexopen = false;
}

$themestyleheader = theme_academi_get_setting('themestyleheader');
$extraclasses[] = ($themestyleheader) ? 'theme-based-header' : 'moodle-based-header';

$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

$secondarynavigation = false;
$overflow = '';
if ($PAGE->has_secondary_navigation()) {
    $tablistnav = $PAGE->has_tablist_secondary_navigation();
    $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    $secondarynavigation = $moremenu->export_for_template($OUTPUT);
    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $overflow = $overflowdata->export_for_template($OUTPUT);
    }
}


/**
 * Add role-authorised Custom Pages to the primary navigation.
 */
if (class_exists('\local_custompages\manager')
        && isloggedin()
        && !isguestuser()) {

    $manager = '\local_custompages\manager';

    // Initialise the primary navigation before inserting custom pages.
    $PAGE->primarynav->initialise();

    $custompages = $manager::get_pages(true);

    usort($custompages, function($a, $b) {
        $order = (int)$a->sortorder <=> (int)$b->sortorder;
        return $order ?: strcasecmp($a->title, $b->title);
    });

    $providers = $manager::get_providers();

    foreach ($custompages as $custompage) {
        $provider = $providers[$custompage->provider] ?? null;

        if (!$provider || !class_exists($provider)) {
            continue;
        }

        $providerinstance = new $provider();

        if (!$manager::can_user_view_page($custompage, $providerinstance)) {
            continue;
        }

        $url = new \moodle_url(
            '/local/custompages/view.php',
            ['slug' => $custompage->slug]
        );

        $key = 'local_custompages_' . $custompage->id;

        $node = \navigation_node::create(
            format_string($custompage->title),
            $url,
            \navigation_node::TYPE_CUSTOM,
            null,
            $key
        );

        // Insert before Admin Area, instead of appending to the menu.
        $PAGE->primarynav->add_node($node, 'siteadminnode');

        $node->showinflatnavigation = true;
    }
}

$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
// If the settings menu will be included in the header then don't add it here.
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);

$coursefullname = ($PAGE->course?->fullname) ? format_string(
    $PAGE->course->fullname,
    true,
    ['context' => context_course::instance($PAGE->course->id), 'escape' => false],
) : '';
$courseurl = $PAGE->course ? new \core\url('/course/view.php', ['id' => $PAGE->course->id]) : null;

$templatecontext += [
    'sitename' => format_string($SITE->fullname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'coursefullname' => $coursefullname,
    'courseurl' => $courseurl ? $courseurl->out(false) : null,
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'courseindexopen' => $courseindexopen,
    'blockdraweropen' => $blockdraweropen,
    'courseindex' => $courseindex,
    'primarymoremenu' => $primarymenu['moremenu'],
    'secondarymoremenu' => $secondarynavigation ?: false,
    'mobileprimarynav' => $primarymenu['mobileprimarynav'],
    'usermenu' => $primarymenu['user'],
    'langmenu' => $primarymenu['lang'],
    'forceblockdraweropen' => $forceblockdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'overflow' => $overflow,
    'headercontent' => $headercontent,
    'addblockbutton' => $addblockbutton,
];
