<?php
requireCap(CAP_RECRUITER);

/**
 * The simple table editor for the tutor
 * @author Pieter van den Hombergh
 * $Id: planned_school_visit.php 1723 2014-01-03 08:34:59Z hom $
 */
require_once("peerutils.php");
require_once('navigation2.php');
require_once("utils.php");
require_once("ste.php");

$page = new PageContainer("Peerweb planned_school_visit" . $PHP_SELF . " on DB " . $db_name);
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setTransactional(true)
        ->setFormAction($PHP_SELF)
        ->setRelation('planned_school_visit')
        ->setMenuName('planned_school_visit')
        ->setKeyColumns(array('planned_school_visit'))
        ->setSubRel('transaction_operator')
        ->setSubRelJoinColumns(array('trans_id' => 'trans_id'))
        ->setNameExpression("rtrim(visit_short)")
        ->setListRowTemplate(array('visit_short', 'visit_date'))
        ->setOrderList(array('visit_date desc', 'visit_short'))
        ->setFormTemplate('templates/planned_school_visit.html')
        ->show();
?>
