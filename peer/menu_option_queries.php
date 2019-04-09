<?php
requireCap(CAP_SYSTEM);

/**
 * @package peerweb
 * @author Pieter van den Hombergh
 * $Id: menu_option_queries.php 1723 2014-01-03 08:34:59Z hom $
 * The simple table editor for the table menu_option_queries. Menu_option_querie is
 * one of the tables that support the simple table editor. It is used to define queries to
 * get option lists in forms. Examples are: manager in medewerkers, edit_type (in menu_item)
 */
require_once("ste.php");

$page = new PageContainer("Peerweb Menu option queries on DB " . $db_name);
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setFormAction(basename(__FILE__))
        ->setRelation('menu_option_queries')
        ->setMenuName('option_queries')
        ->setKeyColumns(array('menu_name', 'column_name'))
        ->setNameExpression("rtrim(menu_name,' ')||', '||rtrim(column_name,' ')")
        ->setOrderList(array('menu_name', 'column_name'))
        ->setListRowTemplate(array('column_name','query'))
        ->setFormTemplate('templates/menu_option_queries.html')
        ->show();

