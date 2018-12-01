<?php

/* $Id: studentgroupresult.php 1825 2014-12-27 14:57:05Z hom $ */
requireCap(CAP_SYSTEM);

include_once('tutorhelper.php');
include_once 'navigation2.php';
require_once 'groupresult3.php';
require_once 'studentPrjMilestoneSelector.php';
$prjm_id = 0;
$pp = array();

$pp[ 'productgrade' ] = $productgrade = 7;
extract( $_SESSION );
ob_start();
tutorHelper( $dbConn, $isTutor );
$tutor_Helper = ob_get_clean();

$prjSel = new StudentMilestoneSelector( $dbConn, $judge, $prjtg_id );
$prjSel->setExtraConstraint( " and prjtg_id in (select distinct prjtg_id from assessment) and "
        . " (pr.capabilities &" . CAP_READ_PEER_ASSESSMENT_DATA . ") <> 0" );
$prjSel->setEmptySelectorResult( "<h1>There are no projects of which you may view the peer results</h1>" );
extract( $prjSel->getSelectedData() );
$_SESSION[ 'prjm_id' ] = $prjm_id;
$_SESSION[ 'prjtg_id' ] = $prjtg_id;
$studentMayRead = hasStudentCap( $snummer, CAP_READ_PEER_ASSESSMENT_DATA, $prjm_id );
$pp[ 'prjList' ] = $prjList = $prjSel->getWidget();


$page = new PageContainer();
// now test if student is allowed

if ( !$studentMayRead ) {
    $page_opening = 'You cannot view the results for this project and group';
    $page->setTitle( $page_opening );
    $page->addBodyComponent( new Component( $tutor_Helper ) );
    $nav = new Navigation( $tutor_navtable, basename( $PHP_SELF ), $page_opening );
    $page->addBodyComponent( $nav );
    $page->addHtmlFragment( 'templates/studentgroupresult_noaccess.html', $pp );

    $page->show();
    exit( 0 );
}
if ( isSet( $_REQUEST[ 'productgrade' ] ) ) {
    $tmpnum = preg_replace( '/,/', '.', $_REQUEST[ 'productgrade' ] );
    if ( preg_match( "/^\d{1,2}(\.?\d*)?$/", $tmpnum ) ) {
        $productgrade = $tmpnum;
    }
}
$page_opening = "Group assessment results viewable by $roepnaam $tussenvoegsel $achternaam ($snummer) for <i>average group grade</i> $productgrade";
$page->setTitle( 'Results for all students in a group' );
$nav = new Navigation( $tutor_navtable, basename( $PHP_SELF ), $page_opening );
$page->addBodyComponent( new Component( $tutor_Helper ) );
$page->addBodyComponent( $nav );
$criteria = getCriteria( $prjm_id );
$sql = "select distinct roepnaam||coalesce(' '||tussenvoegsel,'')||' '||achternaam as naam,achternaam,prjtg_id\n" .
        "from student join judge_notready using(snummer)\n" .
        // "join prj_tutor using(prjtg_id)\n" .
        "where prjtg_id=$prjtg_id order by achternaam,naam";
$resultSet = $dbConn->Execute( $sql );
if ( $resultSet === false ) {
    echo ('Error getting judge not ready with <pre>' . $dbConn->ErrorMsg() . ' with<br/> ' . $sql . "</pre>\n");
    stacktrace( 1 );
}
$lazyjudges = '<table>';
$lazycount = $resultSet->rowCount();
if ( $lazycount > 0 ) {
    while ( !$resultSet->EOF ) {
        $lazyjudges.="\t<tr><td>" . $resultSet->fields[ 'naam' ] . "</td></tr>\n";
        $resultSet->moveNext();
    }
    $lazyjudges .= "\n<table>\n";
}
array_push( $criteria, array( 'nl_short' => 'Overall',
    'de_short' => 'Overall',
    'nl' => 'Eindcijfer',
    'de' => 'Endnote' ) );
$overall_criterium = 99; //count($criteria);

$sql = "select * from project join prj_milestone using(prj_id) join prj_tutor using(prjm_id) join prj_grp using(prjtg_id)  left join grp_alias using(prjtg_id) \n" .
        "where prj_id=$prj_id and milestone=$milestone and snummer=$judge";
$resultSet = $dbConn->Execute( $sql );
if ( $resultSet === false ) {
    $dbconn->log( 'error getting project data with <strong><pre>' . $sql . '</pre></strong> reason : ' .
            $dbConn->ErrorMsg() . '<BR>' );
} else if ( !$resultSet->EOF ) {
    $pp = array_merge( $resultSet->fields );
}
$rainbow = new RainBow();

$pp[ 'criteriaList' ] = getCriteriaList( $criteria, $lang, $rainbow );
$pp[ 'prjList' ] = $prjSel->getWidget();
$pp[ 'productgrade' ] = $productgrade;


if ( $lazycount == 0 ) {
    $pp[ 'viewresult' ] = getGroupResultTable( $dbConn, $prjtg_id, $overall_criterium, $productgrade, true, $criteria, $lang, $rainbow, false,false );
    $templatefile = 'templates/studentgroupresult.html';
} else {
    $pp[ 'viewresult' ] = $lazyjudges;
    $templatefile = 'templates/studentgroupresult_noresult.html';
}
$page->addHtmlFragment( $templatefile, $pp );
$page->show();
?>
