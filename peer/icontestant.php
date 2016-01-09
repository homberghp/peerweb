<?php
/* $Id: icontestant.php 1825 2014-12-27 14:57:05Z hom $ */
include_once('./peerlib/peerutils.inc');
include_once('tutorhelper.inc');
requireCap(CAP_TUTOR);
include_once 'navigation2.inc';
require_once 'GroupPhoto.class.php';
require_once 'studentPrjMilestoneSelector.php';
require_once './peerlib/contestant_table.php';
require_once 'remarklist.php';
$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
$grp_num = 1;
$prjtg_id = 1;
extract($_SESSION);
$contestant = $snummer;
$prjSel = new StudentMilestoneSelector($dbConn, $contestant, $prjm_id);
$prjSel->setExtraConstraint(" and prjtg_id in (select distinct prjtg_id from assessment) ");
extract($prjSel->getSelectedData());
$_SESSION['prjtg_id'] = $prjtg_id;
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;
$_SESSION['grp_num'] = $grp_num;

// get data stored in session or added to session by helpers
$replyText = '';
$script =
        $lang = 'nl';
//echo "$user<br/>\n";

$sql = "select * from student where snummer=$contestant";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    print "error fetching contestant data with $sql : " . $dbConn->ErrorMsg() . "<br/>\n";
}
if (!$resultSet->EOF)
    extract($resultSet->fields, EXTR_PREFIX_ALL, 'contestant');
$lang = strtolower($contestant_lang);
$page_opening = "Assessment received by $contestant_roepnaam $contestant_voorvoegsel $contestant_achternaam ($contestant_snummer)";
$page = new PageContainer();
$page->setTitle('Peer assessment entry form');

$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
ob_start();
tutorHelper($dbConn, $isTutor);
$page->addBodyComponent(new Component(ob_get_clean()));
$page->addBodyComponent($nav);
$pg = new GroupPhoto($dbConn, $prjtg_id);
$pg->setWhereConstraint(" snummer <> $snummer");
$pg->setPictSize('84', '126');
$pg->setMaxCol(8);
$criteria = getCriteria($prjm_id);
$rainbow=new RainBow();
$criteriaList = getCriterialist($criteria, $lang, $rainbow);
$rainbow = new RainBow(STARTCOLOR, COLORINCREMENT_RED, COLORINCREMENT_GREEN, COLORINCREMENT_BLUE);
$remarkList='no remarks';
if (isSet($prjtg_id)) {
    $sqlC = "SELECT judge,roepnaam||coalesce(' '||voorvoegsel,'')||' '||achternaam||coalesce(' ('||role||')','') as naam ,ja.prj_id,\n" .
            "grp_num,criterium,milestone,grade from judge_assessment ja \n" .
            " left join student_role sr on(ja.prjm_id=sr.prjm_id and ja.judge=sr.snummer)\n" .
            " left join project_roles pr on(ja.prj_id=pr.prj_id and sr.rolenum=pr.rolenum)\n" .
            " where contestant=$judge and prjtg_id=$prjtg_id \n" .
            "order by achternaam,judge,criterium";
    $gcTable = groupContestantTable($dbConn, $sqlC, false, $criteria, $lang, $rainbow);
    $remarkList=remarkListIndividual($dbConn,$prjtg_id,$contestant_snummer);
} else {
    $gcTable = "<p>No project group selected</p>";
}
$groupPhotos = $pg->getGroupPhotos();

ob_start();
//
?>
<div id='content' style='padding:1em;'>
  <?= $prjSel->getWidget() ?>
  <?php

if (!$prjSel->isEmptySelector()) {
  include_once 'templates/icontestant.html';
}
?>
</div><?php
$page->addBodyComponent(new Component(ob_get_clean()));
$page->show();

