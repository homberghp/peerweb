<?php

/**
 * The simple table editor for the tutor
 * @author Pieter van den Hombergh
 * $Id: colloquium_speakers.php 1723 2014-01-03 08:34:59Z hom $
 */
require_once("ste.php");
$page = new PageContainer("Peerweb colloqium speakers" . $PHP_SELF . " on DB " . $db_name);
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setFormAction($PHP_SELF)
        ->setRelation('colloquium_speakers')
        ->setMenuName('colloquium_speakers')
        ->setKeyColumns(array('colloquium_speaker_id'))
        ->setNameExpression("rtrim(speaker_org||':'||lastname)")
        ->setListRowTemplate(array('speaker_org', 'achternaam', 'roepnaam', 'email'))
        ->setOrderList(array('speaker_org', 'achternaam'))
        ->setFormTemplate('templates/colloquium_speakers.html')
        ->show();
?>
