<?php

require_once './peerlib/peerutils.php';
require_once'./peerlib/validators.php';
require_once 'navigation2.php';
require_once 'prjMilestoneSelector2.php';
require_once './peerlib/simplequerytable.php';

requireCap(CAP_ENROL);
extract($_SESSION);

$uploadResult = '';


if (isSet($_FILES['userfile']['name']) && ( $_FILES['userfile']['name'] != '' ) && (!isSet($_SESSION['userfile']) || $_SESSION['userfile'] != $_FILES['userfile'])) {
    $basename = sanitizeFilename($_FILES['userfile']['name']);
    $uploadResult = "<fieldset style='color:green; background:black;font-family:monospace'>";
    $file_size = $_FILES['userfile']['size'];
    $tmp_file = $_FILES['userfile']['tmp_name'];
    $userfileName = $_FILES['userfile']['name'];
    $ext = pathinfo($userfileName, PATHINFO_EXTENSION);
    $temp_file_extension = "{$tmp_file}.{$ext}";

    $workdir = "{$tmp_file}.d";
    $worksheetbase = basename($tmp_file);
    $worksheet = "{$workdir}/sv05_aanmelders.xlsx";
    if (!mkdir($workdir, 0775, true)) {
        die('cannot create dir ' . $workdir . '<br/>');
    }
    if (move_uploaded_file($tmp_file, $temp_file_extension)) {
        $uploadResult .= "upload was succesfull {$file_size}, {$tmp_file_extension}, {$worksheet}";
        $cmdString1 = "{$site_home}/scripts/spreadsheet2xlsx {$temp_file_extension} {$worksheet} ";
        $cmd1 = exec($cmdString1);
        $cmdString2 = "{$site_home}/scripts/jmergeAndTicket -w {$workdir}";
        $cmd2 = exec($cmdString2);
        $uploadResult .= "<pre>Commands \n\t{$cmdString1}  \nand \n\t{$cmdString2} executed</pre></fieldset>";
        $uploadResult .= "<pre>results of this command will appear in the prospects table and in links on this page below.</pre></fieldset>";
    }
    $_SESSION['userfile'] = $_FILES['userfile'];
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
$page_opening = "Import New Students from Progress View SV05_aanmelders";
$page->setTitle($page_opening);
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$action = $PHP_SELF;
$page->addBodyComponent($nav);
$prjList = $prjSel->getSelector();
$templatefile = 'templates/importfromprogress.html';
$template_text = file_get_contents($templatefile, true);
$products = glob('output/{classcard,phototicket,prospects,jmerge}*', GLOB_BRACE);
if (count($products)) {
    $uploadResult .= "<p>Results from last import, it might be yours:</p>"
            . "<ul>\n";
    foreach ($products as $product) {
        $n = basename($product);
        $image = getMimeTypeIcon($product);
        $uploadResult .= "<li><a href='output/$n' target='_blank'><img src='{$image}' alt='pdf'/>&nbsp;{$n}</a></li>\n";
    }
    $uploadResult .= "</ul>"
            . "The prospects*.xlsx file contains three worksheets:"
            . "<ol>"
            . "<li><b>sv05_prospects</b> The new prospect students</li>"
            . "<li><b>sv05_ingeschreven</b> Students already known to peerweb in same course</li>"
            . "<li><b>sv05_switchers</b> Students already known but switching to another course</li>"
            . "</ol>";
}
if ($template_text === false) {
    $page->addBodyComponent(new Component("<strong>cannot read template file $templatefile</strong>"));
} else {
    eval("\$text = \"$template_text\";");
    $page->addBodyComponent(new Component($text));
}
$page->show();

