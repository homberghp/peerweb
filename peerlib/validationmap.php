<?php

$validationmap=array();
$sql ="select input_name, regex_name, regex, starred from validator_regex_map";
//$dbConn->log($sql);
$rs=$dbConn->query($sql);
if ($rs=== false){
   $dbConn->log($dbConn->ErrorMsg());
} else {
  foreach ($rs as $row){
    $validationmap[$row['input_name']]=array();
    $validationmap[$row['input_name']]['input_name']=$row['input_name'];
    $validationmap[$row['input_name']]['regex_name']=$row['regex_name'];
    $validationmap[$row['input_name']]['regex']=$row['regex'];
    $validationmap[$row['input_name']]['starred']=$row['starred'];
//    $rs->movenext();
  }
}
$log_unknown_names=true;
$log_validation_failures=true;
