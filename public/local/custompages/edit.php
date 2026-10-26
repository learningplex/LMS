<?php
// This file is part of Moodle - https://moodle.org/

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_custompages\form\page_form;
use local_custompages\manager;

admin_externalpage_setup('local_custompages_manage');

$context = context_system::instance();
require_capability('local/custompages:manage', $context);

$id = optional_param('id', 0, PARAM_INT);
$returnurl = new moodle_url('/local/custompages/index.php');

$record = $id ? manager::get_page($id) : false;

if ($id && !$record) {
    throw new moodle_exception('invalidpageid', 'local_custompages');
}

$providers = manager::get_providers();
$provideroptions = [];

foreach (array_keys($providers) as $component) {
    $provideroptions[$component] = $component;
}

if (!$provideroptions) {
    throw new moodle_exception('noproviders', 'local_custompages');
}

$roleoptions = manager::get_role_options();
$form = new page_form(null, ['providers' => $provideroptions, 'roles' => $roleoptions]);

if ($record) {
    $record->roleids = manager::get_page_role_ids((int)$record->id);
    $form->set_data($record);
} else {
    $form->set_data((object)['roleids' => []]);
}

if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    global $DB;

    $page = (object)[
        'title' => trim($data->title),
        'slug' => trim($data->slug),
        'provider' => $data->provider,
        'providerslug' => trim($data->providerslug),
        'visible' => empty($data->visible) ? 0 : 1,
        'sortorder' => (int)$data->sortorder,
    ];

    if ($record) {
        $page->id = $record->id;
        $DB->update_record('local_custompages_pages', $page);
        $pageid = (int)$record->id;
    } else {
        $pageid = (int)$DB->insert_record('local_custompages_pages', $page);
    }

    manager::save_page_roles($pageid, (array)($data->roleids ?? []));

    redirect($returnurl, get_string('changessaved'));
}

$PAGE->set_url(new moodle_url('/local/custompages/edit.php', ['id' => $id]));
$PAGE->set_title(get_string('addpage', 'local_custompages'));
$PAGE->set_heading(get_string('pluginname', 'local_custompages'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string($record ? 'editpage' : 'addpage', 'local_custompages'));
$form->display();
echo $OUTPUT->footer();
