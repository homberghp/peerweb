<?php
  // passthrough file
  //
requireCap(CAP_SYSTEM);

require_once('rubberstuff.php');
function _header($s) {
  echo $s;
}
if ($_REQUEST['rubberproduct']) { 
  $rubberproduct = $_REQUEST['rubberproduct'];
  $filename = "$rubberbase/$rubberproduct"; 
  //  $mimetype = finfo_file(FILEINFO_MIME_TYPE,$filename);
  $extension= end(explode('.',$filename));
  $mime_type = 'text/plain';
  switch ($extension) {
  case 'pdf' : $mime_type='application/pdf'; break;
    break;
  }
  $browserfile=end(explode('/',$filename));
  if (is_file($filename)) {

    $fp = @fopen($filename, 'r');
    if ($fp != false) {
      // send the right headers

      header("Content-type: $mime_type");
      header("Pragma: public");
      header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
      header("Content-Length: " . filesize($filename));
      header("Content-Disposition: attachment; filename=\"$browserfile\"");

      // dump the picture and stop the script
      fpassthru($fp);
      fclose($fp);
    }
    // log download
    //$dbConn->Execute("insert into downloaded (snummer,upload_id) values ($peer_id,$doc_id)");
    exit ;
 }

}
?>