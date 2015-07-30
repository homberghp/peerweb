<?php
require_once 'component.inc';
require_once('./peerlib/peerutils.inc');
include_once('tutorhelper.inc');
require_once'navigation.inc';

$pp=array();
if (file_exists('fotos/' . $judge . '.jpg')) {
    $pp['foto'] = 'fotos/' . $judge . '.jpg';
} else {
    $pp['foto'] = 'fotos/0.jpg';
}
if (file_exists('mfotos/' . $judge . '.jpg')) {
    $pp['mfoto'] = 'mfotos/' . $judge . '.jpg';
} else {
    $pp['mfoto'] = 'mfotos/0.jpg';
}

$title="My peerweb face";
$page = new PageContainer( $title );
ob_start();
tutorHelper($dbConn, $isTutor);
$page->addBodyComponent(new Component(ob_get_clean()));
$nav = new Navigation(array(),$page,$title);
$page->addBodyComponent($nav);
$page->addHtmlFragment('templates/myface.html',$pp);
$page->show();
?>
