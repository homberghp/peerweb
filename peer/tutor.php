<?php
requireCap(CAP_TUTOR_ADMIN);

/**
 * The simple table editor for the tutor
 * @author Pieter van den Hombergh
 * $Id: tutor.php 1769 2014-08-01 10:04:30Z hom $
 */
require_once("ste.php");
$title = "Tutor editor on DB {$db_name} ";
$page = new PageContainer($title);
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setTitle($title)
        ->setFormAction(basename(__FILE__))
        ->setRelation('tutor')
        ->setMenuName('tutor')
    ->setRawNames(array('userid','roepnaam','achternaam','tussenvoegsel','teaches', 'office'))
        ->setKeyColumns(array('userid'))
        ->setSubRel('student_email')
        ->setSubRelJoinColumns(array('userid' => 'snummer'))
        ->setNameExpression("rtrim(tutor)")
        ->setNameExpression("rtrim(tutor)||':'||rtrim(sub_rel.achternaam)")
        ->setListRowTemplate(array('tu_.faculty_id', 'sub_rel.faculty_id', 'tu_.tutor', 'userid', 'team', 'sub_rel.achternaam', 'sub_rel.roepnaam'))
        ->setOrderList(array('sub_rel.faculty_id', 'tutor'))
        ->setFormTemplate('../templates/tutor.html')
        ->show();
?>
