<?php

/**
 * The simple table editor for the tutor
 * @author Pieter van den Hombergh
 * $Id: tutor.php 1103 2012-02-23 19:22:48Z hom $
 */
require_once("ste.php");
requireCap(CAP_RECRUITER);
$page = new PageContainer("Peerweb verbaende on DB " . $db_name);
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setFormAction($PHP_SELF)
        ->setRelation('verbaende')
        ->setMenuName('verbaende')
        ->setKeyColumns(array('verbaende_id'))
        ->setNameExpression("rtrim(naam_school)")
        ->setListRowTemplate(array('anrede', 'aan', 'adres', 'woonplaats', 'telefon', 'telefax'))
        ->setOrderList(array('naam_school'))
        ->setFormTemplate('templates/verbaende.html')
        ->show();
?>
