<?php

include_once('./peerlib/peerutils.inc');
require_once('./peerlib/validators.inc');
require_once('rubberstuff.php');
include_once('navigation2.inc');
require_once('./peerlib/conffileeditor2.php');
requireCap( CAP_TUTOR );
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