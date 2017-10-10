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

function validateStudents($dbConn, &$uploadResult) {

    $query = "select snummer,grp_num from importer.worksheet w where not exists\n"
            . " (select 1 from student where snummer=w.snummer) order by grp_num,snummer";
    $resultSet = $dbConn->Execute($query);
    $valid = true;
    if ($resultSet === FALSE) {
        echo $uploadResult;
    }
    if (!$resultSet->EOF && (($rowCount = $resultSet->RowCount()) > 0)) {
        $valid = false;
        $uploadResult .= "\n<fieldset style='background:white;color:#800'><pre>$query</pre><h2>The following student numbers are not known in peerweb</h2>" .
                simpleTableString($dbConn, $query)
                . "{$resultSet->atRow()} {$rowCount} rows</fieldset>";
    }
    return $valid;
}

function validateGroups($dbConn, &$uploadResult, $prjm_id) {

    $query = "select distinct grp_num from importer.worksheet w "
            . "where not exists (select 1 from prj_tutor where prjm_id={$prjm_id} and w.grp_num = grp_num) order by grp_num";
    $resultSet = $dbConn->Execute($query);
    $valid = true;
    if (!$resultSet->EOF) {
        $valid = false;
        $uploadResult .= "\n<fieldset style='background:white;color:#800'><h2>The following grp numbers (grp_num) are not defined in this project milestone</h2>" .
                simpleTableString($dbConn, $query)
                . "</fieldset>";
    }
    return $valid;
}

if (isSet($_FILES['userfile']['name']) && ( $_FILES['userfile']['name'] != '' ) && (!isSet($_SESSION['userfile']) || $_SESSION['userfile'] != $_FILES['userfile']) && (($prjm_id = validate($_POST['prjm_id'], 'integer', 0)) != 0)) {
    $basename = sanitizeFilename($_FILES['userfile']['name']);
    $uploadResult = "<fieldset style='color:green; background:black;font-family:monospace'>";
    $file_size = $_FILES['userfile']['size'];
    $tmp_file = $_FILES['userfile']['tmp_name'];
    $workdir = "{$tmp_file}.d";
    $worksheetbase = basename($tmp_file);
    $worksheet = "{$workdir}/worksheet.xlsx";
    if (!mkdir($workdir, 0775, true)) {
        die('cannot create dir ' . $workdir . '<br/>');
    }
    if (move_uploaded_file($tmp_file, "{$worksheet}")) {
        $uploadResult .= "upload and integration was succesfull {$file_size}, {$tmp_file}, {$worksheet}";
        $cmdString = "{$site_home}/scripts/jmerge -w {$workdir} -c {$site_home}/jmerge -p {$site_home}/jmerge/uploadgroup.properties";
        $cmd = `$cmdString`;
        $uploadResult .= "<pre>{$cmd}</pre></fieldset>";
        $valid = validateStudents($dbConn, $uploadResult) && validateGroups($dbConn, $uploadResult, $prjm_id);
        if ($valid) {
            $query = "with members as (with g as (select * from prj_tutor where prjm_id={$prjm_id})\n"
                    . "insert into prj_grp (snummer,prjtg_id) \n"
                    . "select snummer, prjtg_id from g join importer.worksheet using(grp_num) returning *)\n "
                    . " select snummer,achternaam,roepnaam,grp_num,tutor,sclass as klas \n" .
                    " from members join prj_tutor using (prjtg_id) join student using(snummer) join tutor on (tutor_id=userid)\n "
                    . " join student_class using(class_id)\n"
                    . " order by grp_num, achternaam,roepnaam";
            //$uploadResult .= "<fieldset><pre>{$query}</pre></fieldset>";
            $rainbow = new RainBow(STARTCOLOR, COLORINCREMENT_RED, COLORINCREMENT_GREEN, COLORINCREMENT_BLUE);

            $uploadResult .= "\n<fieldset style='background:white;color:#080'><h2>The following students have been added </h2>" .
                    getQueryToTableChecked($dbConn, $query, true, 3, $rainbow, -1, '', '')
                    . "</fieldset>";
            //rmDirAll($workdir);
        }
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

