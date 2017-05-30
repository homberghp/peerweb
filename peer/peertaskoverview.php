<?php
include_once('./peerlib/peerutils.php');
include_once('tutorhelper.php');
include_once('navigation2.php');
include_once 'navigation2.php';
require_once 'personalTaskList.php';
// get group tables for a project
$prj_id=0;
$prjm_id = 0;
$milestone=1;
$afko='PRJ00';
$description='';
extract($_SESSION);

$page = new PageContainer();
ob_start();
tutorHelper($dbConn,$isTutor);
$page->addBodyComponent(new Component(ob_get_clean()));
$page->setTitle('Overview of personal tasks');
$page_opening="Task overview for $roepnaam $voorvoegsel $achternaam ($snummer)";
$nav=new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$page->addBodyComponent($nav);
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
$task_table = personalTaskList($dbConn,$snummer);
$templatefile='templates/peertaskoverview.html';
$template_text= file_get_contents($templatefile, true);
$text='';
if ($template_text === false ) {
  $text="<strong>cannot read template file $templatefile</strong>";
} else {  
  eval("\$text = \"$template_text\";");
}

$page->addBodyComponent(new Component($text));
$page->show();

?>
