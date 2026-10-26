<?php
// This file is part of Moodle - https://moodle.org/

namespace local_custompages;

defined('MOODLE_INTERNAL') || die();

/**
 * Contract for a local plugin that provides custom pages.
 *
 * Provider classes live in:
 * local/PLUGIN/classes/custompages/provider.php
 *
 * Their namespace is:
 * local_PLUGIN\custompages
 */
interface provider {

    /**
     * Return pages supported by this provider.
     *
     * Each item must contain:
     * - slug: Stable provider-specific identifier.
     * - title: Display title.
     *
     * @return array
     */
    public function get_pages(): array;

    /**
     * Render the content for a provider-specific page.
     *
     * Output must be appropriate for the current Moodle user
     * and must enforce any additional provider permissions.
     *
     * @param string $slug Provider-specific page identifier.
     */
    public function render_page(string $slug): void;
}
