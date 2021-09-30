<?php
require_once('validators.php');

$dir=validate($_REQUEST['d'], 'fotodir', 'fotos');
$foto=validate($_REQUEST['s'], 'snummer', '0');
$fotodir="{$fotobase}/{$dir}";
if (! isset($peer_id) && isset($_SERVER['REMOTE_USER'])) {
   $peer_id=validate($_SERVER['REMOTE_USER'],'snummer',0);
}
$fname="{$fotodir}/".allowedPhoto($peer_id,$foto);

$fp = @fopen($fname, 'r');
//echo $fname;
//exit(0);
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
