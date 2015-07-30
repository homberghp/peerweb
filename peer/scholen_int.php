<?php

/**
 * The simple table editor for the tutor
 * @author Pieter van den Hombergh
 * $Id: scholen_int.php 1723 2014-01-03 08:34:59Z hom $
 */
include_once("ste.php");
requireCap(CAP_RECRUITER);

$page = new PageContainer("Peerweb scholen internationaal" . $PHP_SELF . " on DB " . $db_name);
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setShowQuery(true)
        ->setFormAction($PHP_SELF)
        ->setRelation('scholen_int')
        ->setMenuName('scholen_int')
        ->setKeyColumns(array('scholen_int_id'))
        ->setNameExpression("rtrim(naam_volledig)")
        ->setListRowTemplate(array('naam_plaats_vest', 'naam_straat_vest', 'nr_huis_vest', 'postcode_vest'))
        ->setOrderList(array('naam_volledig', 'naam_plaats_vest'))
        ->setFormTemplate('templates/scholen_int.html')
        ->show()
?>
