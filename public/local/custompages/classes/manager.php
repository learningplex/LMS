<?php
// This file is part of Moodle - https://moodle.org/

namespace local_custompages;

defined('MOODLE_INTERNAL') || die();

/**
 * Manages pages registered with the universal page manager.
 */
class manager {

    /**
     * Discover installed local plugins that implement the provider contract.
     *
     * Provider file:
     * local/PLUGIN/classes/custompages/provider.php
     *
     * @return array Component names keyed by component name.
     */
    public static function get_providers(): array {
        $providers = [];
        $plugins = \core_component::get_plugin_list('local');

        foreach ($plugins as $name => $directory) {
            $component = 'local_' . $name;
            $classname = '\\' . $component . '\\custompages\\provider';

            if (class_exists($classname)) {
                $reflection = new \ReflectionClass($classname);

                if (!$reflection->isAbstract() &&
                        $reflection->implementsInterface(provider::class)) {
                    $providers[$component] = $classname;
                }
            }
        }

        return $providers;
    }

    /**
     * Get the pages declared by one provider.
     *
     * @param string $component Provider component, e.g. local_dashboard.
     * @return array
     */
    public static function get_provider_pages(string $component): array {
        $providers = self::get_providers();

        if (!isset($providers[$component])) {
            return [];
        }

        $classname = $providers[$component];
        $instance = new $classname();
        $pages = $instance->get_pages();
        $result = [];

        foreach ($pages as $page) {
            if (empty($page['slug']) || empty($page['title'])) {
                continue;
            }

            $slug = clean_param($page['slug'], PARAM_ALPHANUMEXT);

            if ($slug !== $page['slug']) {
                continue;
            }

            $result[$slug] = [
                'slug' => $slug,
                'title' => clean_param($page['title'], PARAM_TEXT),
            ];
        }

        return $result;
    }

    /**
     * Get registered pages.
     *
     * @param bool $visibleonly Return only visible pages.
     * @return array
     */
    public static function get_pages(bool $visibleonly = false): array {
        global $DB;

        $params = [];
        $sql = 'SELECT *
                  FROM {local_custompages_pages}';

        if ($visibleonly) {
            $sql .= ' WHERE visible = :visible';
            $params['visible'] = 1;
        }

        $sql .= ' ORDER BY sortorder ASC, title ASC';

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get one registered page by its database ID.
     *
     * @param int $id Registration ID.
     * @return \stdClass|false
     */
    public static function get_page(int $id) {
        global $DB;

        return $DB->get_record(
            'local_custompages_pages',
            ['id' => $id],
            '*',
            IGNORE_MISSING
        );
    }

    /**
     * Get one registered page by its URL slug.
     *
     * @param string $slug Universal page URL slug.
     * @return \stdClass|false
     */
    public static function get_page_by_slug(string $slug) {
        global $DB;

        return $DB->get_record(
            'local_custompages_pages',
            ['slug' => $slug],
            '*',
            IGNORE_MISSING
        );
    }

    /**
     * Check whether a universal URL slug already exists.
     *
     * @param string $slug Universal page URL slug.
     * @param int|null $excludeid Registration ID to exclude.
     * @return bool
     */
    public static function slug_exists(
        string $slug,
        ?int $excludeid = null
    ): bool {
        global $DB;

        $sql = 'slug = :slug';
        $params = ['slug' => $slug];

        if ($excludeid !== null) {
            $sql .= ' AND id <> :excludeid';
            $params['excludeid'] = $excludeid;
        }

        return $DB->record_exists_select(
            'local_custompages_pages',
            $sql,
            $params
        );
    }

    /**
     * Return selectable Moodle roles.
     *
     * @return array Role IDs mapped to role names.
     */
    public static function get_role_options(): array {
        $roles = get_all_roles();
        $options = [];

        foreach ($roles as $role) {
            $options[$role->id] = role_get_name($role);
        }

        \core_collator::asort($options);
        return $options;
    }

    /**
     * Return role IDs configured for a registered page.
     *
     * @param int $pageid Registration ID.
     * @return int[]
     */
    public static function get_page_role_ids(int $pageid): array {
        global $DB;

        return array_map(
            'intval',
            $DB->get_fieldset_select(
                'local_custompages_roles',
                'roleid',
                'pageid = :pageid',
                ['pageid' => $pageid]
            )
        );
    }

    /**
     * Save the selected roles for a page.
     *
     * @param int $pageid Registration ID.
     * @param array $roleids Selected role IDs.
     */
    public static function save_page_roles(int $pageid, array $roleids): void {
        global $DB;

        $DB->delete_records('local_custompages_roles', ['pageid' => $pageid]);

        foreach (array_unique(array_map('intval', $roleids)) as $roleid) {
            if ($roleid > 0 && $DB->record_exists('role', ['id' => $roleid])) {
                $DB->insert_record('local_custompages_roles', (object)[
                    'pageid' => $pageid,
                    'roleid' => $roleid,
                ]);
            }
        }
    }

    /**
     * Check whether the current user is allowed by the configured role list.
     * A user must have one of the selected roles in any context. An empty
     * role list is restricted to site administrators.
     *
     * @param int $pageid Registration ID.
     * @return bool
     */
    public static function user_has_page_role(int $pageid): bool {
        global $DB, $USER;

        if (is_siteadmin($USER)) {
            return true;
        }

        $roleids = self::get_page_role_ids($pageid);
        if (!$roleids) {
            return false;
        }

        list($insql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'roleid');
        $params['userid'] = $USER->id;

        return $DB->record_exists_sql(
            "SELECT 1
               FROM {role_assignments} ra
              WHERE ra.userid = :userid
                AND ra.roleid $insql",
            $params
        );
    }

    /**
     * Check the combined role and optional provider access rules.
     *
     * @param \stdClass $page Registered page record.
     * @param object|null $provider Provider instance, if available.
     * @return bool
     */
    public static function can_user_view_page(\stdClass $page, $provider = null): bool {
        if (!self::user_has_page_role((int)$page->id)) {
            return false;
        }

        // Providers may add further restrictions. The role list is not a
        // replacement for permissions enforced by the provider plugin.
        if ($provider !== null && method_exists($provider, 'can_view_page')) {
            return (bool)$provider->can_view_page($page->providerslug);
        }

        return true;
    }

}
