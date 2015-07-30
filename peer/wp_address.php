<?php

/**
 * The simple table editor for the table menu. Menu is one of the tables that support
 * the simple table editor.
 *
 * @package prafda2
 * @author Pieter van den Hombergh
 * $Id: student_admin.php 1102 2012-02-22 21:44:36Z hom $
 */
require_once("ste.php");
requireCap(CAP_LOOKUP_STUDENT);
$page = new PageContainer("Student work placement address " . $PHP_SELF . " on DB " . $db_name);
$ste = new SimpleTableEditor($dbConn, $page, hasCap(CAP_ALTER_STUDENT));
$ste->setFormAction($PHP_SELF)
        ->setRelation('wp_address')
        ->setMenuName('wp_address')
        ->setKeyColumns(array('wp_address_id'))
        ->setFormTemplate('templates/wp_address.html')
        ->setListRowTemplate(array('snummer', 'wp_type', 'company'))
        ->setNameExpression("snummer||': '||wp_type||', start '||start_date||' at '||company")
        ->show();
?>

