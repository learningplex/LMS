<?php
// This file is part of Moodle - https://moodle.org/

require_once(__DIR__ . '/../../config.php');

use local_custompages\manager;

$slug = required_param('slug', PARAM_ALPHANUMEXT);

$page = manager::get_page_by_slug($slug);

if (!$page || !(int)$page->visible) {
    throw new moodle_exception('pagenotfound', 'local_custompages');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/custompages/view.php', ['slug' => $slug]));
$PAGE->set_title(format_string($page->title));
$PAGE->set_heading(format_string($page->title));

$providers = manager::get_providers();

if (!isset($providers[$page->provider])) {
    throw new moodle_exception('providerunavailable', 'local_custompages');
}

$providerclass = $providers[$page->provider];
$provider = new $providerclass();
$providerpages = manager::get_provider_pages($page->provider);

if (!isset($providerpages[$page->providerslug])) {
    throw new moodle_exception('providerpageunavailable', 'local_custompages');
}

if (!manager::can_user_view_page($page, $provider)) {
    throw new required_capability_exception(
        $context,
        'local/custompages:view',
        'nopermissions',
        ''
    );
}

echo $OUTPUT->header();
$provider->render_page($page->providerslug);
echo $OUTPUT->footer();
