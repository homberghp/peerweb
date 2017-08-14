<?php

require_once './peerlib/peerutils.php';
require_once'./peerlib/validators.php';
require_once 'navigation2.php';
require_once 'prjMilestoneSelector2.php';
require_once './peerlib/simplequerytable.php';

requireCap(CAP_SYSTEM);
$prjm_id = 0;
$prj_id = 1;
$milestone = 1;
extract($_SESSION);

$uploadResult = '';

if (isSet($_FILES['userfile']['name']) && ( $_FILES['userfile']['name'] != '' ) && (!isSet($_SESSION['userfile']) || $_SESSION['userfile'] != $_FILES['userfile']) ) {
    $basename = sanitizeFilename($_FILES['userfile']['name']);
    $uploadResult = "<fieldset style='color:green; background:black;font-family:monospace'>";
    $file_size = $_FILES['userfile']['size'];
    $tmp_file = $_FILES['userfile']['tmp_name'];
    $workdir = "{$tmp_file}.d";
    $worksheetbase = basename($tmp_file);
    $worksheet = "{$workdir}/sv09_ingeschrevenen.xlsx";
    if (!mkdir($workdir, 0775, true)) {
        die('cannot create dir ' . $workdir . '<br/>');
    }
    if (move_uploaded_file($tmp_file, "{$worksheet}")) {
        $uploadResult .= "upload and integration was succesfull {$file_size}, {$tmp_file}, {$worksheet}";
        $cmdString = "{$site_home}/scripts/jmerge -w {$workdir} -c {$site_home}/jmerge -p {$site_home}/jmerge/sv09_syncprogress.properties";
        $cmd = `$cmdString`;
        $uploadResult .= "<pre>{$cmd}</pre></fieldset>";
    }
    $_SESSION['userfile'] = $_FILES['userfile'];
}


$page = new PageContainer();
$page_opening = "Synchronise  Student Data from Progress";
$page->setTitle($page_opening);
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$action = $PHP_SELF;
$page->addBodyComponent($nav);
$templatefile = 'templates/syncfromprogress.html';
$template_text = file_get_contents($templatefile, true);
if ($template_text === false) {
    $page->addBodyComponent(new Component("<strong>cannot read template file $templatefile</strong>"));
} else {
    eval("\$text = \"$template_text\";");
    $page->addBodyComponent(new Component($text));
}
$page->show();

