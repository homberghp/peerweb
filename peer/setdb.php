<?php
/**
 * this file determines the database to use (test or production) as well as some
 * other settings.
 * @package peerweb
 * $Id: setdb.php,v 1.15 2009/01/03 08:01:49 hom Exp $
 */
$db_name = 'peer2';
$site_userdir='/home/hom/';
$site_home=$site_userdir.'peerweb/';
$server_url='https://www.fontysvenlo.org';
define('ADMIN_EMAILADDRESS','p.vandenhombergh@fontys.nl');
$root_url='/peertest'; // as seen from the browser
require_once 'configure.php';

