<?php
/**
 * The simple table editor for the tutor
 * @author Pieter van den Hombergh
 * $Id: bigface_settings.php 1723 2014-01-03 08:34:59Z hom $
 */
include_once("ste.php");
$page = new PageContainer();
$page->setTitle("Bigface settings  on DB ".$db_name);
$ste = new SimpleTableEditor($dbConn,$page);
$ste->setFormAction($PHP_SELF)
        ->setRelation('bigface_settings')
        ->setMenuName('bigface_settings')
        ->setKeyColumns(array('bfkey'))
        ->setNameExpression("rtrim(bfkey)")
        ->setListRowTemplate(array('bfkey','bfvalue','comment'))
        ->setOrderList(array('bfkey'))
        ->setFormTemplate('templates/bigface_settings.html')
        ->show();
?>
