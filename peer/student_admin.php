<?php

/**
 * The simple table editor for the table menu. Menu is one of the tables that support
 * the simple table editor.
 *
 * @package prafda2
 * @author Pieter van den Hombergh
 * $Id: student_admin.php 1769 2014-08-01 10:04:30Z hom $
 */
include_once("ste.php");
requireCap(CAP_LOOKUP_STUDENT);
$page = new PageContainer("Student adminstration page " . $PHP_SELF . " on DB " . $db_name);
$ste = new SimpleTableEditor($dbConn, $page, hasCap(CAP_ALTER_STUDENT));
$ste->setFormAction($PHP_SELF)->setShowQuery(true)
        ->setRelation('student_email')
        ->setMenuName('student_admin')
        ->setKeyColumns(array('snummer'))
        ->setRawNames(array('snummer', 'class_id'))
//        ->setSubRel('study_progress')
//        ->setSubRelJoinColumns(array( 'snummer' => 'snummer' ))
//        ->setSupportingRelation('study_progress')
//        ->setSupportingJoinList(array( 'snummer' => 'snummer' ))
        ->setNameExpression("st_.snummer||' '||rtrim(achternaam,' ')||', '||rtrim(roepnaam,' ')||coalesce(' '||trim(tussenvoegsel),'')")
        ->setOrderList(array('achternaam', 'roepnaam'))
        ->setFormTemplate('templates/student_admin.html')
        ->setListRowTemplate(array('st_.snummer', 'minifoto', 'email1', 'pcn', 'hoofdgrp', 'sclass', 'cohort', 'gebdat', 'slb', 'studieplan', 'phone_gsm'))
        ->setListQueryExtension(' join minifoto fo on(st_.snummer=fo.snummer) left join student_class scn using (class_id) ')
        ->show();
?>

