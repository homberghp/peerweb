<?php

/**
 * The simple table editor for the tutor
 * @author Pieter van den Hombergh
 * $Id: class_cluster.php 1723 2014-01-03 08:34:59Z hom $
 */
include_once("ste.php");
$page = new PageContainer("Peerweb class clusters " . $PHP_SELF . " on DB " . $db_name);
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setFormAction($PHP_SELF)
        ->setRelation('class_cluster')
        ->setMenuName('class_cluster')
        ->setKeyColumns(array('class_cluster'))
        ->setNameExpression("rtrim(cluster_name)")
        ->setListRowTemplate(array('cluster_name', 'cluster_description'))
        ->setOrderList(array('sort_order'))
        ->setFormTemplate('templates/class_cluster.html')
        ->show();
?>
