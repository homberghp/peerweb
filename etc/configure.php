<?php
// configures peerweb using settings ../etc/settings.php constants
//
// do not change anything below this line
//

$pg_port=5432;
require_once "{$etc_dir}/settings.php";
$rubberbase = "{$site_home}/rubberreports";
$site_dir = $site_home . '/peer'; // the dir on the server
// IF CLOSED file exists, exit after showing sign
if (is_file($site_dir . '/CLOSED')) {
    require_once '../templates/loginclosed.html';
    exit;
}

$include_path = ini_get('include_path');
$include_path = $site_home . '/peerlib:' . $include_path .':'. $site_home.'/vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/';
$include_path = ini_set('include_path', $include_path);
$subversionscriptdir = "{$site_home}/subversion";
require_once $site_home.'/vendor/autoload.php';
define('ADODB_ASSOC_CASE', 2);
define('STYLEFILE', $root_url . '/style/peertreestyle.css');
define('SITEROOT', $root_url);
define('IMAGEROOT', $root_url . '/images');
define('PHOTOROOT', $root_url . '/fotos');
define('TREEVIEW_SOURCE', './');
switch ($bgstyle) {
    case 'osirix':
        $body_background = 'background:#def url(images/osirix.png)';
        $body_class = 'osirix';
        break;
    case 'staging':
        $body_background = 'background:#def url(images/staging.png)';
        $body_class = 'staging';
        break;
    case 'test':
        $body_background = 'background:#cfc url(images/test.png)';
        $body_class = 'test';
        break;
    case 'local':
        $body_background = 'background:#fc8 url(images/local.png)';
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
$fotobase='/home/f/fontysvenlo.org/peerfotos/';
