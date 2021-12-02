<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

$etc_dir = realpath(dirname(__FILE__).'/../../etc');
$site_home = realpath(dirname(__FILE__).'/../../');
$site_userdir = realpath(dirname(__FILE__).'/../../');
$db_name='peer2';
require_once "{$etc_dir}/peerpassword"; 
$dbConn= new PDO("pgsql:host=localhost;port=5432;dbname={$db_name}", $dbUser, $pass);
$include_path = ini_get('include_path');
$include_path = $site_home . '/peerlib:' . $include_path .':'. $site_home.'/vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/';
$include_path = ini_set('include_path', $include_path);

$root_url = '/peertest'; // as seen from the browser for this instance 
define('STYLEFILE', $root_url . '/style/peertreestyle.css');
define('SITEROOT', $root_url);
define('IMAGEROOT', $root_url . '/images');
define('PHOTOROOT', $root_url . '/fotos');
define('TREEVIEW_SOURCE', './');
