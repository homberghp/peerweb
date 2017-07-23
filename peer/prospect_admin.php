<?php

/**
 * The simple table editor for the table menu. Menu is one of the tables that support
 * the simple table editor.
 *
 * @package peerweb
 * @author Pieter van den Hombergh
 * $Id: student_admin.php 1769 2014-08-01 10:04:30Z hom $
 */
include_once("ste.php");
requireCap(CAP_LOOKUP_STUDENT);
$page = new PageContainer("Prospect Student adminstration " . $PHP_SELF . " on DB " . $db_name);
$ste = new SimpleTableEditor($dbConn, $page, hasCap(CAP_ALTER_STUDENT));
$ste->setFormAction($PHP_SELF)->setShowQuery(true)
        ->setRelation('prospects')
        ->setMenuName('prospects')
        ->setKeyColumns(array('snummer'))
        ->setRawNames(array('snummer', 'class_id'))
        ->setNameExpression("pr_.snummer||' '||rtrim(achternaam,' ')||', '||rtrim(roepnaam,' ')||coalesce(' '||trim(tussenvoegsel),'')")
        ->setOrderList(array('achternaam', 'roepnaam'))
        ->setFormTemplate('templates/prospect_admin.html')
        ->setListRowTemplate(array('pr_.snummer', 'email1', 'email2', 'pcn', 'hoofdgrp','country', 'lang', 'gebdat', 'slb', 'studieplan', 'phone_gsm'))
        ->setListQueryExtension(' left join iso3166 on(geboorteland=a3)')

        ->show();

