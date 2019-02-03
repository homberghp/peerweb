<?php
requireCap(CAP_SYSTEM);
require_once('navigation2.php');
require_once 'simplequerytable.php'; 
require_once 'prjMilestoneSelector2.php';
require_once'studentpicker.php';
require_once 'TemplateWith.php';
//$dbConn->setSqlAutoLog( $db_name <> 'peer' );
requireCap(CAP_TUTOR);
$prjm_id = 0;
$prj_id=1;
$milestone=1;
$newsnummer=1;
extract($_SESSION);

$prjSel=new PrjMilestoneSelector2($dbConn,$peer_id,$prjm_id);
extract($prjSel->getSelectedData());
$_SESSION['prj_id']=$prj_id;
$_SESSION['prjm_id']=$prjm_id;
$_SESSION['milestone']=$milestone;

// unknown project?

$isTutorOwner=checkTutorOwner($dbConn,$prj_id, $peer_id); // check if this is tutor_owner of this project

$prjm_id_selector=$prjSel->getSimpleForm();
$mail_button="&nbsp;";


$isAdmin=hasCap(CAP_SYSTEM)?'true':'false';
$studentPicker= new StudentPicker($dbConn,$newsnummer,'Search and select participant to add.' );
$newsnummer=$studentPicker->processRequest();

if ( isSet($_REQUEST['baccept']) && $newsnummer != 0 ) {
    // try to insert this snummer into max prj_grp 
    $sql= "insert into project_scribe (prj_id,scribe) values($prj_id,$newsnummer)\n";
    $dbConn->Execute($sql);
    $dbConn->log($sql);
    //    $dbConn->log($dbConn->ErrorMsg());
 }

if ( isSet($_REQUEST['bdelete']) && $newsnummer != 0 ) {
    // try to insert this snummer into max prj_grp 
    $sql= "delete from project_scribe where scribe=$newsnummer and prj_id=$prj_id\n";
    $dbConn->Execute($sql);
    $dbConn->log($sql);
    //    $dbConn->log($dbConn->ErrorMsg());
 }

$page= new PageContainer();
$page_opening="Add project scribe".
    "<span style='font-size:8pt;'>prj_id $prj_id milestone $milestone prjm_id $prjm_id</span>";
$page->setTitle('Add a project scribe');
$nav=new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$page->addBodyComponent($nav);
$templatefile='templates/addscribe.html';
$template_text= file_get_contents($templatefile, true);
$studentPicker->setPresentQuery("select scribe as snummer from project_scribe where prj_id=$prj_id");
$student_picker_text=$studentPicker->getPicker();
$scribeQuery="select snummer,achternaam,roepnaam,tussenvoegsel from student_email\n"
  ."where snummer in (select scribe from project_scribe where prj_id=$prj_id) order by achternaam,roepnaam";
$scribeTable=simpleTableString($dbConn,$scribeQuery,
			       "<table summary='students found' border='1' style='border-collapse:collapse'>");
if ($template_text === false ) {
  $page->addBodyComponent( new Component("<strong>cannot read template file $templatefile</strong>"));
} else {  
  $page->addBodyComponent(new Component(templateWith($template_text, get_defined_vars())));
}
$page->show();
?>
