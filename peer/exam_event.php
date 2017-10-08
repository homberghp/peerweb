<?php

/**
 * The simple table editor for the exam_event
 * @author Pieter van den Hombergh
 * $Id: exam_event.php 1723 2014-01-03 08:34:59Z hom $
 */
require_once("ste.php");
$title="Exam_Event editor on DB {$db_name} ";
$page = new PageContainer($title);
$ste = new SimpleTableEditor( $dbConn ,$page);
$ste->setShowQuery( true )
        ->setTitle( $title )
        ->setFormAction( $PHP_SELF )
        ->setRelation( 'exam_event' )
        ->setMenuName( 'exam_event' )
        ->setKeyColumns( array( 'exam_event_id' ) )
        ->setSubRel( 'module_part' )
        ->setSubRelJoinColumns( array( 'module_part_id' => 'module_part_id' ) )
        ->setNameExpression( "progress_code||':'||coalesce(part_description,'no description')" )
        ->setListRowTemplate( array(  'exam_date', 'examiner' ) )
        ->setFormTemplate( 'templates/exam_event.php' )
        ->show();
?>
