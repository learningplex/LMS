<?php
namespace local_customcleanurl;

use local_customcleanurl\local\helper;
use moodle_url;

class customcleanurl implements \core\output\url_rewriter {

    public static function url_rewrite(moodle_url $url) {
        global $CFG, $PAGE;

        if (!empty($CFG->upgraderunning)) {
            return $url;
        }

        if ($url->get_path(false) === '/course/edit.php') {
            if (empty($url->param('id')) && $url->param('category') !== null) {
                return $url;
            }

            if (empty($url->param('id')) && $url->param('category') === null
                    && isset($PAGE) && $PAGE->has_set_url()
                    && $PAGE->url->get_path(false) === '/course/edit.php') {
                $pageid = $PAGE->url->param('id');
                $pagecategory = $PAGE->url->param('category');

                if (empty($pageid) && $pagecategory !== null) {
                    $newcourseurl = new moodle_url($url);
                    $newcourseurl->param('category', $pagecategory);
                    return $newcourseurl;
                } else if (!empty($pageid)) {
                    $urlwithid = new moodle_url($url);
                    $urlwithid->param('id', $pageid);
                    $url = $urlwithid;
                }
            }
        }

        $cleanurl = new \local_customcleanurl\local\clean_url($url);

        if ($cleanurl->cleanedurl instanceof moodle_url) {
            return $cleanurl->cleanedurl;
        }

        return $url;
    }

    public static function html_head_setup() {
        if (!helper::is_enable_customcleanurl()) return null;

        global $PAGE;
        $pagepath = $PAGE->url->get_path(false);

        if ($pagepath === '/course/edit.php'
                && empty($PAGE->url->param('id'))
                && $PAGE->url->param('category') !== null) {
            return null;
        }

        $cleanurl = $PAGE->url->out(false);
        $moodleurl = $PAGE->url->raw_out(false);
        $output = '';

        if ($moodleurl != $cleanurl) {
            $output .= self::get_base_href($moodleurl);
            $output .= self::get_replacestate_script($cleanurl);
            $output .= self::get_anchor_fix_javascript($cleanurl);
            $output .= self::get_link_canonical();
            if (!headers_sent()) {
                header('X-Clean-URL: ' . $cleanurl);
            }
        }
        return $output;
    }

    private static function get_base_href($uncleanedurl) {
        return "<base href=\"{$uncleanedurl}\">\n";
    }

    private static function get_replacestate_script($clean) {
        return "<script>history.replaceState && history.replaceState({}, '', '{$clean}');</script>\n";
    }

    private static function get_anchor_fix_javascript($clean) {
        return <<<HTML
<script>
document.addEventListener('click', function (event) {
    var element = event.target;
    while (element.tagName != 'A') {
        if (!element.parentElement) return;
        element = element.parentElement;
    }
    var href = element.getAttribute('href');
    var iscontrol = element.getAttribute('role') === 'button' || element.hasAttribute('data-action') || element.hasAttribute('data-toggle') || element.hasAttribute('data-bs-toggle');
    if (href && href.charAt(0) == '#' && !iscontrol) {
        element.href = '{$clean}' + href;
    }
}, true);
</script>
HTML;
    }

    private static function get_link_canonical() {
        global $PAGE;
        $cleanescaped = $PAGE->url->out(true);
        return "<link rel=\"canonical\" href=\"{$cleanescaped}\" />\n";
    }
}
