<?php
requireCap(CAP_SYSTEM);
require_once('validators.php');
require_once('navigation2.php');
require_once 'prjMilestoneSelector2.php';
require_once 'TableBuilder.class.php';
require_once 'TaskRowFactory.class.php';
require_once 'TemplateWith.php';

requireScribeCap($peer_id);

// get group tables for a project
$prj_id=0;
$prjm_id = 0;
$milestone=1;
$afko='PRJ00';
$description='';
extract($_SESSION);
$prjSel= new PrjMilestoneSelector2($dbConn,$peer_id,$prjm_id);
$prjSel->setJoin('milestone_grp using (prj_id,milestone) natural join activity_project ');
$prjSel->setJoin(' all_project_scribe using(prj_id) ');
$prjSel->setWhere(' prj_id in (select prj_id from project_task) and '.$peer_id.'=scribe');
extract($prjSel->getSelectedData());
$_SESSION['prj_id']=$prj_id;
$_SESSION['prjm_id']=$prjm_id;
$_SESSION['milestone']=$milestone;

$filename='presencelist_'.$afko.'-'.date('Ymd').'.csv';

$csvout='N';
$csvout_checked='';
if (isSet($_REQUEST['csvout'])) { 
    $csvout=$_REQUEST['csvout'] ;
    $csvout_checked = ($csvout=='Y')?'checked':'';
 }

//pagehead2("Presence list to $afko $year $description");//,$scripts);

$prj_id_selector=$prjSel->getSelector();
$selection_details=$prjSel->getSelectionDetails();
$sql="select st.snummer,roepnaam||coalesce(' '||tussenvoegsel||' ',' ')||achternaam as name,"
  ." '#'||task_number||': '||apt.afko||': '||apt.description as checktitle,\n"
  ." pt.name as task_name,\n"
  ." coalesce(grade::text,mark) as check, ptc.comment as title,photo,grp_num \n"
  ." from prj_grp join all_prj_tutor apt using(prjtg_id)\n"
  ." natural join student_email st \n"
  ." join portrait tp using (snummer) \n"
  ." join project_task pt using(prj_id)\n"
  ." left join project_task_completed_latest ptc using(snummer,task_id)\n"
  ." where prj_id=$prj_id \n"
  ." order by grp_num,achternaam,roepnaam, task_number\n";

$dbConn->log($sql); 
$page = new PageContainer();
include 'js/balloonscript.php';

$page->setTitle('Overview of tasks completed');
$page->addHeadComponent( new Component("<style type='text/css'>
    *.notered { 
	background-image:url('images/redNote.png');
    }
    *.notegreen { 
	background-image:url('images/greenNote.png');
    }
    *.noteblue { 
	background-image:url('images/blueNote.png');
    }
 </style>"));
$page_opening="Task completed list for project $afko $description prj_id $prj_id";
$nav=new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$page->addBodyComponent($nav);
$tableBuilder= new TableBuilder($dbConn,new TaskRowFactory());
$task_table = $tableBuilder->getTable($sql,'snummer');

$templatefile='templates/taskoverview.html';
$template_text= file_get_contents($templatefile, true);
$text='';
if ($template_text === false ) {
  $text="<strong>cannot read template file $templatefile</strong>";
} else {  
  $text=templateWith($template_text, get_defined_vars());
}

$page->addBodyComponent(new Component($text));
$page->addBodyComponent(new Component('<!-- db_name='.$db_name.'-->'));
$page->show();

?>
