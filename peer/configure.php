<?php
//
// do not change anything below this line
//
$rubberbase=$site_home.'rubberreports';
$site_dir= $site_home.'peer'; // the dir on the server
$upload_path_prefix=$site_home.'/upload';
$upload_path_prefix='/home/f/fontysvenlo.org/peerweb/upload';
ini_set('error_reporting',E_ALL );
$include_path=ini_get('include_path');
$include_path=$site_dir.'/peerlib:'.$include_path.':/usr/share/php/PHPExcel/Classes';
$include_path=ini_set('include_path',$include_path);
$subversionscriptdir=$site_home.'subversion';
define('ADODB_ASSOC_CASE',2);
define('STYLEFILE',$root_url.'/style/peertreestyle.css');
define('SITEROOT',$root_url);
define('IMAGEROOT',$root_url.'/images');
define('PHOTOROOT',$root_url.'/fotos');
define('TREEVIEW_SOURCE','./');
if ($db_name =='peer') {
  $body_background='background:#eee';
  $body_class='';
} else { 
  $body_background='background:#cfc url(style/images/test.png)';
  $body_class='test';
}
define('BODY_BACKGROUND',$body_background);
define('BODY_CLASS', $body_class);
//$body_background='#ffe url('.IMAGEROOT.'/fontys_fish.png)';

/* Database connection stuff. */

require_once('peerpgdbconnection.php');
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
require_once'/home/etc/peerpassword';
$host = 'localhost';
$proto = 'pgsql';
$tutor_code = '';
$dbConn = new PeerPGDBConnection($proto);
if (!$dbConn->Connect('localhost', $dbUser, $pass, $db_name)) {
    die("sorry, cannot connect to database $db_name because " . $dbConn->ErrorMsg());
}
// get database time
$sql = "select date_trunc('seconds',now()) as database_time";
$resultSet = $dbConn->Execute($sql);
extract($resultSet->fields);
//$dbConn->setLogFilename($site_home.'/log/updatelog.txt');
$dbConn->setSqlLogModifyingQuery(true);
$dbConn->setSqlLogging(true);


require_once 'peerutils.inc';
//$dbConn->setLogFilename($site_home.'/log/updatelog.txt');
$dbConn->setSqlLogModifyingQuery(true);

$dbConn->setSqlLogging(true);
// must include next line before any session start
include_once("treeviewclasses.php"); 
// login starts a session
include_once($site_dir.'/'.'login.php');

