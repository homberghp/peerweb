<?php
requireCap(CAP_TUTOR);

/**
 * The simple table editor for the table menu. Menu is one of the tables that support
 * the simple table editor.
 *
 * @package peerweb
 * @author Pieter van den Hombergh
 * $Id: deftask.php 1723 2014-01-03 08:34:59Z hom $
 */
require_once('navigation2.php');
require_once("utils.php");
require_once("ste.php");
$pdj_id = 1;
$milestone = 1;
extract($_SESSION);
$dbConn->setSqlAutoLog(true);
$page = new PageContainer("Peerweb testscript " . basename(__FILE__) . " on DB " . $db_name);
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setFormAction(basename(__FILE__))
        ->setRelation('project_task')
        ->setMenuName('project_task')
        ->setKeyColumns(array('task_id'))
        ->setNameExpression('name')
        ->setOrderList(array('task_id', 'name', 'description'))
        ->setListRowTemplate(array('description', 'prj_id'))
        ->setFormTemplate('../templates/tasks.html')
        ->show();
?>