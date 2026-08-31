<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'GuidePlex_ubu';
$CFG->dbuser    = 'GuidePlex_ubu';
$CFG->dbpass    = 'Gsfcu@1234';
$CFG->dbpersist = false;
$CFG->prefix    = 'mlrr_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => 3306,
  'dbsocket' => '0',
  'dbcollation' => 'utf8mb4_unicode_ci',
);

// Moodle Public URL Configuration
$CFG->sslproxy = false;
$CFG->wwwroot  = 'https://guideplexlms.gsfcuniversity.in:8083';
//$CFG->wwwroot  = 'http://10.205.19.6.in:8083';

$CFG->dataroot  = '/var/www/html/GuidePLex/.httmvdu57sq3gf.data';
$CFG->admin     = 'admin';
$CFG->noemailever = true;
$CFG->directorypermissions = 00777;
$CFG->dbsessions = false;

require_once(__DIR__ . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
// Temporary Developer Debugging for Performance Generator
//@error_reporting(E_ALL | E_STRICT);
//@ini_set('display_errors', '1');
//$CFG->debug = (E_ALL | E_STRICT);
//$CFG->debugdisplay = 1;

require_once(__DIR__ . '/lib/setup.php');
