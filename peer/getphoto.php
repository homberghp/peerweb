<?php
require_once('validators.php');

$dir=validate($_REQUEST['d'], 'fotodir', 'fotos');
$foto=validate($_REQUEST['s'], 'snummer', '0');
$fotodir="{$fotobase}/{$dir}";
$fname="{$fotodir}/".allowedPhoto($peer_id,$foto);

$fp = @fopen($fname, 'r');
// send the right headers
header("Content-type: image/jpeg");
header("Pragma: public");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Length: " . filesize($fname));
header("Content-Disposition: inline; filename=\"{$foto}.jpg\"");

// dump the picture and stop the script
fpassthru($fp);
fclose($fp);
exit(0);
