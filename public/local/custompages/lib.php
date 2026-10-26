<?php
// This file is part of Moodle - https://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Add permitted Custom Pages to Moodle's global navigation.
 *
 * @param global_navigation $navigation
 */
function local_custompages_extend_navigation(
    global_navigation $navigation
): void {
    if (!isloggedin() || isguestuser()) {
        return;
    }

    $pages = \local_custompages\manager::get_pages(true);
    $providers = \local_custompages\manager::get_providers();

    foreach ($pages as $page) {
        // Skip pages whose provider is unavailable.
        if (!isset($providers[$page->provider])) {
            continue;
        }

        $providerclass = $providers[$page->provider];
        $provider = new $providerclass();

        if (!\local_custompages\manager::can_user_view_page($page, $provider)) {
            continue;
        }

        $url = new moodle_url(
            '/local/custompages/view.php',
            ['slug' => $page->slug]
        );

        $node = $navigation->add(
            format_string($page->title),
            $url,
            navigation_node::TYPE_CUSTOM,
            null,
            'local_custompages_' . $page->id
        );

        // Ask Moodle's primary navigation to display this item in the flat
        // top navigation menu where the active theme supports it.
        $node->showinflatnavigation = true;
    }
}

