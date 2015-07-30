<?php

/**
 * The simple table editor for the table menu. Menu is one of the tables that support
 * the simple table editor.
 *
 * @package prafda2
 * @author Pieter van den Hombergh
 * $Id: literature.php 1723 2014-01-03 08:34:59Z hom $
 */
include_once("peerutils.inc");
include_once('navigation2.inc');
include_once("utils.inc");
include_once("ste.php");
$navTitle = "Course literature" . $PHP_SELF . " on DB " . $db_name;
$page = new PageContainer();
$page->setTitle('Literature');
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setFormAction($PHP_SELF);
$ste->setRelation('literature');
$ste->setMenuName('literature');
$ste->setKeyColumns(array('literature_id'));
$ste->setNameExpression("rtrim(menu_name,' ')||', '||rtrim(column_name,' ')");
$ste->setOrderList(array('literature_code'));
$ste->setFormTemplate('templates/literature.html');

$page_opening = "Literature for all courses";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$page->addBodyComponent($nav);
$ste->render();
$page->addBodyComponent(new Component('<!-- db_name=$db_name $Id: literature.php 1723 2014-01-03 08:34:59Z hom $ -->'));
$page->show();
?>





