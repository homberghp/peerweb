<?php
requireCap(CAP_SYSTEM);

/**
 * The simple table editor for the tutor
 * @author Pieter van den Hombergh
 * $Id: module.php 1723 2014-01-03 08:34:59Z hom $
 */
require_once("ste.php");
$title = "Partial grade event editor on DB $db_name ";
$page = new PageContainer();
$page->setTitle($title);

$ste = new SimpleTableEditor($dbConn, $page);
$ste->setTitle($title)
        ->setFormAction(basename(__FILE__))
        ->setRelation('partial_grade_event')
        ->setMenuName('partial_grade_event')
        ->setKeyColumns(array('partial_grade_event_id'))
        ->setNameExpression("rtrim(event_exam_code)||'-'||event_date")
        ->setListRowTemplate(array('event_exam_code','event_date','description','owner'))
        ->setOrderList(array('event_date', 'event_exam_code', 'description'))
        ->setFormTemplate('../templates/partial_grade_event.html')
        ->show();

