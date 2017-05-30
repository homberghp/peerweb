<?php
/* $Id: mypeerresults.php 1761 2014-05-24 13:17:31Z hom $ */
include_once('./peerlib/peerutils.php');
include_once('./peerlib/simplequerytable.php');
include_once('makeinput.php');
include_once('tutorhelper.php');
include_once 'navigation2.inc';
$judge=$snummer;
$sql="select * from student where snummer=$judge";
$resultSet=$dbConn->Execute($sql);
if ($resultSet === false) {
    print "error fetching judge data with $sql : ".$dbConn->ErrorMsg()."<br/>\n";
 }
if (!$resultSet->EOF) extract($resultSet->fields,EXTR_PREFIX_ALL,'judge');
$student_data="$judge_roepnaam $judge_voorvoegsel $judge_achternaam ($judge_snummer)";
$page_opening='All peer assessment results for '.$student_data;
$page=new PageContainer();
$page->setTitle($page_opening);
$nav=new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
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
<h2>This page informs you about your peer assessment results of all assessments</h2>
<p>All assessment results for <b><?=$student_data?></b></p>
<fieldset><legend>Group membership</legend>
<?php
  $lang='\''.$lang.'\'';
$sql="select year,afko,description,milestone,grp_num,criterium as crit_num,".
  " case \n".
  "   when $lang='de' then de_short\n".
  "   when $lang='nl' then nl_short\n".
  "   else en_short\n".
  " end as criterium\n,".
  " round(grade,2)||'('||round(grp_avg,2)||')' as grade,\n".
  " round(multiplier,2) as muliplier\n".
  "  from my_peer_results_2 mr join project using(prj_id)\n".
  "  where snummer=$snummer and assessment_complete = true order by year desc,prj_id,milestone,criterium";

$resultSet= $dbConn->Execute($sql);
if ($resultSet === false) {
    $dbConn->log('Error '.$dbConn->ErrorMsg()." with ".$sql);
 } else {
    simpletable($dbConn,$sql,
		"<table summary='group memership' ".
		"border='1'  style='border-collapse:collapse;background:white;border:1px 1px;' >\n");
    //    $dbConn->log($sql);
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
