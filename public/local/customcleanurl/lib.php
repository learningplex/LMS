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
 * Local plugin callbacks for customcleanurl.
 *
 * @package    local_customcleanurl
 * @copyright  2025 https://santoshmagar.com.np/
 * @author     santoshtmp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

/**
 * Callback executed before HTTP headers are sent.
 * From moodle 4.4 callback are managed through callback hook
 *
 * This hook allows the plugin to initialize URL rewriting
 * before Moodle sends any output headers.
 *
 * @see https://moodledev.io/docs/4.5/apis/core/hooks
 * @see https://docs.moodle.org/dev/Output_callbacks#before_http_headers
 *
 */
function local_customcleanurl_before_http_headers() {
    if (class_exists("\local_customcleanurl\local\helper")) {
        \local_customcleanurl\local\helper::urlrewriteclass_initialize();
    }
    \local_customcleanurl\local\helper::urlredirect_initialize();
}


/**
 * Callback executed immediately after config.php has been processed.
 *
 * This hook is useful for early initialization, such as
 * enabling URL rewriting logic at the start of execution.
 *
 * @see https://docs.moodle.org/dev/Login_callbacks#after_config
 *
 */
function local_customcleanurl_after_config() {
    if (class_exists("\local_customcleanurl\local\helper")) {
        \local_customcleanurl\local\helper::urlrewriteclass_initialize();
    }
}
