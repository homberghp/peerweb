<?php
requireCap(CAP_SYSTEM);
/**
 * The simple table editor for the tutor
 * @author Pieter van den Hombergh
 * $Id: module_activity_description.php 1724 2014-01-03 09:06:02Z hom $
 */
require_once("ste.php");
$page = new PageContainer("Module activity description editor on DB $db_name ");
$ste = new SimpleTableEditor($dbConn,$page);
$ste->setTitle($title)
        ->setFormAction($PHP_SELF)
        ->setRelation('module_activity_description')
        ->setMenuName('module_activity_description')
        ->setKeyColumns(array('module_activity_id', 'language_id'))
        ->setNameExpression("module_activity_id||':'||rtrim(language_id)")
        ->setListRowTemplate(array('module_activity_id','language_id','description'))
        ->setOrderList(array('module_activity_id', 'language_id'))
        ->setFormTemplate('templates/module_activity_description.html')
        ->show();
