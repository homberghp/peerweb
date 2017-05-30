<?php
include_once('./peerlib/peerutils.php');
require_once('./peerlib/validators.php');
include_once('navigation2.inc');
include './peerlib/simplequerytable.php';
require_once 'prjMilestoneSelector2.php';
requireCap(CAP_TUTOR);

$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
extract($_SESSION);

$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
extract($prjSel->getSelectedData());
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;

$filename = 'ads_data_' . $afko . '-' . date('Ymd') . '.csv';

$csvout = 'N';
$csvout_checked = '';
if (isSet($_REQUEST['csvout'])) {
    $csvout = $_REQUEST['csvout'];
    $csvout_checked = ($csvout == 'Y') ? 'checked' : '';
}


$sql = "select snummer,achternaam,roepnaam,voorvoegsel,lower(course_short) as opleiding,\n"
        . "cohort,email1 as email,pcn,hoofdgrp,lower(lang) as language from student \n"
        . "join fontys_course on(course=opl) where snummer in \n" .
        "(select snummer from prj_grp join prj_tutor using(prjtg_id) where prjm_id=$prjm_id)\n";
//$dbConn->log($sql);
$rainbow = new RainBow(STARTCOLOR, COLORINCREMENT_RED, COLORINCREMENT_GREEN, COLORINCREMENT_BLUE);
if ($csvout == 'Y') {
    $dbConn->queryToCSV( $sql, $filename, ',', true, 'Content-type: application/vnd.sun.xml.calc; charset: utf-8;');
    exit(0);
}
pagehead('Get ads data for students in project');
$page_opening = "Ads data to create student accounts in fthv domain for project "
        . "<i>\"$afko $description\"</i> prj_id $prj_id milestone $milestone prjm_id $prjm_id";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);

$prjSel->setJoin('milestone_grp using (prj_id,milestone)');
$prj_id_selector = $prjSel->getSelector();
?>
<?= $nav->show() ?>
<div id='navmain' style='padding:1em;'>
    <fieldset><legend>Select project</legend>
        <form method="get" name="project" action="<?= $PHP_SELF; ?>">
            <?= $prj_id_selector ?>
            csv:
            <input type='checkbox' name='csvout' <?= $csvout_checked ?> value='Y' />
            <input type='submit' name='get' value='Get' />
        </form><br/>
        <?= simpletable($dbConn, $sql, "<table summary='candidates' style='border-collapse:collapse' border='1'>"); ?>
    </fieldset>
</div>
</body>
</html>
<?php
echo "<!-- dbname=$db_name -->";
?>5A