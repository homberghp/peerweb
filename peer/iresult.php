<?php
/* $Id: iresult.php 1825 2014-12-27 14:57:05Z hom $ */
//session_start();
include_once('peerutils.php');
include_once('tutorhelper.php');
include_once 'navigation2.php';
require_once 'studentPrjMilestoneSelector.php';
$groupgrade = 7;
// some defaults to prevent script faults
$afko = 'NOP';
$description = 'No project defined';
$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
$grp_num = 1;
$prjtg_id = 1;
extract( $_SESSION );
$judge = $snummer;
$prjSel = new StudentMilestoneSelector( $dbConn, $judge, $prjtg_id );
$prjSel->setExtraConstraint( " and prjtg_id in (select distinct prjtg_id from assessment) " );
extract( $prjSel->getSelectedData() );
$_SESSION['prjtg_id'] = $prjtg_id;
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;
$_SESSION['grp_num'] = $grp_num;

if ( isSet( $_REQUEST['groupgrade'] ) ) {
    $tmpnum = $_REQUEST['groupgrade'];
    if ( preg_match( "/^\d{1,2}(\.?\d+)?$/", $tmpnum ) ) {
        $_SESSION['groupgrade'] = $groupgrade = $_REQUEST['groupgrade'];
    }
}
$sql = "SELECT roepnaam, tussenvoegsel,achternaam,coalesce(lang,'EN') as lang \n"
        . "FROM student WHERE snummer=$snummer";
$resultSet = $dbConn->Execute( $sql );
if ( $resultSet === false ) {
    die( 'Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql );
}
extract( $resultSet->fields );
$lang = strtolower( $lang );
$selectCrit = "AND snummer=$snummer";
if ( $isTutor ) {
    $selectCrit = '';
}
$pp=array();
// first test if this student participates in any assessment;
$sql = "select count(*) as assessment_count from assessment where contestant=$snummer";
$resultSet = $dbConn->Execute( $sql );
if ( $resultSet === false ) {
    echo ("Cannot get assessment data with <pre>$sql</pre> Cause: " . $dbConn->ErrorMsg() . "\n");
}
extract( $resultSet->fields );
if ( $assessment_count != 0 ) {
    $pp['prjList'] = $prjSel->getWidget();
} else {
    $pp['prjList'] = "<h1>Sorry, you are not enlisted for any assessment</h1>";
}
$page_opening = "Participant $roepnaam $tussenvoegsel $achternaam ($snummer)";
$page = new PageContainer();
$page->setTitle( 'Individual result' );
$nav = new Navigation( $tutor_navtable, basename( $PHP_SELF ), $page_opening );
$nav->setInterestMap( $tabInterestCount );

$nav->addLeftNavText( file_get_contents( 'news.html' ) );
ob_start();
tutorHelper( $dbConn, $isTutor );
$page->addBodyComponent( new Component( ob_get_clean() ) );
$page->addBodyComponent( $nav );
$lazyCount = 0;
$lazyjudges = '';
$sqlx = "select distinct roepnaam||coalesce(' '||tussenvoegsel,'')||' '||achternaam as naam,achternaam,prjtg_id\n" .
        "from student join judge_notready using(snummer)\n" .
        "join prj_tutor using(prjtg_id)\n" .
        "where prjtg_id=$prjtg_id order by achternaam,naam";
$resultSet = $dbConn->Execute( $sqlx );
if ( $resultSet === false ) {
    echo ('Error getting judge not ready with <pre>' . $dbConn->ErrorMsg() . ' with<br/> ' . $sqlx . "</pre>\n");
    stacktrace( 1 );
} else 
while ( !$resultSet->EOF ) {
    $lazyCount++;
    $lazyjudges.='<tr><td>' . $resultSet->fields['naam'] . '</td></tr>';
    $resultSet->moveNext();
}
$sql = "select '('||snummer||')' as snummer,roepnaam||' '||coalesce(tussenvoegsel,'')||' '||achternaam as name,\n" .
        " student_class.sclass as class\n" .
        " from student join prj_grp using(snummer) \n" .
        "join student_class using (class_id)\n" .
        "join all_prj_tutor using(prjtg_id)\n" .
        "where prjtg_id=$prjtg_id \n" .
        "order by class,achternaam,roepnaam";
$resultSet = $dbConn->Execute( $sql );
if ( $resultSet === false ) {
    die( 'Error getting judge fellows ' . $dbConn->ErrorMsg() . ' with ' . $sql );
}
$pp['fellowTable'] = getQueryToTableChecked( $dbConn, $sql, true, 2,
        new RainBow( 0x46B4B4, 64, 32, 0 ), 7, '', 0 );
$pp['formOrNop']='';
if ( $lazyCount > 0 ) {
    $pp['formOrNop']= "<br/>" . $langmap['niet zover'][$lang] . ": \n<table>" . $lazyjudges . "</table>\n";
} else {
    $rainbow = new RainBow( STARTCOLOR, COLORINCREMENT_RED, COLORINCREMENT_GREEN, COLORINCREMENT_BLUE );
    $pp['formOrNop']= "<form action='$PHP_SELF' name='recalc' method='get'>\n".
    getIndividualResultTable( $dbConn, $lang, $prjtg_id, $snummer, $groupgrade,
            $rainbow )."\n</form>\n"
            ."<table><tr><td>"
            .$langmap['columnexplain'][$lang]
            ."</td><td>"
            .$langmap['columnnorights'][$lang]
            ."</td></tr></table>\n";
}
$page->addHtmlFragment( 'templates/iresult.html' , $pp );

$page->show();
?>
