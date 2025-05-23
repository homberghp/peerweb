<?php

requireCap(CAP_SYSTEM);
require_once('validators.php');
//require_once('rubberstuff.php');
require_once('navigation2.php');
require_once('conffileeditor2.php');
requireCap( CAP_SYSTEM );
// anticipate a save initiated by user.
$saveResult = ConfFileEditor::save();
$page_opening = "Rubber editor file ";
$page = new PageContainer();
$page->setTitle( $page_opening );
$nav = new Navigation( $navtable, basename( __FILE__ ), $page_opening );
$page->addBodyComponent( $nav );
if ( $saveResult != '' ) {
  $page->addBodyComponent( new Component($saveResult) );
}
if (isSet($_REQUEST['formEditFile'])) {
  $_SESSION['formFileToEdit'] = $_REQUEST['formEditFile'];
}
$_SESSION['formFileToEdit']='./../templates/editform/html';
$pp = array( );
$fileEditor = new ConfFileEditor( basename(__FILE__), '../templates/formedit.html');
$fileEditor->setDescription("Edit query, template or tex file");
$fileEditor->getWidgetForPage( $page );
$page->show();
?>