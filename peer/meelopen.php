<?php
requireCap(CAP_RECRUITER);

/**
 * The simple table editor for the tutor
 * @author Pieter van den Hombergh
 * $Id: meelopen.php 1723 2014-01-03 08:34:59Z hom $
 */
require_once("ste.php");
$navTitle = "Meelopen " . $PHP_SELF . " on DB " . $db_name;
$page = new PageContainer();
$page->setTitle($navTitle);
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setFormAction($PHP_SELF)
        ->setRelation('meelopen')
        ->setMenuName('meelopen')
        ->setKeyColumns(array('meelopen_id'))
        ->setListRowTemplate(array('email', 'taal', 'straat', 'huisnr', 'postcode', 'plaats', 'land', 'telefoon'
            , 'participation', 'invitation', 'confirmed', 'vooropleiding'))
        ->setOrderList(array('achternaam', 'roepnaam'))
        ->setNameExpression("rtrim(achternaam||', '||roepnaam||coalesce(', '||tussenvoegsel,''))")
        ->setFormTemplate('templates/meelopen.html')
        ->show();

