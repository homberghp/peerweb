<?php
include_once('./peerlib/peerutils.php');
require_once('./peerlib/querytotable.php');
include_once('navigation2.inc');
include_once 'project_selector.php';
requireCap(CAP_TUTOR);
$milestones=1;
extract($_SESSION);
$year = date('Y');
$milestone_span=42*86400; // think of better default
if (date('m') < 07) {
    $year -=1;
}
if ( isSet($_POST['prj_id']) ) {
    $_SESSION['prj_id'] = $prj_id=$_POST['prj_id'];
}
if ( isSet($_POST['milestone']) ) {
    $_SESSION['milestone'] = $milestone = $_POST['milestone'];
 }
if (!isSet($_SESSION['prj_id'])) {
    // smart guess
    $sql = "select prj_id,afko,year,description from project\n".
	" where prj_id=(select max(prj_id) as prj_id from project)";
    $resultSet= $dbConn->Execute($sql);
    if ( $resultSet === false ) {
	die( "<br>Cannot get smart project data with $sql, cause".$dbConn->ErrorMsg()."<br>");
    }

    if ($resultSet->EOF) {
	$prj_id=0; 
    } else {
	extract($resultSet->fields);
    }
    $_SESSION['prj_id']=$prj_id;
 }
if (isSet($_SESSION['prj_id'])) {
    $sql = "select prj_id,afko,description,year,max(milestone) as milestones from ".
	"project p left join prj_milestone m using (prj_id) where prj_id=$prj_id group by prj_id,afko,description,year";
    $resultSet=$dbConn->Execute($sql);
    if ( $resultSet === false ) {
	die( "<br>Cannot get sequence next value with ".$dbConn->ErrorMsg()."<br>");
    }
    $afko= $resultSet->fields['afko'];
    $description= $resultSet->fields['description'];
    $year= $resultSet->fields['year'];
    if (isSet($resultSet->fields['milestones'])) {
	$milestones = $resultSet->fields['milestones'];
    } else $milestones = 0;
}
if (isSet($_POST['bsubmit']) && isSet($_POST['milestones']) ) {
    if ( $_POST['bsubmit'] == 'Update' && isSet($_POST['prj_id']) ) { 
	$milestones = $_POST['milestones'];
	// throw out those that are too many
	$sql = "delete from prj_milestone where prj_id=$prj_id and milestone > $milestones";
	$resultSet=$dbConn->Execute($sql);
	if ( $resultSet === false ) {
	    die( "<br>Cannot delete milestone values with ".$sql." reason ".$dbConn->ErrorMsg()."<br>");
	}
	// now get the max present and add to what's needed
	$sql = "select max(milestone) as milestone from prj_milestone where prj_id=$prj_id";
	$resultSet=$dbConn->Execute($sql);
	if ( $resultSet === false ) {
	    die( "<br>Cannot get max with ".$sql." reason ".$dbConn->ErrorMsg()."<br>");
	}
	if (isSet($resultSet->fields['milestone'])) {
	    $milestone = $resultSet->fields['milestone']+1; 
	} else 	$milestone =1; 
	$milestone_date=mktime()+$milestone_span;
	while ($milestone <= $milestones) {
	    $assessment_due=date('Y-m-d',$milestone_date);
	    $sql = "insert into prj_milestone (prj_id,milestone,prj_milestone_open,assessment_due) \n".
		" values( $prj_id,$milestone,false,'$assessment_due')";
	    $resultSet=$dbConn->Execute($sql);
	    if ( $resultSet === false ) {
		die( "<br>Cannot update milestone values with ".$sql." reason ".$dbConn->ErrorMsg()."<br>");
	    }
	    $milestone++;
	    $milestone_date +=$milestone_span;
	}
    }
    $_SESSION['milestone']=$milestones; // save last used value as default
 } else if ( isSet( $_POST['submitdue']) && isSet($_POST['assessment_due'])) {
    $sql= "begin work;\n";
    for ($i=0; $i < count($_POST['assessment_due']); $i++) {
	$assessment_due= $_POST['assessment_due'][$i];
	$mil=$i+1;
	$sql .="update prj_milestone set assessment_due='$assessment_due' where prj_id=$prj_id and milestone=$mil;\n";
    }
    $sql .="commit";
    $resultSet=$dbConn->Execute($sql);
    if ( $resultSet === false ) {
	echo( "<br>Cannot update milestone values with ".$sql." reason ".$dbConn->ErrorMsg()."<br>");
    }
 }

$prj_id= isSet($_SESSION['prj_id'])?$_SESSION['prj_id']:-1;
extract(getTutorOwnerData($dbConn,$prj_id));
$_SESSION['prj_id']=$prj_id;
$isTutorOwner= ($tutor==$tutor_code);
$page = new PageContainer();
$page->setTitle('Define the number of assessments (milestones) in the project.');
$page_opening="Define the number of assessments (milestones) in the project. <font style='font-size:6pt;'>prj_id $prj_id</font>\n";
$nav=new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$form1 = new HtmlContainer("<fieldset id='form1'><legend><b>Project milestones.</b></legend>");
$form1->addText("After you determined the number of milestones, select the due dates. (Defaults are 14 days from now).");

$form1Form= new HtmlContainer("<form id='project' method='post' name='project' action='$PHP_SELF'>");

// ."<!--<input type='submit' name='bsubmit' value='Get'>-->";
//if ($isTutorOwner) {
  $submit_button ="<button type='submit' name='bsubmit' value='Update'>Update</button>";
// } else {
//  $submit_button ='';
// }
$project_selector=getProjectSelector($dbConn, $peer_id,$prj_id);

$templatefile='templates/defmilestoneform1.html.inc';
$template_text= file_get_contents($templatefile, true);
if ($template_text === false ) {
  $form1Form->addText("<strong>cannot read template file $templatefile</strong>");
} else {  
  eval("\$text = \"$template_text\";");
  $form1Form->addText($text);
}
$form1->add($form1Form);
$page->addBodyComponent($nav);
$page->addBodyComponent($form1);

$form2= new HtmlContainer("<fieldset><legend>Due dates</legend>");
$form2->addText("After you determined the number of milestones, select the due dates. (Defaults are 14 days from now).");
$form2Form = new HtmlContainer("<form method='post' name='duedates' action='$PHP_SELF'>");

$sql = "select 'M'||milestone as name, assessment_due,\n".
    "  case when prj_milestone_open=true then  'open' else 'closed' end as open \n".
     " from prj_milestone where prj_id=$prj_id order by milestone";
$inputColumns=array( '1'=> array( 'type'=>'N','size'=>'12'));
ob_start(); // collect table data
// column '0' = M<milestone>
$inputColumns=array( //'0' => array( 'type' => 'T', 'size' => '4'),
		     '1' => array( 'type' => 'N', 'size' => '12'),
		     //'2' => array( 'type' => 'B', 'size' => '1', 'colname' => 'open' ),
		     );
queryToTableChecked2($dbConn,$sql,true,0,new RainBow(0x46B4B4,64,32,0),'open[]', array(),$inputColumns);
$form2Form->addText(ob_get_clean());
$form2Form->addText("<input type='hidden' name='prj_id' value='$prj_id' />\n".
		"<input type='submit' name='submitdue' value='Submit' />\n".
		"<input type='reset' name='reset' value='Reset' />");
$form2->add($form2Form);
$page->addBodyComponent($form2);
$page->addBodyComponent(new Component('<!-- db_name=$db_name $Id: defmilestone.php 1723 2014-01-03 08:34:59Z hom $ -->'));
$page->show();
?>