<?php
requireCap(CAP_SYSTEM);
/**
 * The simple table editor for the table menu. Menu is one of the tables that support
 * the simple table editor.
 *
 * @package peerweb
 * @author Pieter van den Hombergh
 * $Id: menu.php 1723 2014-01-03 08:34:59Z hom $
 */
require_once("peerutils.php");
require_once('navigation2.php');
require_once("utils.php");
require_once("ste.php");
$navTitle = "Peerweb testscript " . basename(__FILE__) . " on DB " . $db_name;
$page = new PageContainer();
$page->setTitle('Menu');
//$dbConn->setSqlAutoLog(true);
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setFormAction(basename(__FILE__));
$ste->setRelation('menu');
$ste->setMenuName('menu');
$ste->setKeyColumns(array('menu_name', 'relation_name'));
$ste->setNameExpression("rtrim(menu_name,' ')||', '||rtrim(relation_name,' ')");
$ste->setOrderList(array('menu_name', 'relation_name'));
$ste->setFormTemplate('templates/menu.html');
$ste->setListRowTemplate(array('menu_name', 'relation_name'));

$page_opening = "Menu";
$nav = new Navigation($tutor_navtable, basename(basename(__FILE__)), $page_opening);
$page->addBodyComponent($nav);

if (isSet($_POST['Insert'])) {
    /* try to insert menu,column_names into menu_items */
    $menu_name = $_POST['menu_name'];
    $relation_name = $_POST['relation_name'];
    $sql = "insert into menu_item (menu_name,column_name,edit_type,capability,item_length)\n"
            . "  select '{$menu_name}' as menu_name, column_name,\n"
            . " 'T' as edit_type, 32767 as capability, character_maximum_length\n"
            . " from information_schema.columns where table_name='{$relation_name}'";
    $dbMessage = "";//<pre>{$sql}</pre>";
    $result = doUpdate($dbConn, $sql, $dbMessage);
    $page->addBodyComponent(new Component("<fieldset><legend>Create menu_items</legend>\n" . $dbMessage . "\n</fieldset>\n"));
}
$ste->render();
$page->addBodyComponent(new Component('<!-- db_name=$db_name $Id: menu.php 1723 2014-01-03 08:34:59Z hom $ -->'));
$page->addBodyComponent(new Component('<a href="tets.php">tets</a>' . basename(__FILE__)));
$page->show();
