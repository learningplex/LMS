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
 * Htaccess management helper for local_customcleanurl plugin.
 *
 * @package    local_customcleanurl
 * @copyright  2025 https://santoshmagar.com.np/
 * @author     santoshtmp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

namespace local_customcleanurl\local;

/**
 * A class to check and modify htaccess file to rewrite the server route
 *
 * @package    local_customcleanurl
 * @copyright  2025 santoshtmp <https://santoshmagar.com.np/>
 * @author     santoshtmp
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class htaccess {
    /**
     * Get absolute path to the `.htaccess` file inside Moodle root.
     *
     * @return string Path to .htaccess file.
     */
    protected static function get_htaccessfilepath() {
        global $CFG;
        return $CFG->dirroot . '/.htaccess';
    }

    /**
     * Check if the required rewrite rules exist in `.htaccess`.
     *
     * @return bool True if customcleanurl rewrite rules are found, false otherwise.
     */
    public static function check_rewrite_htaccess() {
        try {
            if (file_exists(self::get_htaccessfilepath())) {
                $contents = file_get_contents(self::get_htaccessfilepath());
                return str_contains($contents, self::get_default_htaccess_content());
            } else {
                return false;
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the default `.htaccess` rewrite rules required by this plugin.
     *
     * @return string The default rewrite rules block.
     */
    public static function get_default_htaccess_content() {
        global $CFG;
        $subdirpath = (new \moodle_url($CFG->wwwroot))->get_path(false);
        return trim("
# BEGIN_MOODLE_LOCAL_CUSTOMCLEANURL
# DO NOT EDIT LOCAL_CUSTOMCLEANURL ROUTE
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ " . $subdirpath . "/local/customcleanurl/route.php [L]
ErrorDocument 403 " . $subdirpath . "/local/customcleanurl/404.php
ErrorDocument 404 " . $subdirpath . "/local/customcleanurl/404.php
</IfModule>
# DO NOT EDIT LOCAL_CUSTOMCLEANURL ROUTE
# END_MOODLE_LOCAL_CUSTOMCLEANURL
        ");
    }
}
