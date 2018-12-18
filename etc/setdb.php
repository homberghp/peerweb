<?php

/**
 * this file determines the database to use (test or production) as well as some
 * other settings.
 * @package peerweb
 * $Id: setdb.php,v 1.15 2009/01/03 08:01:49 hom Exp $
 */
$etc_dir = realpath(dirname(__FILE__).'/../etc');
$site_home = realpath(dirname(__FILE__).'/../');
$site_userdir = realpath(dirname(__FILE__).'/../../');
require_once $etc_dir.'/configure.php';
