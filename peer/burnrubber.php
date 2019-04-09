<?php
requireCap(CAP_SYSTEM);

require_once 'rubberstuff.php';
$filename = validate($_REQUEST['rubberproduct'],'filename','x.txt');
$filename = "$rubberbase/".preg_replace('/^(\.\/)+/','',$filename).'*';
//echo "/bin/rm -f $filename";
@`/bin/rm -f $filename`;
if (isset($_SERVER['HTTP_REFERER'])) {
  header('Location: '.$_SERVER['HTTP_REFERER']);
 }
