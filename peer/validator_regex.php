<?php
requireCap(CAP_SYSTEM);

/**
 * The simple table editor for the table menu. Menu is one of the tables that support
 * the simple table editor.
 *
 * @package peerweb
 * @author Pieter van den Hombergh
 * $Id: validator_regex.php 1570 2013-08-09 19:51:30Z hom $
 */
require_once("ste.php");
$page = new PageContainer("Validator Regex Editor on DB " . $db_name);
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setTitle('Regex Editor')
        ->setFormAction($PHP_SELF)
        ->setRelation('validator_regex_slashed')
        ->setMenuName('validator_regex')
        ->setKeyColumns(array('regex_name'))
        ->setNameExpression("rtrim(regex_name,' ')||', '||rtrim(regex,' ')")
        ->setOrderList(array('regex_name'))
        ->setFormTemplate('templates/validator_regex.html')
        ->show();





