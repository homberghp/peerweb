<?php

/**
 * The simple table editor for the table menu. Menu is one of the tables that support
 * the simple table editor.
 *
 * @package peerweb
 * @author Pieter van den Hombergh
 * $Id: defactivity.php 1723 2014-01-03 08:34:59Z hom $
 */
require_once("ste.php");
$pdj_id = 1;
$milestone = 1;
extract($_SESSION);
$dbConn->setSqlAutoLog(true);
$page = new PageContainer("Define or update Activity ");
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setFormAction($PHP_SELF)
        ->setRelation('activity')
        ->setMenuName('activity')
        ->setKeyColumns(array('act_id'))
        //->setShowQuery(true)
    ->setNameExpression("rtrim(short)||'*'||part||': '||rtrim(ac_.description)")
        ->setOrderList(array('datum desc', 'start_time', 'short'))
        ->setListRowTemplate(array('project', 'prj_id','project_description' ,'ac_.prjm_id','datum', 'start_time', 'act_id','act_type', 'part'))
        //->setListRowTemplate(array('datum', 'start_time', 'act_id','act_type', 'part'))
        ->setFormTemplate('templates/activity.html')
        ->setSubRel('all_project_milestone')
        ->setSubRelJoinColumns(array('prjm_id'=>' prjm_id'))
        ->show();
