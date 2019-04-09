<?php
requireCap(CAP_SYSTEM);

/**
 * The simple table editor for the table menu. Menu is one of the tables that support
 * the simple table editor.
 *
 * @package peerweb
 * @author Pieter van den Hombergh
 * $Id: menu_items.php 1723 2014-01-03 08:34:59Z hom $
 */

require_once("ste.php");

$page = new PageContainer("Peerweb testscript " . basename(__FILE__) . " on DB " . $db_name);
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setFormAction(basename(__FILE__))
        ->setRelation('menu_item')
        ->setMenuName('menu_item')
        ->setKeyColumns(array('menu_name', 'column_name'))
        ->setListRowTemplate(array('menu_name', 'column_name', 'edit_type', 'item_length', 'regex_name', 'placeholder','id'))
        ->setNameExpression("rtrim(menu_name,' ')||', '||rtrim(column_name,' ')")
        ->setOrderList(array('menu_name', 'column_name'))
        ->setFormTemplate('templates/menu_item.html')
        ->show();





