<?php
  /** process the incoming prjm_id request from a selector */
if (isSet($_REQUEST['prjm_id'])) {
  $prjm_id = validate($_REQUEST['prjm_id'],'integer',$prjm_id);
 } else if (!isset($_SESSION['prjm_id'])){
    $sql="select prj_id,min(prjm_id) as prjm_id,milestone from prj_milestone where prj_id=(select max(prj_id) as prj_id from project) group by prj_id";
    $resultSet=$dbConn->Execute($sql);
    if ( $resultSet === false ) {
	echo( "<br>Cannot get prj tutors with <pre>\"".$sql .'"</pre>, cause '.$dbConn->ErrorMsg()."<br>");
	stacktrace(1);
	die();
    }
    extract($resultSet->fields);
 }
if ($_SESSION['prjm_id'] != $prjm_id ) {
    $sql="select afko,description, year,prjm_id, prj_id,milestone from prj_milestone natural join project\n"
      ." where prjm_id=$prjm_id "; 
    $resultSet=$dbConn->Execute($sql);
    if ( $resultSet === false ) {
	echo( "<br>Cannot get prj_milestone data with <pre>\"".$sql .'"</pre>, cause '.$dbConn->ErrorMsg()."<br>");
	stacktrace(1);
	die();
    }
    extract($resultSet->fields);
}
$_SESSION['prjm_id']=$prjm_id;
// below are temp till complete use new normalisation
$_SESSION['prj_id']= $prj_id;
$_SESSION['milestone']= $milestone;
?>