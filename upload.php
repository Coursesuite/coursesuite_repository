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
 * repo upload form
 * requires authorization header to be passed through
 * i.e. nginx  = fastcgi_param HTTP_AUTHORIZATION $http_authorization;
 *      apache = SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
 *
 * @package    respository_coursesuite
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// come one, come all
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, POST, GET');
header('Access-Control-Allow-Headers: X-Requested-With, X-Filename, Authorization');

require_once('../../config.php');

defined('MOODLE_INTERNAL') || die();

$apikey = get_config('coursesuite', 'apikey');

$bearer = null;
if (isset($_SERVER['Authorization'])) {
    $bearer = trim($_SERVER["Authorization"]);
} else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
    $bearer = trim($_SERVER["HTTP_AUTHORIZATION"]);
} else if (isset($_SERVER['HTTP_BEARER'])) { // Apache
    $bearer = trim($_SERVER["HTTP_BEARER"]);
} elseif (function_exists('apache_request_headers')) {
    $requestHeaders = apache_request_headers();
    $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
    if (isset($requestHeaders['Authorization'])) {
        $bearer = trim($requestHeaders['Authorization']);
    }
}

// debug: $bearer = $apikey;

if (empty($bearer)) die("-1");

// is the bearer specified the correct format?
$bearer = str_ireplace('Bearer: ', '', $bearer);
if (!preg_match('/^[a-f0-9]{32}$/', $bearer)) die("-2");

// does the bearer match the saved apikey?
if (strcasecmp($bearer,$apikey) !== 0) die("-3");

// save the incoming file into the repository folder
$dest = $CFG->dataroot . '/repository/coursesuite/';
$method = $_SERVER['REQUEST_METHOD'];

$raw = print_r($_SERVER, true);

if ($method == 'POST') { // direct from app

    foreach ($_FILES as $file) {
        $out = $dest . basename($file["name"]);
        if (file_exists($out)) unlink($out); // overwrite
        move_uploaded_file($file["tmp_name"], $out);
        $uploads = "post " . $file["tmp_name"] . " to " . $out;
    }

} elseif ($method == 'PUT') { // generally from curl proxy, e.g. publish.php

    $filename = basename($_SERVER['HTTP_X_FILENAME']); // don't accept paths
    $dest .= $filename;
    if (file_exists($dest)) unlink($dest); // overwrite
    $uploads = "put " . $filename . " to " . $dest;
    $in = fopen('php://input','r');
    $out = fopen($dest,'w');
    stream_copy_to_stream($in,$out);
    // file_put_contents($dest, file_get_contents('php://input'));
}

// $log = implode(PHP_EOL, ["method=$method", "apikey=$apikey", "bearer=$bearer", "files=$raw", "uploads=$uploads",""]);
// file_put_contents($dest . "upload_log.txt", $log, FILE_APPEND);

exit();