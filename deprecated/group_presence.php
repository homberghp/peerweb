<?php
requireCap(CAP_SYSTEM);
require_once 'peerutils.php';
require_once 'tutorhelper.php';
require_once 'navigation2.php';
$snummer=$_SESSION['snummer'];
$personal_presence='Y';
extract($_SESSION);
$snummer=$_SESSION['snummer'];
$presence_prj_id=54;
$course_week_no=1;
$presence_day=5;
$hourcode=6;
extract($_SESSION);
// pick up inputs
if (isSet($_REQUEST['presence_prj_id'])) {
    $_SESSION['presence_prj_id'] = $presence_prj_id=validate($_REQUEST['presence_prj_id'],'prj_id',$presence_prj_id);
 }
if (isSet($_REQUEST['presence_day'])) {
    $_SESSION['presence_day']= $presence_day= validate($_REQUEST['presence_day'],'integer',1);
 }
if (isSet($_REQUEST['hourcode'])) {
    $_SESSION['hourcode']= $hourcode= validate($_REQUEST['hourcode'],'integer',1);
 }
// get project data
$sql ="select * from project where prj_id=$presence_prj_id";
$resultSet=$dbConn->Execute($sql);
if ($resultSet == false) {
    die( "<br>Cannot get project data with <pre>$sql</pre> cause".$dbConn->ErrorMsg()."<br>");
 }
extract($resultSet->fields);



if (isSet($_REQUEST['personal_presence'])) {
    $_SESSION['personal_presence'] = $personal_presence = ($_REQUEST['personal_presence']=='Y')?'Y':'N';
 }

if ($personal_presence =='Y') {
    $page_opening="Presence of $roepnaam $tussenvoegsel $achternaam, <span style='font-size:6pt;'>($snummer)</span>";
 } else {
    $page_opening="Student presence for participants of module $presence_prj_id  $afko, $year $description";
 }

$page=new PageContainer();
$page->setTitle('Presence during fontys timetable hours');
$page_opening="Presence of $roepnaam $tussenvoegsel $achternaam ($snummer)";
$nav=new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);

ob_start();
tutorHelper($dbConn,$isTutor);
$page->addBodyComponent(new Component(ob_get_clean()));
$page->addBodyComponent($nav);
ob_start();

require_once '../peerlib/presence2.php';
$page->addBodyComponent( new Component(ob_get_clean()));
$page->show();
?>
