<?php
/**
 * The simple table editor for the table menu. Menu is one of the tables that support
 * the simple table editor.
 *
 * @package prafda2
 * @author Pieter van den Hombergh
 * $Id: menu.php 1723 2014-01-03 08:34:59Z hom $
 */
include_once("peerutils.php");
include_once('navigation2.php');
include_once("utils.php");
include_once("ste.php");
$navTitle= "Peerweb testscript ".$PHP_SELF." on DB ".$db_name;
$page = new PageContainer();
$page->setTitle('Menu');
//$dbConn->setSqlAutoLog(true);
$ste = new SimpleTableEditor($dbConn,$page);
$ste->setFormAction($PHP_SELF);
$ste->setRelation('menu');
$ste->setMenuName('menu');
$ste->setKeyColumns(array('menu_name','relation_name'));
$ste->setNameExpression("rtrim(menu_name,' ')||', '||rtrim(relation_name,' ')");
$ste->setOrderList(array('menu_name','relation_name'));
$ste->setFormTemplate('templates/menu.html');
$ste->setListRowTemplate(array('menu_name','relation_name'));

$page_opening="Menu";
$nav=new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$page->addBodyComponent($nav);

if (isSet($_POST['Insert'])) {
  /* try to insert menu,column_names into menu_items */
  $menu_name= $_POST['menu_name'];
  $relation_name= $_POST['relation_name'];
  $sql = "insert into menu_item (menu_name,column_name,edit_type,capability,item_length)  select '$menu_name' as menu_name, column_name,".
    " 'T' as edit_type, 32767 as capability, character_maximum_length".
    " from information_schema.columns where table_name='$relation_name'";
  $dbMessage='';
  $result = doUpdate($dbConn,$sql,$dbMessage);
  $page->addBodyComponent(new Component("<fieldset><legend>Create menu_items</legend>\n".$dbMessage."\n</fieldset>\n"));
}
$ste->render();
$page->addBodyComponent(new Component('<!-- db_name=$db_name $Id: menu.php 1723 2014-01-03 08:34:59Z hom $ -->'));
$page->addBodyComponent(new Component('<a href="tets.php">tets</a>'.$PHP_SELF));
$page->show();
?>
