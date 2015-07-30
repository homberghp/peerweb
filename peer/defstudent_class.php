<?php

/**
 * The simple table editor for the table menu. Menu is one of the tables that support
 * the simple table editor.
 *
 * @package prafda2
 * @author Pieter van den Hombergh
 * $Id: defstudent_class.php 1723 2014-01-03 08:34:59Z hom $
 */
require_once("peerutils.inc");
requireCap(CAP_TUTOR);
include_once("ste.php");
$page = new PageContainer("Class adminstration page  on DB " . $db_name);
//$dbConn->setSqlAutoLog(true);
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setFormAction($PHP_SELF)
        ->setRelation('student_class')
        ->setMenuName('student_class')
        ->setKeyColumns(array('class_id'))
->setListRowTemplate(array('class_id', 'sclass','sort1', 'sort2', 'faculty_id', 'class_cluster', 'owner', 'comment'))
        ->setNameExpression("class_id||' name '||sclass")
//        ->setSupportingRelation('faculty')
//        ->setSupportingJoinList(array('faculty_id' => 'faculty_id'))
        ->setOrderList(array('sort1', 'sort2', 'sclass'))
        ->setFormTemplate('templates/class_admin.html')
        ->show();
?>

