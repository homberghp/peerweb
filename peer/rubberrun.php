<?php
requireCap(CAP_SYSTEM);
require_once 'rubberstuff.php';
$target = $_REQUEST['project'];
$processor = "$rubberbase/scripts/run";
//echo "$processor $target";
@`$processor $target`;
if (isset($_SERVER['HTTP_REFERER'])) {
  header('Location: '.$_SERVER['HTTP_REFERER']);
 }
