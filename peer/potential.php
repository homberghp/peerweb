<?php

/**
 * The simple table editor for the table menu. Menu is one of the tables that support
 * the simple table editor.
 *
 * @package peerweb
 * @author Pieter van den Hombergh
 * $Id: potential.php 1723 2014-01-03 08:34:59Z hom $
 */
include_once("peerutils.php");
requireCap(CAP_TUTOR);
include_once("ste.php");

$page = new PageContainer($navTitle = "Register a potential student on DB " . $db_name);
$page->setTitle($navTitle);
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setFormAction($PHP_SELF)
        ->setRelation('potentials')
        ->setMenuName('potentials')
        ->setKeyColumns(array('pot_id'))
        ->setFormTemplate('templates/potential.html')
        ->setNameExpression("rtrim(achternaam,' ')||', '||rtrim(roepnaam,' ')||'('||trim(coalesce(email,'no email'))||')'")
        ->setOrderList(array('achternaam', 'roepnaam'))
        ->show();
?>

