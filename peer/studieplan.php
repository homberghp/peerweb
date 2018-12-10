<?php
requireCap(CAP_SYSTEM);
/**
 * The simple table editor for studieplan
 * @author Pieter van den Hombergh
 * $Id: tutor.php 1769 2014-08-01 10:04:30Z hom $
 */
require_once("ste.php");
$title = "Studieplan editor on DB {$db_name} ";
$page = new PageContainer($title);
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setTitle($title)
    ->setFormAction($PHP_SELF)
    ->setRelation('studieplan')
    ->setMenuName('studieplan')
    ->setKeyColumns(array('studieplan'))
    ->setNameExpression("studieplan||':'||rtrim(studieplan_short)")
    ->setListRowTemplate(array('studieplan','studieplan_short', 'studieplan_omschrijving', 'studieprogr','variant_omschrijving'))
    ->setOrderList(array('studieplan_short'))
    ->setFormTemplate('templates/studieplan.html')
    ->show();

