<?php
requireCap(CAP_SYSTEM);

/**
 * The simple table editor for the tutor
 * @author Pieter van den Hombergh
 * $Id: tutor.php 1709 2013-12-08 07:56:27Z hom $
 */
require_once("ste.php");
$page = new PageContainer("Nat mapper $db_name ");
$ste = new SimpleTableEditor( $dbConn ,$page);
$ste->setTitle( "Map nationalities" )
        ->setFormAction( $PHP_SELF )
        ->setRelation( 'nat_mapper' )
        ->setMenuName( 'nat_mapper' )
        ->setKeyColumns( array( 'id' ) )
        ->setNameExpression( "rtrim(nation_omschr)" )
        ->setListRowTemplate( array( 'nation_omschr','nationaliteit','id') )
        ->setOrderList( array( 'nation_omschr' ) )
        ->setFormTemplate( 'templates/nat_mapper.html' )
        ->show();
