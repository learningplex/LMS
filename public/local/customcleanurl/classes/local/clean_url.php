<?php
namespace local_customcleanurl\local;

use moodle_url;

class clean_url {
    private $originalurl;
    private $params;
    private $path;
    public $cleanedurl;

    public function __construct(moodle_url $url) {
        $this->originalurl = $url;
        $this->path = $this->originalurl->get_path(false);
        $this->params = $this->originalurl->params();
        $this->cleanedurl = null;
        $this->execute();
    }

    private function execute() {
        if (!in_array($this->originalurl->get_scheme(), ['http', 'https'])) return;
        if (!helper::is_enable_customcleanurl()) return;
        $this->clean_path();
        $this->create_cleaned_url();
    }

    private function remove_index_php($removelastpath = '') {
        if ($removelastpath) {
            if (substr($this->path, -strlen($removelastpath)) == $removelastpath) {
                return substr($this->path, 0, -strlen($removelastpath));
            }
        }
        if (substr($this->path, -10) == '/index.php') {
            return substr($this->path, 0, -10);
        }
        if (substr($this->path, -4) == '.php') {
            return substr($this->path, 0, -4);
        }
    }

    private function create_cleaned_url() {
        $this->path = ltrim($this->path, '/');
        if ($this->path) {
            $this->path = "/" . $this->path;
            $originalpath = $this->originalurl->get_path(false);
            if ($this->path == $originalpath) {
                $this->cleanedurl = $this->originalurl;
                return;
            }
            $this->cleanedurl = new moodle_url($this->path, $this->params);
            return;
        }
    }

    private function clean_path() {
        global $DB, $CFG;
        $cleanurltype = get_config('local_customcleanurl', 'cleanurl_type');
        $cleanurltype = explode(",", $cleanurltype);
        if (!isset($CFG->subdirpath)) {
            $CFG->subdirpath = (new \moodle_url($CFG->wwwroot))->get_path(false);
        }

        if (in_array('defineurl', $cleanurltype)) {
            $defaulturl = str_replace($CFG->wwwroot, '', $this->originalurl->raw_out(false));
            $checkcustomurlpath = $DB->get_record('local_customcleanurl', ['default_url' => $defaulturl, 'cleanurl_type' => 'defineurl']);
            if ($checkcustomurlpath) {
                $this->path = implode('/', array_map('rawurlencode', explode('/', $checkcustomurlpath->custom_url)));
                $this->params = [];
                return;
            }
        }

        if (in_array('courseurl', $cleanurltype)) {
            if (preg_match('#^' . $CFG->subdirpath . '/course#', $this->path, $matches)) {
                $this->clean_course_url();
                return;
            }
        }

        if (in_array('userurl', $cleanurltype)) {
            if (preg_match('#^' . $CFG->subdirpath . '/user/(profile|edit)\.php#', $this->path, $matches)) {
                $this->clean_users_profile_url();
                return;
            }
        }
    }

    private function clean_course_url() {
        global $DB, $CFG;
        if (!empty($CFG->subdirpath)) {
            if (strpos($this->path, $CFG->subdirpath) === 0) {
                $this->path = substr($this->path, strlen($CFG->subdirpath));
            }
        }

        $coursepath = ['/course/view.php', '/course/edit.php', '/course/index.php'];
        if (!in_array($this->path, $coursepath)) return false;

        if (empty($this->params['id']) && isset($this->params['category'])) return false;

        $courseid = isset($this->params['id']) ? $this->params['id'] : '';
        $categoryid = isset($this->params['categoryid']) ? $this->params['categoryid'] : '';

        if ($courseid == '1') return false;

        $cleannewpath = $this->remove_index_php('/view.php');
        if ($courseid) {
            $course = $DB->get_record('course', ['id' => $courseid]);
            if ($course) {
                unset($this->params['id']);
                $cleannewpath = $cleannewpath . '/' . rawurlencode(strtolower($course->shortname));
                if ($this->check_path_allowed($cleannewpath)) {
                    $this->path = $cleannewpath;
                }
            }
        } else if ($categoryid) {
            $coursecategories = $DB->get_record('course_categories', ['id' => $categoryid]);
            if ($coursecategories) {
                unset($this->params['categoryid']);
                $cleannewpath = $cleannewpath . '/category/' . $coursecategories->id . '/' . rawurlencode(strtolower($coursecategories->name));
                if ($this->check_path_allowed($cleannewpath)) {
                    $this->path = $cleannewpath;
                }
            }
        }

        return false;
    }

    private function clean_users_profile_url() {
        if (empty($this->params['id'])) return null;

        global $DB, $CFG;
        if (!empty($CFG->subdirpath)) {
            if (strpos($this->path, $CFG->subdirpath) === 0) {
                $this->path = substr($this->path, strlen($CFG->subdirpath));
            }
        }

        $user = $DB->get_record('user', ['id' => $this->params['id']]);
        if ($user) {
            unset($this->params['id']);
            $cleannewpath = $this->remove_index_php();
            $cleannewpath = $cleannewpath . '/' . rawurlencode(strtolower($user->username));
            if ($this->check_path_allowed($cleannewpath)) {
                $this->path = $cleannewpath;
            }
        }
        return $user;
    }

    private function check_path_allowed($path) {
        global $CFG;
        return (!is_dir($CFG->dirroot . $path) && !is_file($CFG->dirroot . $path . ".php"));
    }
}
