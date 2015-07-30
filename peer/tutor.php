<?php

/**
 * The simple table editor for the tutor
 * @author Pieter van den Hombergh
 * $Id: tutor.php 1769 2014-08-01 10:04:30Z hom $
 */
require_once("ste.php");
$title = "Tutor editor on DB {$db_name} ";
$page = new PageContainer($title);
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setShowQuery(true)
        ->setTitle($title)
        ->setFormAction($PHP_SELF)
        ->setRelation('tutor')
        ->setMenuName('tutor')
        ->setRawNames(array('userid'))
        ->setKeyColumns(array('userid'))
        ->setSubRel('student')
        ->setSubRelJoinColumns(array('userid' => 'snummer'))
        ->setNameExpression("rtrim(tutor)")
        ->setNameExpression("rtrim(tutor)||':'||rtrim(sub_rel.achternaam)")
        ->setListRowTemplate(array('tu_.faculty_id', 'sub_rel.faculty_id', 'tu_.tutor', 'userid', 'team', 'sub_rel.achternaam', 'sub_rel.roepnaam'))
        ->setOrderList(array('sub_rel.faculty_id', 'tutor'))
        ->setFormTemplate('templates/tutor.html')
        ->show();
?>
