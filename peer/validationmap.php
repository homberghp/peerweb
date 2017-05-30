<?php

$validationmap=array();
$sql ="select input_name, regex_name, regex, starred from validator_regex_map";
//$dbConn->log($sql);
$rs=$dbConn->Execute($sql);
if ($rs=== false){
   $dbConn->log($dbConn->ErrorMsg());
} else {
  while (!$rs->EOF){
    $validationmap[$rs->fields['input_name']]=array();
    $validationmap[$rs->fields['input_name']]['input_name']=$rs->fields['input_name'];
    $validationmap[$rs->fields['input_name']]['regex_name']=$rs->fields['regex_name'];
    $validationmap[$rs->fields['input_name']]['regex']=$rs->fields['regex'];
    $validationmap[$rs->fields['input_name']]['starred']=$rs->fields['starred'];
    $rs->movenext();
  }
}
$log_unknown_names=true;
$log_validation_failures=true;
?>