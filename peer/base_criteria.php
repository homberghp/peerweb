<?php

/**
 * The simple table editor for the table menu. Menu is one of the tables that support
 * the simple table editor.
 *
 * @package peerweb
 * @author Pieter van den Hombergh
 * $Id: base_criteria.php 1724 2014-01-03 09:06:02Z hom $
 */
requireCap(CAP_TUTOR);
include_once("ste.php");
$page = new PageContainer("Base peerweb grading criteria on DB " . $db_name);
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setFormAction($PHP_SELF)
        ->setRelation('base_criteria')
        ->setMenuName('base_criteria')
        ->setKeyColumns(array('criterium_id'))
        ->setNameExpression("nl_short")
        ->setOrderList(array('criterium_id'))
        ->setFormTemplate('templates/base_criteria.html')
//        ->setSubRel('student')
//        ->setSubRelJoinColumns(array('author' => 'snummer'))
        ->setListRowTemplate(array('criterium_id', 'author','nl_short',  'nl', 'de_short', 'de', 'en_short', 'en'))
        ->show();
?>

