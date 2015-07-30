<?php
// $validationmap=array(
// 		  'snummer' => '/^\d{6,8}$/',
// 		  'doc_id'  =>  '/^\d+$/',
// 		  'date'    =>  '/^\d{4}-\d{2}-\d{2}$/',
// 		  'assessment_due' =>  '/^\d{4}-\d{2}-\d{2}$/',
// 		  'prj_id'  =>  '/^\d+$/',
// 		  'peer_id' =>  '/^\d{6,8}$/', 
// 		  'milestone'=> '/^\d{1,2}$/',
// 		  'prj_id_milestone' => '/\d+?:\d{1,2}$/',
// 		  'prj_id_milestone_grp_num' => '/\d+?:\d{1,2}:\d{1,2}$/',
// 		  'prj_task_id' => '/\d+?:\d+?:\d+?$/',
// 		  'sortorder' => '/^(asc|desc)$/',
// 		  'grp_count' => '/^\d{1,2}$/',
// 		  'tutor'   => '/^[A-Z]{3}$/',
// 		  'sclass'  => '/^\w{1,6}$/',
// 		  'grp_num' => '/^\d{1,3}$/',
// 		  'doctype' => '/^\d+$/',
// 		  'integer' => '/^\d+$/',
// 		  'phone_number' => '/^\+?(\d|\s){8,20}$/',
// 		  'signed_integer' => '/^(\+|-)\d+$/',
// 		  'split_minute' => '/^(\d+\s*days?\s*)?(\d+?(:\d{2}){1,2}|\d{1,2})?$/',
// 		  'timestamp' => '/^\s*\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/',
// 		  'duration' => '/^\d{2}:\d{2}:\d{2}$/',
// 		  'cword4' => '/^\w{1,4}$/',
// 		  'cword6' => '/^\w{1,6}$/',
// 		  'email' => '/^\w+(\w|\-|\.)*\@[a-zA-Z0-9][a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/',
// 		  'achternaam'=>'/^(\w|\s)+$/',
// 		  'alias'=>'/^(\w|\s){,16}$/',
// 		  'roepnaam'=>'/^(\w|\s){,16}$/',
// 		  'voorletters'=>'/^(\w|\.){,6}$/',
// 		  'project.grp_num' => '/^(\w+)?\.\d+$/',
// 		  'year_month' => '/^\d{4}:\d{1,2}$/',
// 		  'grp_num_contact' => '/^\d{1,2}:\d{6,8}$/',
// 		  'countrycode2' => '/^[A-Z]{2}$/',
// 		  );

// $sql="select count(*) from validator_regex_map";
// $dbConn->log($sql);
// $rs=$dbConn->Execute($sql);
// if ($rs=== false){
//   $dbConn->log($dbConn->ErrorMsg());
// }
// if (!$rs->EOF){
//   $dbConn->log("new map");
//   if ($rs->fields['count'] < count($validationmap)) {
//     $sql ="begin work;\n";
//     foreach ($validationmap as $key =>$value ){
//       $sql .="insert into validator_regex (regex_name,regex) values('$key','$value');\n".
// 	"insert into validator_map (input_name,regex_name) values('$key','$key');\n";
//     }
//     $sql .="commit";
//     $dbConn->log($sql);
//     $dbConn->Execute($sql);
//   }
// }

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