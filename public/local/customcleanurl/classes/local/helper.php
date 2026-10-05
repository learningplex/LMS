<?php
namespace local_customcleanurl\local;

use moodle_url;
use stdClass;

class helper {
    public static function is_enable_customcleanurl() {
        global $CFG;
        try {
            if (isset($CFG->enablecustomcleanurl)) return $CFG->enablecustomcleanurl;
            $enablecustomcleanurl = get_config('local_customcleanurl', 'enable_customcleanurl');
            $CFG->enablecustomcleanurl = ($enablecustomcleanurl && $enablecustomcleanurl == '1');
        } catch (\Throwable $th) {
            $CFG->enablecustomcleanurl = false;
        }
        return $CFG->enablecustomcleanurl;
    }

    public static function customcleanurl_routecheck() {
        global $CFG;
        if (isset($CFG->customcleanurlroutecheck)) return $CFG->customcleanurlroutecheck;
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $CFG->wwwroot . '/customcleanurl/routetest');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpcode === 200) {
                $response = json_decode($response);
                $customcleanurlroutecheck = isset($response->status) ? (bool)$response->status : false;
            } else {
                $customcleanurlroutecheck = false;
            }
        } catch (\Throwable $th) {
            $customcleanurlroutecheck = false;
        }
        $CFG->customcleanurlroutecheck = $customcleanurlroutecheck;
        return $CFG->customcleanurlroutecheck;
    }

    public static function check_requesturl($requesturl) {
        global $CFG, $DB;

        $responsedata = [
            'status' => true,
            'moodleurl' => '',
            'filepath' => '',
            'param' => [],
            'message' => '',
            'urltype' => '',
        ];

        if (!self::is_enable_customcleanurl()) {
            $responsedata['status'] = false;
            $responsedata['message'] = get_string('featureisnotenable', 'local_customcleanurl');
            return $responsedata;
        }

        $responseuri = '';
        $cleanurltype = get_config('local_customcleanurl', 'cleanurl_type');
        $cleanurltype = explode(",", $cleanurltype);

        $subdirpath = (new \moodle_url($CFG->wwwroot))->get_path(false);
        $requestmoodleurl = new moodle_url(rtrim($requesturl, "/"));
        $requestpath = $requestmoodleurl->get_path(false);
        $requestpath = str_replace($subdirpath, '', $requestpath);

        if (in_array('defineurl', $cleanurltype)) {
            $checkcustomurlpath = $DB->get_record('local_customcleanurl', ['custom_url' => $requestpath, 'cleanurl_type' => 'defineurl']);
            if (!$checkcustomurlpath) {
                $decoderequestpath = implode('/', array_map('rawurldecode', explode('/', $requestpath)));
                $checkcustomurlpath = $DB->get_record('local_customcleanurl', ['custom_url' => $decoderequestpath, 'cleanurl_type' => 'defineurl']);
            }
            if ($checkcustomurlpath) {
                $responseuri = $checkcustomurlpath->default_url;
            }
        }

        if (!$responseuri) {
            $parts = explode("/", trim($requestpath, '/'));
            $uniquename = rawurldecode(end($parts));

            if (in_array('courseurl', $cleanurltype) && !$responseuri && $parts[0] === 'course') {
                $course = $DB->get_record('course', ['shortname' => $uniquename]);
                if ($course && count($parts) === 2) {
                    $responseuri = "/course/view.php?id=" . $course->id;
                } else if ($course && count($parts) === 3 && $parts[1] === 'edit') {
                    $responseuri = "/course/edit.php?id=" . $course->id;
                } else if (count($parts) === 4) {
                    $coursecategories = $DB->get_record('course_categories', ['id' => $parts[2]]);
                    if ($coursecategories) {
                        $responseuri = "/course/index.php?categoryid=" . $coursecategories->id;
                    }
                }
            }

            if (in_array('userurl', $cleanurltype) && !$responseuri && $parts[0] === 'user') {
                $user = $DB->get_record('user', ['username' => $uniquename]);
                if ($user && count($parts) === 3) {
                    if ($parts[1] === 'profile') {
                        $responseuri = "/user/profile.php?id=" . $user->id;
                    } else if ($parts[1] === 'edit') {
                        $responseuri = "/user/edit.php?id=" . $user->id;
                    }
                }
            }
        }

        if ($responseuri) {
            $requestparam = $requestmoodleurl->params();
            $responseurl = new moodle_url($responseuri);

            foreach ($responseurl->params() as $k => $v) {
                if (array_key_exists($k, $requestparam)) {
                    $a = new stdClass();
                    $a->param = $k;
                    $a->responsepath = $responseuri;
                    $responsedata['status'] = false;
                    $responsedata['message'] = get_string('invalidcustomparam', 'local_customcleanurl', $a);
                    return $responsedata;
                }
            }

            $rawresponsepath = $responseurl->get_path(false);
            $responsepath = str_starts_with($rawresponsepath, $subdirpath) ? substr($rawresponsepath, strlen($subdirpath)) : $rawresponsepath;

            $responsedata['urltype'] = self::geturlpathtype($responsepath);
            $responsedata['filepath'] = $CFG->dirroot . $responsepath;
            $responsedata['param'] = $responseurl->params();
            $responsedata['moodleurl'] = $responseurl->raw_out(false);

            return $responsedata;
        }

        $dirpath = $CFG->dirroot . $requestpath;
        if (is_dir($dirpath)) {
            $files = scandir($dirpath);
            foreach ($files as $filename) {
                if ($filename === 'index.html' || $filename === 'index.php') {
                    $pathinfofolder = pathinfo($filename);
                    $ext = $pathinfofolder['extension'];
                    $filepath = rtrim($dirpath, '/') . '/index.' . $ext;
                    $cleanindexpath = rtrim($requestpath, '/') . '/index.' . $ext;
                    $responsedata['filepath'] = $filepath;
                    $responsedata['urltype'] = 'dirpath';
                    $responsedata['param'] = $requestmoodleurl->params();
                    $responsedata['moodleurl'] = (new moodle_url($cleanindexpath, $requestmoodleurl->params()))->raw_out(false);
                    return $responsedata;
                }
            }
        }

        if (str_contains($requestpath, '.php') && $requestpath !== '/local/customcleanurl/route.php') {
            $cleanphppath = explode('.php', $requestpath)[0] . '.php';
            $filepath = $CFG->dirroot . $cleanphppath;
            if (file_exists($filepath)) {
                $responsedata['filepath'] = $filepath;
                $responsedata['urltype'] = 'phppath';
                $responsedata['param'] = $requestmoodleurl->params();
                $responsedata['moodleurl'] = (new moodle_url($cleanphppath, $requestmoodleurl->params()))->raw_out(false);
                return $responsedata;
            }
        }

        if ($requestpath == '/customcleanurl/routetest') {
            $responsedata['urltype'] = 'customcleanurl_routetest';
            return $responsedata;
        }

        $responsedata['urltype'] = '404';
        $responsedata['filepath'] = $CFG->dirroot . '/local/customcleanurl/404.php';
        return $responsedata;
    }

    public static function geturlpathtype($responsepath) {
        $urltypes = [
            'customcleanurl_courseurl' => ['/course/view.php', '/course/edit.php', '/course/index.php'],
            'customcleanurl_userurl' => ['/user/profile.php', '/user/edit.php'],
        ];
        foreach ($urltypes as $key => $item) {
            if (is_array($item)) {
                if (in_array($responsepath, $item, true)) return $key;
            } else {
                if ($item === $responsepath) return $key;
            }
        }
        $responsepath = str_replace('.php', '', $responsepath);
        return implode("_", explode("/", trim($responsepath, "/")));
    }

    public static function urlrewriteclass_initialize() {
        global $CFG;
        if (during_initial_install() || isset($CFG->upgraderunning)) return;
        if (self::is_enable_customcleanurl() && class_exists('\local_customcleanurl\customcleanurl')) {
            $CFG->urlrewriteclass = '\\local_customcleanurl\\customcleanurl';
        }
    }

    public static function urlredirect_initialize() {
        global $CFG, $DB, $PAGE;
        $enableurlredirect = get_config('local_customcleanurl', 'enable_urlredirect');
        if ($enableurlredirect) {
            $requesturi = $_SERVER['REQUEST_URI'];
            $subdirpath = (new \moodle_url($CFG->wwwroot))->get_path(false);
            $requestmoodleurl = new moodle_url(rtrim($requesturi, "/"));
            $requesturl = $requestmoodleurl->out(false);
            $requesturl = str_replace($CFG->wwwroot, '', $requesturl);
            $requesturl = str_replace($subdirpath, '', $requesturl);

            $checkcustomurlpath = $DB->get_record('local_customcleanurl', ['default_url' => $requesturl, 'cleanurl_type' => 'urlredirect']);
            if ($checkcustomurlpath) {
                redirect(new moodle_url($checkcustomurlpath->custom_url));
            }
        }
    }

    public static function add_define_custom_url_node(\navigation_node $secondaryview): void {
        global $PAGE, $CFG;
        if (!$PAGE->has_secondary_navigation()) return;
        if (!has_capability('local/customcleanurl:managecustomcleanurl', $PAGE->context)) return;
        if ($secondaryview->find('local_customcleanurl', null)) return;
        $currentpagelayout = $PAGE->pagelayout;
        if (in_array($currentpagelayout, ['frontpage', 'admin', 'mydashboard', 'mypublic', 'mycourses'], true)) return;
        if ($currentpagelayout === 'coursecategory') {
            $categoryid = optional_param('categoryid', 0, PARAM_INT);
            if (empty($categoryid)) return;
        }
        if (in_array($PAGE->pagetype, ['error-404', 'grade-report-overview-index', 'user-files'], true)) return;
        $cleanurltype = get_config('local_customcleanurl', 'cleanurl_type');
        $cleanurltype = explode(',', (string) $cleanurltype);
        if (!in_array('defineurl', $cleanurltype, true)) return;

        $pagerawurl = str_replace($CFG->wwwroot, '', $PAGE->url->raw_out(false));
        $url = new \moodle_url('/local/customcleanurl/define_custom_url.php', [
            'action'      => 'edit',
            'sesskey'     => sesskey(),
            'default_url' => $pagerawurl,
            'returnurl'   => $pagerawurl,
        ]);
        $node = \navigation_node::create(
            get_string('define_custom_url', 'local_customcleanurl'),
            $url,
            \navigation_node::TYPE_SETTING,
            null,
            'local_customcleanurl',
            new \pix_icon('i/settings', '')
        );
        $node->showinsecondarynavigation = true;
        $node->display = true;
        $secondaryview->add_node($node);
    }
}
