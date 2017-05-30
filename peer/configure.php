<?php
// configures peerweb using settings ../etc/settings.php constants
//
// do not change anything below this line
//
require_once "{$etc_dir}/settings.php";
$rubberbase = $site_home . 'rubberreports';
$site_dir = $site_home . '/peer'; // the dir on the server
ini_set('error_reporting', E_ALL);
$include_path = ini_get('include_path');
$include_path = $site_dir . '/peerlib:' . $include_path . ':/usr/share/php/PHPExcel/Classes';
$include_path = ini_set('include_path', $include_path);
$subversionscriptdir = $site_home . 'subversion';
define('ADODB_ASSOC_CASE', 2);
define('STYLEFILE', $root_url . '/style/peertreestyle.css');
define('SITEROOT', $root_url);
define('IMAGEROOT', $root_url . '/images');
define('PHOTOROOT', $root_url . '/fotos');
define('TREEVIEW_SOURCE', './');
switch ($bgstyle) {
    case 'test':
        $body_background = 'background:#cfc url(style/images/test.png)';
        $body_class = 'test';
        break;
    case 'local':
        $body_background = 'background:#fc8 url(style/images/local.png)';
        $body_class = 'local';
        break;
    case 'peer':
    default:
        $body_background = 'background:#eee';
        $body_class = '';
        break;
}
define('BODY_BACKGROUND', $body_background);
define('BODY_CLASS', $body_class);
//$body_background='#ffe url('.IMAGEROOT.'/fontys_fish.png)';

/* Database connection stuff. */

require_once('peerpgdbconnection.php');
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
require_once "{$etc_dir}/peerpassword";
isset($dbhost) || $dbhost = '';
$dbproto = 'pgsql';
$tutor_code = '';
$dbConn = new PeerPGDBConnection($dbproto);
if (!$dbConn->Connect($dbhost, $dbUser, $pass, $db_name)) {
    die("sorry, cannot connect to database $db_name because " . $dbConn->ErrorMsg());
}
// get database time
$sql = "select date_trunc('seconds',now()) as database_time";
$resultSet = $dbConn->Execute($sql);
extract($resultSet->fields);
//$dbConn->setLogFilename($site_home.'/log/updatelog.txt');
$dbConn->setSqlLogModifyingQuery(true);
$dbConn->setSqlLogging(true);


require_once 'peerutils.php';
//$dbConn->setLogFilename($site_home.'/log/updatelog.txt');
$dbConn->setSqlLogModifyingQuery(true);

$dbConn->setSqlLogging(true);
// must include next line before any session start
include_once("treeviewclasses.php");
// login starts a session
include_once($site_dir . '/' . 'login.php');

