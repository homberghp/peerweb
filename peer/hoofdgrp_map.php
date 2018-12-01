<?php
requireCap(CAP_SYSTEM);

/**
 * The simple table editor for the tutor
 * @author Pieter van den Hombergh
 * $Id: tutor.php 1709 2013-12-08 07:56:27Z hom $
 */
require_once("ste.php");
$page = new PageContainer("Hoofdgrp mapper $db_name ");
$ste = new SimpleTableEditor( $dbConn ,$page);
$ste->setTitle( $title )
        ->setFormAction( $PHP_SELF )
        ->setRelation( 'hoofdgrp_map' )
        ->setMenuName( 'hoofdgrp_map' )
        ->setKeyColumns( array( '_id' ) )
        ->setNameExpression( "rtrim(opleiding)" )
        ->setListRowTemplate( array( 'instituutcode','hoofdgrp','lang','course') )
        ->setOrderList( array( 'opleiding' ) )
        ->setFormTemplate( 'templates/hoofdgrp_map.html' )
        ->show();
