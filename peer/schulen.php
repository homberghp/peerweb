<?php

/**
 * The simple table editor for the tutor
 * @author Pieter van den Hombergh
 * $Id: schulen.php 1723 2014-01-03 08:34:59Z hom $
 */
include_once("ste.php");
requireCap(CAP_RECRUITER);
$page = new PageContainer("Peerweb schulen in NRW on DB " . $db_name);
$ste = new SimpleTableEditor($dbConn,$page);
$ste->setFormAction($PHP_SELF)
        ->setRelation('schulen')
        ->setMenuName('schulen')
        ->setKeyColumns(array('schulen_id'))
        ->setNameExpression("rtrim(naam_school)")
        ->setListRowTemplate(array('schultyp', 'aan', 'url', 'adres', 'woonplaats', 'email', 'telefon', 'telefax'))
        ->setOrderList(array('naam_school'))
        ->setFormTemplate('templates/schulen.html')
        ->show()
?>
