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
 * repository_coursesuite class
 *
 * @since 2.0
 * @package    repository
 * @subpackage coursesuite
 * @copyright  2019 tim
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/repository/lib.php');

class repository_coursesuite extends repository {

    /**
     * coursesuite plugin constructor
     * @param int $repositoryid
     * @param object $context
     * @param array $options
     */
    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
        parent::__construct($repositoryid, $context, $options);
    }

    private function list_apps($token, $apps) {
        $result = [];
        // the script creates an iframe lightbox in a self-contained inline script, since this is rendered using javascript
        $lightbox = 'function e(){return Array.from(document.querySelectorAll("body *")).map(function(a){return parseFloat(window.getComputedStyle(a).zIndex)}).filter(function(a){return!isNaN(a)}).sort(function(a,b){return a-b}).pop()}function f(a){a&&a.preventDefault();var b=document.createElement("div"),d=document.createElement("iframe"),c=document.createElement("div");o="cs-overlay";if(x=document.querySelector("#"+o))return document.body.style.overflow="auto",document.body.removeChild(x),!1;b.id=o;b.style="position:fixed;top:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:"+e()+1;b.appendChild(d);d.style="position:absolute;width:90%;height:90%;left:5%;top:5%";d.src=a.target.href;c.style="position:absolute;top:calc(5% - 24px);left:96%;width:24px;height:24px;cursor:pointer";c.innerHTML="<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\"><path stroke=\"white\" d=\"M0 0l24 24M0 24L24 0\"/></svg>";c.onclick=f;b.appendChild(c);document.body.appendChild(b);document.body.style.overflow="hidden";return 1};return f(event);';
        foreach ($apps as $index => $app) {
            if ($app->app_key==='scormninja') continue;
            $url = str_replace('{token}', $token, $app->launch) . 'moodle/';
            $result[] = "<a href='{$url}' target='{$app->app_key}' class='btn btn-primary btn-sm' onclick='{$lightbox}'>{$app->name}</a> ";
        }
        return implode(PHP_EOL, $result);
    }

    public function check_login() {
        return true;
    }

    /**
     * Generate upload form
     */
    public function print_login($ajax = true) {
        return $this->get_listing();
        return true; // when true, it calls build_breadcrumb()
                    // when false it calls get_upload_template

    }

    /**
     * allow searching
     */
    public function global_search() {
        return false;
    }

    public function get_listing($path='', $page = '') {
        global $OUTPUT;

        $token = get_config("coursesuite","token");
        $apps = get_config("coursesuite","info");
        if (!empty($apps)) $apps = json_decode($apps);

        $message = $this->list_apps($token,$apps);

        // see filepicker.js for options
        $list = array(
            'list' => array(),
            'help' => 'https://www.coursesuite.com',
            'dynload' => true,
            'nologin' => true,
            'nosearch' => true,
            'message' => $message,
            'path' => array()
        );

        // We analyse the path to extract what to browse.
        $fullpath = empty($fullpath) ? $this->build_node_path('root') : $fullpath;
        $trail = explode('|', $fullpath);
        $trail = array_pop($trail);
        list($mode, $path, $unused) = $this->explode_node_path($trail);


        // Cleaning up the requested path.
        $path = trim($path, '/');
        if (!$this->is_in_repository($path)) {
            // In case of doubt on the path, reset to default.
            $path = '';
        }
        $rootpath = $this->get_rootpath();
        $abspath = rtrim($rootpath . $path, '/') . '/';

        // Retrieve list of files and directories and sort them.
        $fileslist = array();
        $dirslist = array();
        if ($dh = opendir($abspath)) {
            while (($file = readdir($dh)) != false) {
                if ($file != '.' and $file != '..') {
                    if (is_file($abspath . $file)) {
                        $fileslist[] = $file;
                    } else {
                        $dirslist[] = $file;
                    }
                }
            }
        }
        core_collator::asort($fileslist, core_collator::SORT_NATURAL);
        core_collator::asort($dirslist, core_collator::SORT_NATURAL);

        // Fill the results.
        foreach ($dirslist as $file) {
            $list['list'][] = $this->build_node($rootpath, $path, $file, true, $fullpath);
        }
        foreach ($fileslist as $file) {
            $list['list'][] = $this->build_node($rootpath, $path, $file, false, $fullpath);
        }

        $list['path'] = $this->build_breadcrumb($fullpath);
        $list['list'] = array_filter($list['list'], array($this, 'filter'));

        return $list;
    }

    /**
     * Names of the plugin settings - must contain 'pluginname' because of internal wierdness
     *
     * @return array
     */
    public static function get_type_option_names() {
        return array('apikey', 'apisecret',  'pluginname');
    }

    // this is the admin settings form which stores against plugin config
    public static function type_config_form($mform, $classname = 'repository') {
        global $CFG;
        parent::type_config_form($mform);

        $apikey = get_config('coursesuite', 'apikey');
        $apisecret = get_config('coursesuite', 'apisecret');

        $mform->addElement('text', 'apikey', get_string('apikey', 'repository_coursesuite'),
            array('value' => $clientid, 'size' => '40'));
        $mform->addRule('apikey', $strrequired, 'required', null, 'client');
        $mform->setType('apikey', PARAM_RAW_TRIMMED);

        $mform->addElement('text', 'apisecret', get_string('apisecret', 'repository_coursesuite'),
            array('value' => $clientsecret, 'size' => '40'));
        $mform->addRule('apisecret', $strrequired, 'required', null, 'client');
        $mform->setType('apisecret', PARAM_RAW_TRIMMED);

        $mform->addElement('static', null, '',  get_string('information', 'repository_coursesuite'));

        if (!is_https()) {
            $mform->addElement('static', null, '',  get_string('warninghttps', 'repository_coursesuite'));
        }

    }

    // during the process of saving the repo settings, see if we can precache the coursesuite api response
    public static function type_form_validation($mform, $data, $errors) {

        $username = isset($data['apikey']) ? $data['apikey'] : '';
        $password = isset($data['apisecret']) ? $data['apisecret'] : '';

        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/repository/coursesuite/upload.php";
        $apihost = "https://www.coursesuite.ninja";

        if (!empty($username) && !empty($password)) {

            $c = new curl(["debug"=>false,"cache"=>true]);

            $options = array();
            $options["CURLOPT_HTTPAUTH"] = CURLAUTH_DIGEST;
            $options["CURLOPT_USERPWD"] = $username . ":" . $password;
            $options["CURLOPT_FOLLOWLOCATION"] = true;
            $options["CURLOPT_RETURNTRANSFER"] = true;
            $c->setopt($options);

            $data = ["publish_url" => $url];
            $postbody = http_build_query($data);

            // create a new launch token
            $response =  $c->post($apihost . "/api/createToken/", $postbody);
            $info = $c->get_info();
            if (!empty($info['http_code']) && $info['http_code'] === 200) {
                $auth = json_decode($response);
                $token = $auth->token;
                set_config("token", $token, "coursesuite");
                unset($auth,$response);
            }

            $c->resetopt();
            $c->resetHeader();

            // now cache app names this apikey can access
            $headers = array();
            $headers[] = "Authorization: Bearer: {$username}";
            $c->setHeader($headers);

            $options = array();
            $options["CURLOPT_RETURNTRANSFER"] = true;
            $c->setopt($options);

            $response =  $c->post($apihost . "/api/info/");
            $info = $c->get_info();
            if (!empty($info['http_code']) && $info['http_code'] === 200) {
                set_config("info", $response, "coursesuite");
            }

        }
        return $errors;
    }

   /**
     * supported return types
     * @return int
     */
    public function supported_returntypes() {
        return FILE_INTERNAL; //  | FILE_EXTERNAL;
    }

    /**
     * Is this repository accessing private data?
     *
     * @return bool
     */
    public function contains_private_data() {
        return false;
    }

    /**
     * Return the rootpath of this repository instance.
     *
     * Trim() is a necessary step to ensure that the subdirectory is not '/'.
     *
     * @return string path
     * @throws repository_exception If the subdir is unsafe, or invalid.
     */
    public function get_rootpath() {
        global $CFG;
        $path = $CFG->dataroot . '/repository/coursesuite/';
        if (!is_dir($path)) {
            mkdir($path, $CFG->directorypermissions, true);
        }
        if (!is_dir($path)) {
            throw new repository_exception('The instance is not properly configured, invalid path.');
        }
        return $path;
    }


    /**
     * Extract information from a node path.
     *
     * Note, this should not include preceding paths.
     *
     * @param string $path The path of the node.
     * @return array Contains the mode, the relative path, and the display text.
     */
    protected function explode_node_path($path) {
        list($mode, $realpath, $display) = explode(':', $path);
        return array(
            $mode,
            base64_decode($realpath),
            base64_decode($display)
        );
    }

    /**
     * Build the path to a browsable node.
     *
     * @param string $mode The type of browse mode.
     * @param string $realpath The path, or similar.
     * @param string $display The way to display the node.
     * @param string $root The path preceding this node.
     * @return string
     */
    protected function build_node_path($mode, $realpath = '', $display = '', $root = '') {
        $path = $mode . ':' . base64_encode($realpath) . ':' . base64_encode($display);
        if (!empty($root)) {
            $path = $root . '|' . $path;
        }
        return $path;
    }

    /**
     * Checks if $path is part of this repository.
     *
     * Try to prevent $path hacks such as ../ .
     *
     * We do not use clean_param(, PARAM_PATH) here because it also trims down some
     * characters that are allowed, like < > ' . But we do ensure that the directory
     * is safe by checking that it starts with $rootpath.
     *
     * @param string $path relative path to a file or directory in the repo.
     * @return boolean false when not.
     */
    protected function is_in_repository($path) {
        $rootpath = $this->get_rootpath();
        if (strpos(realpath($rootpath . $path), realpath($rootpath)) !== 0) {
            return false;
        }
        return true;
    }

    /**
     * Build a file or directory node.
     *
     * @param string $rootpath The absolute path to the repository.
     * @param string $path The relative path of the object
     * @param string $name The name of the object
     * @param string $isdir Is the object a directory?
     * @param string $rootnodepath The node leading to this node (for breadcrumb).
     * @return array
     */
    protected function build_node($rootpath, $path, $name, $isdir, $rootnodepath) {
        global $OUTPUT;

        $relpath = trim($path, '/') . '/' . $name;
        $abspath = $rootpath . $relpath;
        $node = array(
            'title' => $name,
            'datecreated' => filectime($abspath),
            'datemodified' => filemtime($abspath),
        );

        if ($isdir) {
            $node['children'] = array();
            $node['thumbnail'] = $OUTPUT->image_url(file_folder_icon(90))->out(false);
            $node['path'] = $this->build_node_path('browse', $relpath, $name, $rootnodepath);

        } else {
            $node['source'] = $relpath;
            $node['size'] = filesize($abspath);
            $node['thumbnail'] = $OUTPUT->image_url(file_extension_icon($name, 90))->out(false);
            $node['icon'] = $OUTPUT->image_url(file_extension_icon($name, 24))->out(false);
            $node['path'] = $relpath;
        }

        return $node;
    }

    /**
     * Build the breadcrumb from a full path.
     *
     * @param string $path A path generated by {@link self::build_node_path()}.
     * @return array
     */
    protected function build_breadcrumb($path) {
        global $OUTPUT;
        $breadcrumb = array(array(
            'name' => get_string('root', 'repository_coursesuite'),
            'path' => $this->build_node_path('root')
        ));

        $crumbs = explode('|', $path);
        $trail = '';

        foreach ($crumbs as $crumb) {
            list($mode, $nodepath, $display) = $this->explode_node_path($crumb);
            switch ($mode) {
                case 'search':
                    $breadcrumb[] = array(
                        'name' => get_string('searchresults', 'repository_coursesuite'),
                        'path' => $this->build_node_path($mode, $nodepath, $display, $trail),
                    );
                    break;

                case 'browse':
                    $breadcrumb[] = array(
                        'name' => $display,
                        'path' => $this->build_node_path($mode, $nodepath, $display, $trail),
                    );
                    break;
            }

            $lastcrumb = end($breadcrumb);
            $trail = $lastcrumb['path'];
        }

        return $breadcrumb;
    }

    /**
     * Search files in repository.
     *
     * This search works by walking through the directories returning the files that match. Once
     * the limit of files is reached the walk stops. Whenever more files are requested, the walk
     * starts from the beginning until it reaches an additional set of files to return.
     *
     * @param string $query The query string.
     * @param int $page The page number.
     * @return mixed
     */
    public function search($query, $page = 1) {
        global $OUTPUT, $SESSION;

        $query = core_text::strtolower($query);
        $remainingdirs = 1000;
        $remainingobjects = 5000;
        $perpage = 50;

        // Because the repository API is weird, the first page is 0, but it should be 1.
        if (!$page) {
            $page = 1;
        }

        // Initialise the session variable in which we store the search related things.
        if (!isset($SESSION->repository_filesystem_search)) {
            $SESSION->repository_filesystem_search = array();
        }

        // Restore, or initialise the session search variables.
        if ($page <= 1) {
            $SESSION->repository_filesystem_search['query'] = $query;
            $SESSION->repository_filesystem_search['from'] = 0;
            $from = 0;
        } else {
            // Yes, the repository does not send the query again...
            $query = $SESSION->repository_filesystem_search['query'];
            $from = (int) $SESSION->repository_filesystem_search['from'];
        }
        $limit = $from + $perpage;
        $searchpath = $this->build_node_path('search', $query);

        // Pre-search initialisation.
        $rootpath = $this->get_rootpath();
        $found = 0;
        $toexplore = array('');

        // Retrieve list of matching files and directories.
        $matches = array();
        while (($path = array_shift($toexplore)) !== null) {
            $remainingdirs--;

            if ($objects = scandir($rootpath . $path)) {
                foreach ($objects as $object) {
                    $objectabspath = $rootpath . $path . $object;
                    if ($object == '.' || $object == '..') {
                        continue;
                    }

                    $remainingobjects--;
                    $isdir = is_dir($objectabspath);

                    // It is a match!
                    if (strpos(core_text::strtolower($object), $query) !== false) {
                        $found++;
                        $matches[] = array($path, $object, $isdir);

                        // That's enough, no need to find more.
                        if ($found >= $limit) {
                            break 2;
                        }
                    }

                    // I've seen enough files, I give up!
                    if ($remainingobjects <= 0) {
                        break 2;
                    }

                    // Add the directory to things to explore later.
                    if ($isdir) {
                        $toexplore[] = $path . trim($object, '/') . '/';
                    }
                }
            }

            if ($remainingdirs <= 0) {
                break;
            }
        }

        // Extract the results from all the matches.
        $matches = array_slice($matches, $from, $perpage);

        // If we didn't reach our limits of browsing, and we appear to still have files to find.
        if ($remainingdirs > 0 && $remainingobjects > 0 && count($matches) >= $perpage) {
            $SESSION->repository_filesystem_search['from'] = $limit;
            $pages = -1;

        // We reached the end of the repository, or our limits.
        } else {
            $SESSION->repository_filesystem_search['from'] = 0;
            $pages = 0;
        }

        // Organise the nodes.
        $results = array();
        foreach ($matches as $match) {
            list($path, $name, $isdir) = $match;
            $results[] = $this->build_node($rootpath, $path, $name, $isdir, $searchpath);
        }

        $list = array();
        $list['list'] = array_filter($results, array($this, 'filter'));
        $list['dynload'] = true;
        $list['nologin'] = true;
        $list['page'] = $page;
        $list['pages'] = $pages;
        $list['path'] = $this->build_breadcrumb($searchpath);

        return $list;
    }

    /**
     * Return file path.
     * @return array
     */
    public function get_file($file, $title = '') {
        global $CFG;
        $file = ltrim($file, '/');
        if (!$this->is_in_repository($file)) {
            throw new repository_exception('Invalid file requested.');
        }
        $file = $this->get_rootpath() . $file;

        // This is a hack to prevent move_to_file deleting files in local repository.
        $CFG->repository_no_delete = true;
        return array('path' => $file, 'url' => '');
    }

    /**
     * Return the source information
     *
     * @param stdClass $filepath
     * @return string|null
     */
    public function get_file_source_info($filepath) {
        return $filepath;
    }

}

