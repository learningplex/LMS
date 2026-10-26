<?php
// This file is part of Moodle - https://moodle.org/

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Custom Pages';
$string['managepages'] = 'Manage pages';
$string['nopages'] = 'No custom pages have been configured yet.';

$string['addpage'] = 'Add page';
$string['editpage'] = 'Edit page';
$string['pagetitle'] = 'Page title';
$string['pageslug'] = 'Page URL slug';
$string['providerplugin'] = 'Provider plugin';
$string['providerpageslug'] = 'Provider page identifier';
$string['visibility'] = 'Visibility';
$string['sortorder'] = 'Sort order';
$string['visible'] = 'Visible';
$string['hidden'] = 'Hidden';

$string['invalidprovider'] = 'Please select a valid provider plugin.';
$string['invalidproviderpage'] = 'The selected provider does not declare this page identifier.';
$string['duplicateslug'] = 'This URL slug is already in use.';
$string['invalidpageid'] = 'The requested page does not exist.';
$string['noproviders'] = 'No compatible local page providers are installed.';
$string['pagenotfound'] = 'This page does not exist or is disabled.';
$string['providerunavailable'] = 'The provider plugin for this page is not available.';
$string['providerpageunavailable'] = 'This page is no longer declared by its provider.';
$string['pagedeleted'] = 'Page deleted.';
$string['confirmdeletepage'] = 'Are you sure you want to delete this page registration?';

$string['allowedroles'] = 'Roles allowed to view this page';
$string['allowedroles_help'] = 'Select one or more roles. Users assigned any selected role in Moodle will be allowed to see and open this page. If no roles are selected, only site administrators can access it. Provider-specific access restrictions may also apply.';
