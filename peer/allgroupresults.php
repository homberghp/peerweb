<?php
requireCap(CAP_TUTOR);
require_once('navigation2.php');
require_once 'simplequerytable.php';
require_once 'prjMilestoneSelector2.php';
require_once 'pgrowparser.php';
require_once 'SpreadSheetWriter.php';

requireCap( CAP_TUTOR );
// get group tables for a project
$afko = 'PRJ00';
$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
extract( $_SESSION );

$prjSel = new PrjMilestoneSelector2( $dbConn, $peer_id, $prjm_id );
$prjSel->setWhere( "has_assessment = true" );
extract( $prjSel->getSelectedData() );
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;

$filename = 'peerassessment_grades_' . $afko . '-' . date( 'Ymd' );

/**
 * Get the grades the students received per project/milestone. 
 */
//$prjm_id = 408;
// get the number of milestones, their names and weights
$sql = "select prj_id,prjm_id,milestone,milestone_name,weight from prj_milestone where prj_id=$prj_id";
$resultSet = $dbConn->Execute( $sql );
$gradeColumns = array( );
if ( $resultSet === 0 ) {
  die( "cannot get result with<pre>" . $sql . "</pre><br/> reason " . $dbConn->ErrorMsg() . "\n" );
}
$pp=array();

$criteria = criteriaShortAsArray( getCriteria( $prjm_id ), 'en' );
$criteria[] = 'Overall';
$sql = "select prjtg_id,tutor_grade from prj_tutor where prjm_id=$prjm_id";
$sql2 = "";
$resultSet = $dbConn->Execute( $sql );
$con = " ";
while ( !$resultSet->EOF ) {
  extract( $resultSet->fields );
  $sql2 .= $con . " select * from assessment_grade_set($prjtg_id,$tutor_grade)\n";
  $con = "union\n\t";

  $resultSet->moveNext();
}
//echo "<pre>$sql2</pre>\n";
$sql = "select s.snummer,achternaam,roepnaam,tussenvoegsel,gebdat,grp_num,coalesce(mg.grade,tutor_grade) as group_grade,"
        . "ags.grade,\n"
        . "ags.multiplier,ags.multiplier[array_upper(ags.multiplier,1)] as final_mult,"
        . "ags.grade[array_upper(ags.grade,1)] as peers_grade,tutor,mg.grade as final_tutor_grade,operator,date_trunc('second',ts) as trans_time\n"
        . " from  \n"
        . " prj_grp \n "
        . " join all_prj_tutor using(prjtg_id) \n"
        . " join student_email s using(snummer)\n "
        . "left join ($sql2) ags using(prjtg_id,snummer)\n"
        . " left join milestone_grade mg using(snummer,prjm_id) left join transaction using(trans_id)\n"
        . " where prjm_id=$prjm_id order by grp_num,achternaam";

$spreadSheetWriter = new SpreadSheetWriter( $dbConn, $sql );
$title = "Results for all peer assessment groups in project $afko $year milestone $milestone";
$spreadSheetWriter->setFilename( $filename )
        ->setLinkUrl( $root_url . basename(__FILE__) )
        ->setTitle( $title )
        ->setColorChangerColumn( 5 )
        ->setRowParser( new RowWithArraysParser() );
$spreadSheetWriter->processRequest();
$pp['spreadSheetWidget'] = $spreadSheetWriter->getWidget();
$pp['projectSelector'] = $prjSel->getSelector();
$pp['selectionDetails'] = $prjSel->getSelectionDetails();

$page = new PageContainer();
$page->setTitle( 'All group tables' );
$page_opening = "Group peer assessment results for all groups of project $afko $description "
        . "<span style='font-size:8pt;'>prjm_id $prjm_id prj_id $prj_id milestone $milestone </span>";
$nav = new Navigation( $tutor_navtable, basename( __FILE__ ), $page_opening );
$nav->setInterestMap( $tabInterestCount );

$rainbow = new RainBow();
$pp['rtable'] = getQueryToTableChecked( $dbConn, $sql, false, 5, $rainbow, -1, '', '' );
$page->addBodyComponent( $nav );
$page->addHtmlFragment('templates/allgroupresult.html', $pp);
$page->show();
?>
