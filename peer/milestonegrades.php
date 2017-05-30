<?php
include_once('./peerlib/peerutils.php');
include_once('navigation2.php');
require_once './peerlib/simplequerytable.php';
require_once 'prjMilestoneSelector2.php';
require_once './peerlib/pgrowparser.php';
require_once './peerlib/SpreadSheetWriter.php';

requireCap(CAP_TUTOR);
// get group tables for a project
$afko = 'PRJ00';
$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
extract($_SESSION);

$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
extract($prjSel->getSelectedData());
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;

$filename = 'peerweb_grades_' . $afko . '-' . date('Ymd');

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Get the grades the students received per project/milestone. 
 */
//$prjm_id = 408;
// get the number of milestones, their names and weights
$sql = "select prj_id,prjm_id,milestone ,milestone_name,weight from prj_milestone where prj_id=$prj_id";
$resultSet = $dbConn->Execute($sql);
$gradeColumns = array();
if ($resultSet === 0) {
    die("cannot get result with<pre>" . $sql . "</pre><br/> reason " . $dbConn->ErrorMsg() . "\n");
} else {
  $milestone= $resultSet->fields['milestone'];
  $milestone_name= $resultSet->fields['milestone_name'];
  $weight = $resultSet->fields['weight'];
    $cols = "select snummer,grade ";
    $relName = 'mg' . $milestone;
    $gradeName = 'm' . $milestone . preg_replace('/\s+/', '_', $milestone_name);
    $cols = "select snummer ";
    $join = "\n\tprj_grp pg join all_prj_tutor pt on(pg.prjtg_id=pt.prjtg_id and pt.prj_id=$prj_id and pt.milestone=1)";
    //$resultSet->moveNext();
    while (!$resultSet->EOF) {
        extract($resultSet->fields, EXTR_PREFIX_ALL, 'T');
        $relName = 'mg' . $T_milestone;
        $gradeName = 'm' . $T_milestone . preg_replace('/\s+/', '_', $T_milestone_name);
        array_push($gradeColumns, $gradeName);
        $cols .= ",\n\t$relName.grade as $gradeName,$relName.multiplier as ${gradeName}_mul,coalesce($relName.grade,0)*${T_weight} as ${gradeName}_weight";
        $join .= "\n left join ( select snummer, grade, multiplier,weight from milestone_grade join prj_milestone"
                . " using(prjm_id)  \n\t\twhere prj_id=$T_prj_id and milestone=$T_milestone)\n\t\t$relName "
                . "using(snummer)";
        $resultSet->moveNext();
    }
    $subSelect = $cols . "\n from" . $join;
    //    $dbConn->log($subSelect);
}

$grades = "";
$con='';
foreach($gradeColumns as $grade) {
    
    $grades .= $con."subselect.${grade},subselect.${grade}_mul";
    $con="\n\t,";
}
$sql = "select snummer,achternaam,roepnaam,voorvoegsel, afko, year, \n"
        . "milestone,grp_num,\"alias\",tutor,\"role\",$grades,sw.final_grade \n"
        . "from prj_grp join all_prj_tutor using(prjtg_id)\n"
        . " join student using(snummer) \n"
        . " left join milestone_grade using(prjm_id,snummer)\n"
        . " left join student_role using (prjm_id,snummer)\n"
        . " left join project_roles using (prj_id,rolenum)\n"
        . " join ($subSelect) subselect using(snummer)\n"
        . " left join (select snummer,round(grade_weight_sum/w.weight_sum,1) \n"
        . " as final_grade from project_grade_weight_sum_product\n"
        . " cross join (select weight_sum from project_weight_sum where prj_id=$prj_id) w where prj_id=$prj_id ) sw using(snummer)\n"
        . "where prjm_id=$prjm_id order by grp_num,achternaam,roepnaam";

$pp=array();

$dbConn->log($sql);
$spreadSheetWriter = new SpreadSheetWriter($dbConn, $sql);
$title ="Results for all participants in project $afko $year milestone $milestone";
$spreadSheetWriter->setFilename($filename)
        ->setLinkUrl($server_url . $PHP_SELF )
        ->setTitle($title)
        ->setColorChangerColumn(7);
        //->setRowParser( new RowWithArraysParser());

$spreadSheetWriter->processRequest();
$pp['spreadSheetWidget'] = $spreadSheetWriter->getWidget();

$page = new PageContainer();
$page->setTitle('Get group tables');
$page_opening = "Group lists for project $afko $description <span style='font-size:8pt;'>prjm_id $prjm_id prj_id $prj_id milestone $milestone </span>";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$rainbow = new RainBow();
$pp['rtable'] = getQueryToTableChecked($dbConn, $sql, false, 7, $rainbow, -1, '', ''); 
$pp['selector'] = $prjSel->getSelector();
$pp['selectionDetails'] = $prjSel->getSelectionDetails();
$page->addBodyComponent($nav);
$page->addHtmlFragment('templates/milestonegrades.html', $pp);
$page->show();
?>
