<?php

/**
 * The simple table editor for the tutor
 * @author Pieter van den Hombergh
 * $Id: presse.php 1723 2014-01-03 08:34:59Z hom $
 */
include_once("ste.php");
requireCap(CAP_RECRUITER);

$page = new PageContainer("Peerweb presse " . $PHP_SELF . " on DB " . $db_name);
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setFormAction($PHP_SELF);
$ste->setRelation('presse');
$ste->setMenuName('presse');
$ste->setKeyColumns(array('presse_id'));
$ste->setNameExpression("rtrim(firma)")
        ->setListRowTemplate(array('firma', 'firmentype', 'ort', 'email_kontaktperson', 'telefon', 'telefax', 'website'))
        ->setOrderList(array('firma'))
        ->setFormTemplate('templates/presse.html')
        ->show()
?>
