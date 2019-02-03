<?php
requireCap(CAP_SYSTEM);

/**
 * The simple table editor for the table menu. Menu is one of the tables that support
 * the simple table editor.
 *
 * @package peerweb
 * @author Pieter van den Hombergh
 * $Id: known_courses2.php 1723 2014-01-03 08:34:59Z hom $
 */
require_once('navigation2.php');
require_once("utils.php");
require_once("ste.php");

$page = new PageContainer("Fontys courses in peerweb on DB " . $db_name);
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setFormAction($PHP_SELF);
$ste->setRelation('fontys_course');
$ste->setMenuName('fontys_course')
        ->setKeyColumns(array('course'))
        ->setNameExpression("course||' :'||course_short||', '||rtrim(course_description)")
        ->setOrderList(array('fo_.course', 'course_description', 'fo_.faculty_id'))
        ->setListRowTemplate(array('fo_.course','course_short','course_description','fo_.faculty_id','faculty_short'))
        ->setSubRel('faculty')
        ->setSubRelJoinColumns(array('faculty_id' => 'faculty_id'))
        ->setFormTemplate('templates/known_courses2.html')
        ->show();
