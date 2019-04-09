<?php
requireCap(CAP_LOOKUP_STUDENT);

/**
 * The simple table editor for the table menu. Menu is one of the tables that support
 * the simple table editor.
 *
 * @package peerweb
 * @author Pieter van den Hombergh
 * $Id: student_admin.php 1769 2014-08-01 10:04:30Z hom $
 */
require_once("ste.php");
$page = new PageContainer("Student adminstration page");
$ste = new SimpleTableEditor($dbConn, $page, hasCap(CAP_ALTER_STUDENT));
$ste->setFormAction(basename(__FILE__))
        ->setRelation('student_email')
        ->setMenuName('student_admin')
        ->setKeyColumns(array('snummer'))
        ->setRawNames(array('snummer', 'class_id'))
        ->setNameExpression("st_.snummer||' '||rtrim(achternaam,' ')||', '||rtrim(roepnaam,' ')||coalesce(' '||trim(tussenvoegsel),'')")
        ->setOrderList(array('achternaam', 'roepnaam'))
        ->setFormTemplate('templates/student_admin.html')
        ->setListRowTemplate(array('st_.snummer', 'minifoto', 'email1', 'pcn', 
            'hoofdgrp', 'sclass', 'cohort', 'gebdat', 'slb', 'studieplan'))
        ->setListQueryExtension("\n"
                . "   join minifoto fo on(st_.snummer=fo.snummer) \n"
                . "   left join student_class scn using (class_id) " )
        ->show();


