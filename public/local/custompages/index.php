<?php
// This file is part of Moodle - https://moodle.org/

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_custompages\manager;

admin_externalpage_setup('local_custompages_manage');

$context = context_system::instance();
require_capability('local/custompages:manage', $context);

$deleteid = optional_param('delete', 0, PARAM_INT);
$confirmdelete = optional_param('confirmdelete', 0, PARAM_BOOL);

// Require an explicit confirmation and a valid session key before deleting.
if ($deleteid && $confirmdelete && confirm_sesskey()) {
    global $DB;
    $DB->delete_records('local_custompages_roles', ['pageid' => $deleteid]);
    $DB->delete_records('local_custompages_pages', ['id' => $deleteid]);
    redirect(
        new moodle_url('/local/custompages/index.php'),
        get_string('pagedeleted', 'local_custompages')
    );
} else if ($deleteid) {
    $confirmurl = new moodle_url('/local/custompages/index.php', [
        'delete' => $deleteid,
        'confirmdelete' => 1,
        'sesskey' => sesskey(),
    ]);
    $cancelurl = new moodle_url('/local/custompages/index.php');
    $PAGE->set_url('/local/custompages/index.php', ['delete' => $deleteid]);
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('confirmdeletepage', 'local_custompages'),
        new single_button($confirmurl, get_string('delete'), 'post'),
        $cancelurl
    );
    echo $OUTPUT->footer();
    exit;
}

$PAGE->set_url(new moodle_url('/local/custompages/index.php'));
$PAGE->set_title(get_string('managepages', 'local_custompages'));
$PAGE->set_heading(get_string('pluginname', 'local_custompages'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managepages', 'local_custompages'));

echo $OUTPUT->single_button(
    new moodle_url('/local/custompages/edit.php'),
    get_string('addpage', 'local_custompages'),
    'get'
);

$pages = manager::get_pages();

if (!$pages) {
    echo $OUTPUT->notification(
        get_string('nopages', 'local_custompages'),
        'info'
    );
} else {
    $table = new html_table();
    $table->head = [
        get_string('pagetitle', 'local_custompages'),
        get_string('pageslug', 'local_custompages'),
        get_string('providerplugin', 'local_custompages'),
        get_string('visibility', 'local_custompages'),
        get_string('sortorder', 'local_custompages'),
        get_string('actions'),
    ];

    foreach ($pages as $page) {
        $editurl = new moodle_url('/local/custompages/edit.php', ['id' => $page->id]);
        $deleteurl = new moodle_url('/local/custompages/index.php', [
            'delete' => $page->id,
            'sesskey' => sesskey(),
        ]);
        $viewurl = new moodle_url('/local/custompages/view.php', [
            'slug' => $page->slug,
        ]);

        $actions = html_writer::link($viewurl, get_string('view'));
        $actions .= ' | ' . html_writer::link($editurl, get_string('edit'));
        $actions .= ' | ' . html_writer::link(
            $deleteurl,
            get_string('delete'),
            ['data-confirm' => get_string('confirmdeletepage', 'local_custompages')]
        );

        $table->data[] = [
            format_string($page->title),
            s($page->slug),
            s($page->provider),
            $page->visible
                ? get_string('visible', 'local_custompages')
                : get_string('hidden', 'local_custompages'),
            (int)$page->sortorder,
            $actions,
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
