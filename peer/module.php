<?php

/**
 * The simple table editor for the tutor
 * @author Pieter van den Hombergh
 * $Id: module.php 1723 2014-01-03 08:34:59Z hom $
 */
require_once("ste.php");
$title = "Module editor on DB $db_name ";
$page = new PageContainer();
$page->setTitle($title);

$ste = new SimpleTableEditor($dbConn, $page);
$ste->setShowQuery(true)
        ->setTitle($title)
        ->setFormAction($PHP_SELF)
        ->setRelation('module')
        ->setMenuName('module')
        ->setKeyColumns(array('module_id'))
        ->setNameExpression("semester||':'||rtrim(progress_code)")
        ->setListRowTemplate(array('semester', 'module_id', 'progress_code', 'module_description'))
        ->setOrderList(array('semester', 'progress_code', 'module_description'))
        ->setFormTemplate('templates/module.html')
        ->show();

