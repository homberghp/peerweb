<?php

/**
 * The simple table editor for the table menu. Menu is one of the tables that support
 * the simple table editor.
 *
 * @package prafda2
 * @author Pieter van den Hombergh
 * $Id: enumeraties.php 1723 2014-01-03 08:34:59Z hom $
 */
require_once("ste.php");
requireCap(CAP_SYSTEM);
$page = new PageContainer("Set enumerations in menus " . $PHP_SELF . " on DB " . $db_name);
$ste = new SimpleTableEditor($dbConn, $page, hasCap(CAP_SYSTEM));
$ste->setFormAction($PHP_SELF)
        ->setRelation('enumeraties')
        ->setMenuName('enumeraties')
        ->setKeyColumns(array('id'))
        ->setFormTemplate('templates/enumeraties.html')
        ->setListRowTemplate(array('id', 'menu_name', 'column_name', 'name', 'value', 'sort_order', 'is_default'))
        ->setNameExpression("menu_name||', '||column_name")
        ->show();
?>

