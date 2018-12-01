<?php
requireCap(CAP_SYSTEM);
/**
 * The simple table editor for the tutor
 * @author Pieter van den Hombergh
 * $Id: tutor.php 1709 2013-12-08 07:56:27Z hom $
 */
require_once("ste.php");
$page = new PageContainer("Country table $db_name ");
$ste = new SimpleTableEditor( $dbConn ,$page);
$ste->setTitle( "country codes" )
        ->setFormAction( $PHP_SELF )
        ->setRelation( 'iso3166' )
        ->setMenuName( 'iso3166' )
        ->setKeyColumns( array( 'number' ) )
        ->setNameExpression( "rtrim(country)" )
        ->setListRowTemplate( array( 'country','a2','a3','number','country_by_lang','land_nl'))
        ->setOrderList( array( 'a3' ) )
        ->setFormTemplate( 'templates/iso3166.html' )
        ->show();
?>
