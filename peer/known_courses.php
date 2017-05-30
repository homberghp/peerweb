<?php
include_once('./peerlib/peerutils.php');
require_once'./peerlib/simplequerytable.php';
requireCap(CAP_TUTOR);
$page_opening='Select or define a project ';

$page = new PageContainer();
$page->setTitle('Fontys knwon courses');
$nav=new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$form1= new HtmlContainer('<fieldset id=\'fieldset1\'><legend>Fontys courses known by peerweb</legend>');
$sql ="select course,".
#"regexp_replace(course_description,'&','\&amp;')as course_description,".
"rtrim(course_description),".
"faculty,faculty_short as inst from fontys_course join faculty using(faculty_id)";
ob_start();
simpletable($dbConn,$sql,'<table border=\'1\' style=\'border-collapse:collapse\' summary=\'table of courses\'>');
$form1->addText(ob_get_clean());
$page->addBodyComponent($form1);
$page->addBodyComponent(new Component('<!-- db_name=$db_name $Id: known_courses.php 1723 2014-01-03 08:34:59Z hom $ -->'));
$page->addBodyComponent($nav);
$page->show();
?>
