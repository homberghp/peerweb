<?php

include_once('./peerlib/peerutils.php');
require_once('./peerlib/validators.php');
include_once('navigation2.php');
require_once 'prjMilestoneSelector2.php';

requireCap(CAP_TUTOR);
$prjm_id = 0;
$prj_id = 1;
$milestone = 1;
extract($_SESSION);

$uploadResult = '';


if (isSet($_FILES['userfile']['name']) && ( $_FILES['userfile']['name'] != '' ) 
        && (!isSet($_SESSION['userfile']) || $_SESSION['userfile'] != $_FILES['userfile']) 
        && (($prjm_id = validate($_POST['prjm_id'], 'integer', 0)) != 0)) {
    $basename = sanitizeFilename($_FILES['userfile']['name']);
    $file_size = $_FILES['userfile']['size'];
    $tmp_file = $_FILES['userfile']['tmp_name'];
    $workdir="{$tmp_file}.d";
    $worksheetbase = basename($tmp_file);
    $worksheet = "{$workdir}/worksheet.xlsx";
    if (!mkdir($workdir, 0775, true)) {
         die('cannot create dir ' . $workdir . '<br/>');
    }
    if (move_uploaded_file($tmp_file, "{$worksheet}")) {
        $uploadResult = "upload and integration was succesfull {$file_size}, {$tmp_file}, {$worksheet}}";
        
    }
    $_SESSION['userfile']=$_FILES['userfile'];
}


$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
$prjSel->setWhere("valid_until > now()::date and owner_id={$peer_id}"
        . " and exists (select 1 from prj_tutor where prjm_id=pm.prjm_id)"
        . "and not exists (select 1 from prj_grp join prj_tutor using(prjtg_id) where prjm_id=pm.prjm_id)");

extract($prjSel->getSelectedData());
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;
$page = new PageContainer();
$page_opening = "Import groups";
$page->setTitle($page_opening);
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$action = $PHP_SELF;
$page->addBodyComponent($nav);
$prjList = $prjSel->getSelector();
$templatefile = 'templates/importgroups.html';
$template_text = file_get_contents($templatefile, true);
if ($template_text === false) {
    $page->addBodyComponent(new Component("<strong>cannot read template file $templatefile</strong>"));
} else {
    eval("\$text = \"$template_text\";");
    $page->addBodyComponent(new Component($text));
}
$page->show();

