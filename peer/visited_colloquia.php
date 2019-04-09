<?php
requireCap(CAP_SYSTEM);

/* $Id: visited_colloquia.php 1761 2014-05-24 13:17:31Z hom $ */
require_once('simplequerytable.php');
require_once('makeinput.php');
require_once('tutorhelper.php');
require_once 'navigation2.php';
$judge=$snummer;
$sql="select * from student_email where snummer=$judge";
$resultSet=$dbConn->Execute($sql);
if ($resultSet === false) {
    print "error fetching judge data with $sql : ".$dbConn->ErrorMsg()."<br/>\n";
 }
if (!$resultSet->EOF) extract($resultSet->fields,EXTR_PREFIX_ALL,'judge');

$page_opening='The colloquia visited'." by $judge_roepnaam $judge_tussenvoegsel $judge_achternaam ($judge_snummer)";
$page=new PageContainer();
$page->setTitle($page_opening);
$nav=new Navigation($tutor_navtable, basename(__FILE__), $page_opening);
$nav->setInterestMap($tabInterestCount);

$nav->addLeftNavText(file_get_contents('news.html'));
ob_start();
tutorHelper($dbConn,$isTutor);
$page->addBodyComponent(new Component(ob_get_clean()));
$page->addBodyComponent($nav);
ob_start();

?>
<table width='100%'><tr><td valign='top'>
<div style='padding:1em'>
<h2>This page informs you about the colloquia in which your presence was recorded</h2>
<fieldset><legend>Colloquia you visited</legend>
<a href='activityreport.php' target='_blank'>Print a report in pdf</a>
<?php
$sql="select datum as date, to_char(start_time,'HH24:MI') as time,short as title,part as p, description from activity join activity_participant using(act_id) where snummer=$judge order by date,time";
$resultSet= $dbConn->Execute($sql);
if ($resultSet === false) {
    $dbConn->log('Error '.$dbConn->ErrorMsg()." with ".$sql);
 } else {
    simpletable($dbConn,$sql,
		"<table width='100%' summary='visited colloquia' ".
		"border='0' style='background:rgba(255,255,255,0.5);border:1px 1px;' >\n");

 }
?>
</fieldset>
</div>
</td></tr></table>
<!-- db_name=<?=$db_name?> -->
<?php
$page->addBodyComponent( new Component(ob_get_clean()));
$page->show();
?>
