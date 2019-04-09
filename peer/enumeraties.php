<?php
requireCap(CAP_SYSTEM);

/**
 * The simple table editor for the table menu. Menu is one of the tables that support
 * the simple table editor.
 *
 * @package peerweb
 * @author Pieter van den Hombergh
 * $Id: enumeraties.php 1723 2014-01-03 08:34:59Z hom $
 */
require_once("ste.php");
$page = new PageContainer("Set enumerations in menus " . basename(__FILE__) . " on DB " . $db_name);
$ste = new SimpleTableEditor($dbConn, $page, hasCap(CAP_SYSTEM));
$ste->setFormAction(basename(__FILE__))
        ->setRelation('enumeraties')
        ->setMenuName('enumeraties')
        ->setKeyColumns(array('id'))
        ->setFormTemplate('templates/enumeraties.html')
        ->setListRowTemplate(array('id', 'menu_name', 'column_name', 'name', 'value', 'sort_order', 'is_default'))
        ->setNameExpression("menu_name||', '||column_name")
        ->show();
?>

