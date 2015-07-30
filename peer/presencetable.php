<?php
require_once('./peerlib/peerutils.inc');
function checkTable($dbConn,$query,$rowTriggerColumn,$headColumn,$checkcolumn,$notecolumn,$tabledef="<table summary='simple table' border='1' style='border-collapse:collapse'>") {
  $result ='';
  $head="<tr>\n";
  $triggerval=''; 
  global $ADODB_FETCH_MODE;
  $ADODB_FETCH_MODE = ADODB_FETCH_NUM;
  $coltypes=array();
  $colgroupdef='<colgroup>'."\n";
  $columnNames = array();
  $rowcounter=0;
  $resultSet = $dbConn->Execute( $query );
  if ($resultSet === false) {
      echo   "<pre>Cannot read table data with \n\t".$query." \n\treason \n\t".$dbConn->ErrorMsg()."at\n";
      stacktrace(1);
      echo  "</pre>";
  }
  $colcount=$resultSet->FieldCount()-1;
  for ($i = 0; $i < $colcount;$i++ ) {
    $field = $resultSet->FetchField($i);
    $columnNames[$i] = $field->name;
    if ($i != $headColumn && $i != $checkcolumn) {
      $head .=  "\t<th class='tabledata head' style='text-algin:left;'>".niceName($field->name)."</th>\n";
      $colgroupdef .="\t<col/>\n";
    }
  }
  $sessioncount=0;
  $present=0;
  while(!$resultSet->EOF ) {
    if ($triggerval != $resultSet->fields[$rowTriggerColumn]) {
      $rowcounter++;
      if ($result != '' ) {
	$result .= "<th align='right' >". round(100*$present/$sessioncount,0)."%</th></tr>\n";
	$present=0;
	//$result .= "</tr><!-- new row -->\n";
      }
      $result .= "<tr>\n";
      $colcount=$resultSet->FieldCount()-1;
      // get row head columns
      for ($i = 0; $i < $colcount;$i++ ) {
	if ($i != $headColumn && $i != $checkcolumn) {
	  $result .= "\t".'<td>';
	  if (isSet($resultSet->fields[$i])) {
	    $cell=trim($resultSet->fields[$i]);
	  } else {
	    $cell ='&nbsp;';
	  }
	  $result .= $cell;
	  $result .= "</td>\n";
	}
      }
    }
    $triggerval = $resultSet->fields[$rowTriggerColumn];
    if ($rowcounter ==1 ) { 
      $sessioncount++;
      $head .="\t<th title='".$resultSet->fields[$headColumn]."'>$sessioncount</th>\n";
            $colgroupdef .="\t<col/>\n";
    }
    
    if (isSet($resultSet->fields[$notecolumn])) {
      $note = " title='".$resultSet->fields[$notecolumn]."' class='abs' ";
    } else {
      $note='';
    }
    $result .= "\t<td$note>".$resultSet->fields[$checkcolumn]."</td>\n";
    if (isSet($resultSet->fields[$checkcolumn])) {
      $present += ($resultSet->fields[$checkcolumn] == 'P')?1:0;
    }
    //    $result .= '<td>'.$resultSet->fields[1]."</td>\n";
    $resultSet->MoveNext();
  }
  $result .= "<th align='right' >". round(100*$present/$sessioncount,1)."</th></tr>\n";
  $head .= "<th>% P</th></tr><!-- /head -->\n";
  $colgroupdef .="</colgroup>\n";
  $result .=  "</table>\n";
  $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
  $result = $tabledef."\n<thead>\n".$colgroupdef."\n</thead>\n".$head.$result;
  return $result;
} /* checktable */

function presenceTable($dbConn, $prjm_id,$constraint=''){
  $sqln= "select afko,description,prjm_id ,year,prj_id,milestone \n"
    ." from project natural join prj_milestone where prjm_id=$prjm_id";
  //$page_opening="Presence list for project $afko $description prjm_id $prjm_id prj_id $prj_id milestone $milestone";
  $resultSet = $dbConn->Execute( $sqln );
  if (!$resultSet->EOF){
    extract($resultSet->fields);
    $caption="Presence list for project $afko ($year) $description prjm_id $prjm_id prj_id $prj_id milestone $milestone";
  } else { 
    $caption='Presence list';
  }
  $sql ="select \n".
  //  "al.student, ".
    //  " al.snummer,\n".
    //    " roepnaam||coalesce(' '||voorvoegsel||' ',' ')||achternaam as name,".
    "p.afko||p.year as activity,".
    "agroup as grp, \n".
  " datum||'#'||al.act_id||': '||short||' '||al.description as title, present,note \n".
    " from act_presence_list2 al join student st using(snummer) \n".
    "natural join activity join prj_milestone using(prjm_id) join project p using (prj_id)".
  " left join absence_reason ar using (act_id,snummer)\n".
  " where prjm_id=$prjm_id ";
 if ($constraint != ''){
   $sql .=' and '.$constraint;
 }
 $sql .= " order by datum,start_time";//achternaam,roepnaam,al.act_id desc\n";
 return checkTable($dbConn,$sql,0,2,3,4,"<table summary='$caption' border='1' align='left' style='border-collapse:collapse'>\n".
		   "<caption style='font-weight:bold'>$caption</caption>\n");
}

function personalPresenceList($dbConn,$snummer) {
  $result="<table style='background:rgba(255,255,255,0.5);margin: 0 0 0 1em'><br/>";
  $sql = "select distinct prjm_id from activity_project \n"
    ."natural join project natural join prj_milestone natural join prjm_activity_count \n"
    ."natural join prj_tutor \n"
    ."natural join prj_grp where snummer=$snummer order by prjm_id desc";
  $resultSet = $dbConn->Execute( $sql );
  while(!$resultSet->EOF ) {
    $prjm_id = $resultSet->fields['prjm_id'];
    $result .= "<tr><td>\n".presenceTable($dbConn,$prjm_id, "snummer=$snummer")."\n</td></tr>\n";
    $resultSet->moveNext();
  }
  return $result."</table>";
}
?>