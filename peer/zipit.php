<?php
requireCap(CAP_TUTOR);
require_once'validators.php';
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
if ( isSet( $_REQUEST['prjm_id'] ) ) {
  $prjm_id = validate($_REQUEST['prjm_id'],'integer','0');
  $_SESSION['prjm_id'] = validate($prjm_id,'integer','0');
}

$sql = "select afko,year,prjm_id,doctype,milestone,tutor,grp_num,rel_file_path,doc_type_desc,author,author_name,archfilename\n"
        . "from upload_archive_names where prjm_id=$prjm_id";
if (isSet( $_REQUEST['doctype'] ) ){
    $doctype=validate($_REQUEST['doctype'],'integer','0');
    $sql .= " and doctype=$doctype";
}
$resultSet = $dbConn->Execute( $sql );
$fdate=date('Y-m-d');
$dropauthor=false;
if ( isSet( $_REQUEST['dropauthor'] ) ) {
  $dropauthor=true;
}
if ( $resultSet === false ) {
  die( "Query failed with" . $dbConn->ErrorMsg() );
} else if ( !$resultSet->EOF ) {
  $zipfilename = tempnam('/tmp','zip');;
  $zip = new ZipArchive();
  $res = $zip->open( $zipfilename, ZipArchive::CREATE );
  $filename='';
  if ( $res === TRUE ) {
    while ( !$resultSet->EOF ) {
      extract( $resultSet->fields );
      if ($filename =='') {
	$filename=$afko.'-'.$year.'M'.$milestone.'-'.(isSet($doctype)?($doc_type_desc.'-'):'').$fdate.'.zip';
      }
      $srcFile =  $upload_path_prefix . '/'  . $rel_file_path;
      $archFilePath = $archfilename.'/' . ($dropauthor?'':($author . '/')) . basename( $rel_file_path );
      $zip->addFile( $srcFile, $archFilePath );
      $resultSet->moveNext();
    }
    $zip->close();
    $fp = fopen( $zipfilename,'r');
    header("Content-type: application/zip");
    header("Pragma: public");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Length: " . filesize($zipfilename));
    header("Content-Disposition: attachment; filename=\"$filename\"");
    fpassthru( $fp );
    fclose( $fp );
  }
}
?>
