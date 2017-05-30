<?php

/**
 * The simple table editor for the table menu. Menu is one of the tables that support
 * the simple table editor.
 *
 * @package prafda2
 * @author Pieter van den Hombergh
 * $Id: deftask.php 1723 2014-01-03 08:34:59Z hom $
 */
include_once("peerutils.php");
include_once('navigation2.inc');
include_once("utils.php");
include_once("ste.php");
$pdj_id = 1;
$milestone = 1;
extract($_SESSION);
$dbConn->setSqlAutoLog(true);
$page = new PageContainer("Peerweb testscript " . $PHP_SELF . " on DB " . $db_name);
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setFormAction($PHP_SELF)
        ->setRelation('project_task')
        ->setMenuName('project_task')
        ->setKeyColumns(array('task_id'))
        ->setNameExpression('name')
        ->setOrderList(array('task_id', 'name', 'description'))
        ->setListRowTemplate(array('description', 'prj_id'))
        ->setFormTemplate('templates/tasks.html')
        ->show();
?>