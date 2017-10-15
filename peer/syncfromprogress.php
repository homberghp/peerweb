<?php

require_once './peerlib/peerutils.php';
require_once'./peerlib/validators.php';
require_once 'navigation2.php';
require_once 'prjMilestoneSelector2.php';
require_once './peerlib/simplequerytable.php';
require_once 'TemplateWith.php';


requireCap(CAP_SYNC_PROGRESS);
$prjm_id = 0;
$prj_id = 1;
$milestone = 1;
extract($_SESSION);

$uploadResult = '';

if (isSet($_FILES['userfile']['name']) && ( $_FILES['userfile']['name'] != '' ) && (!isSet($_SESSION['userfile']) || $_SESSION['userfile'] != $_FILES['userfile'])) {
    $basename = sanitizeFilename($_FILES['userfile']['name']);
    $uploadResult = "<fieldset style='color:green; background:black;font-family:monospace'>";
    $file_size = $_FILES['userfile']['size'];
    $tmp_file = $_FILES['userfile']['tmp_name'];
    $userfileName=$_FILES['userfile']['name'];
//    print_r($_FILES);
    $ext = pathinfo($userfileName, PATHINFO_EXTENSION);
    $temp_file_extension="{$tmp_file}.{$ext}";
    $workdir = "{$tmp_file}.d";
    $worksheetbase = basename($tmp_file);
    $worksheet = "{$workdir}/sv09_ingeschrevenen.xlsx";
    if (!mkdir($workdir, 0775, true)) {
        die('cannot create dir ' . $workdir . '<br/>');
    }
    if (move_uploaded_file($tmp_file, $temp_file_extension)) {
        
        $uploadResult .= "upload and integration was succesfull {$file_size}, {$temp_file_extension}, {$worksheet}";
        $cmdString1 = "{$site_home}/scripts/spreadsheet2xlsx {$temp_file_extension} {$worksheet} ";
        $cmd1 = exec($cmdString1);
        $cmdString2 = "{$site_home}/scripts/jmergeSync -w {$workdir} ";
        $cmd2 = exec($cmdString2);
        $uploadResult .= "<pre>Commands \n\t{$cmdString1}  \nand \n\t{$cmdString2} executed</pre></fieldset>";
        rmDirAll($workdir);
    }
    $_SESSION['userfile'] = $_FILES['userfile'];
}


$page = new PageContainer();
$page_opening = "Synchronise  Student Data from Progress view SV09_ingeschrevenen";
$page->setTitle($page_opening);
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$action = $PHP_SELF;
$page->addBodyComponent($nav);
$templatefile = 'templates/syncfromprogress.html';
$template_text = file_get_contents($templatefile, true);
$sql="select x,comment from sv09_import_summary order by row";
$uploadResult.= simpleTableString($dbConn,$sql);

$products = glob('output/sync*.log', GLOB_BRACE);
if (count($products)) {
    $uploadResult .= "<p>Results from last sync, it might be yours:</p>"
            . "<ul>\n";
    foreach ($products as $product) {
        $n = basename($product);
        $image = getMimeTypeIcon($product);
        $uploadResult .= "<li><a href='output/$n' target='_blank'><img src='{$image}' alt='pdf'/>&nbsp;{$n}</a></li>\n";
    }
    $uploadResult .= "</ul>";
}
if ($template_text === false) {
    $page->addBodyComponent(new Component("<strong>cannot read template file $templatefile</strong>"));
} else {
    $page->addBodyComponent(new Component(templateWith($template_text, get_defined_vars())));
}
$page->show();

