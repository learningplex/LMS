<?php
// This file is part of Moodle - https://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade local_custompages.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_custompages_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026101200) {
        $table = new xmldb_table('local_custompages_pages');

        if (!$dbman->table_exists($table)) {
            $table = new xmldb_table('local_custompages_pages');

            $table->add_field('id', XMLDB_TYPE_INTEGER, '10',
                null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('title', XMLDB_TYPE_CHAR, '255',
                null, XMLDB_NOTNULL, null, '');
            $table->add_field('slug', XMLDB_TYPE_CHAR, '100',
                null, XMLDB_NOTNULL, null, '');
            $table->add_field('provider', XMLDB_TYPE_CHAR, '100',
                null, XMLDB_NOTNULL, null, '');
            $table->add_field('providerslug', XMLDB_TYPE_CHAR, '100',
                null, XMLDB_NOTNULL, null, '');
            $table->add_field('visible', XMLDB_TYPE_INTEGER, '1',
                null, XMLDB_NOTNULL, null, '1');
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10',
                null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('slug_uix', XMLDB_INDEX_UNIQUE, ['slug']);
            $table->add_index('provider_ix', XMLDB_INDEX_NOTUNIQUE, ['provider']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026101200, 'local', 'custompages');
    }

    if ($oldversion < 2026102500) {
        $table = new xmldb_table('local_custompages_roles');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10',
                null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('pageid', XMLDB_TYPE_INTEGER, '10',
                null, XMLDB_NOTNULL);
            $table->add_field('roleid', XMLDB_TYPE_INTEGER, '10',
                null, XMLDB_NOTNULL);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('page_fk', XMLDB_KEY_FOREIGN,
                ['pageid'], 'local_custompages_pages', ['id']);
            $table->add_index('page_role_uix', XMLDB_INDEX_UNIQUE,
                ['pageid', 'roleid']);
            $table->add_index('role_ix', XMLDB_INDEX_NOTUNIQUE, ['roleid']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026102500, 'local', 'custompages');
    }

    return true;
}
