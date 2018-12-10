<?php
requireCap(CAP_SYSTEM);
require_once('validators.php');
require_once('rubberstuff.php');
include_once('navigation2.php');
require_once('conffileeditor2.php');
// anticipate a save initiated by user.
$saveResult = ConfFileEditor::save();
$page_opening = "Rubber editor file ";
$page = new PageContainer();
$page->setTitle( $page_opening );
$nav = new Navigation( $tutor_navtable, basename( $PHP_SELF ), $page_opening );
$page->addBodyComponent( $nav );
if ( $saveResult != '' ) {
  $page->addBodyComponent( new Component($saveResult) );
}
if (isSet($_REQUEST['rubberEditFile'])) {
  $_SESSION['fileToEdit'] = $_REQUEST['rubberEditFile'];
}
$pp = array( );
$fileEditor = new ConfFileEditor( 'rubberreports.php');
$fileEditor->setDescription("Edit query, template or tex file");
$fileEditor->getWidgetForPage( $page );
$page->show();
?>