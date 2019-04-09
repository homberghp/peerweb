<?php
  /* $Id: validators.php 1729 2014-02-06 10:30:18Z hom $ */
  /*
   * validator is var name, regex, replacement value
   */
$validators=array(
		  'snummer' =>  array( '/^\d{4,8}$/', 1),
		  'doc_id'  =>  array( '/^\d+$/', 0),
		  'date'    =>  array( '/^\d{4}-\d{2}-\d{2}','1970-01-01'),
		  'prj_id'  =>  array( '/^\d+$/',1),
		  'peer_id' =>  array( '/^\d{4,8}$/', 0 ),
		  'milestone'=> array( '/^\d{1,2}$/',1),
		  'prj_id_milestone' => array( '/\d+?:\d{1,2}$/','1:1'),
		  'prj_id_milestone_grp_num' => array( '/\d+?:\d{1,2}:\d{1,2}$/','1:1:1'),
		  'prj_task_id' => array( '/\d+?:\d+?:\d+?$/','0:0:0'),
		  'sortorder' => array( '/^(asc|desc)$/','asc'),
		  'grp_count' => array( '/^\d{1,2}$/',2),
		  'tutor'   => array( '/^[A-Z]{3}$/','HEU' ), 
		  'sclass'  => array( '/^\w{1,6}$/','TIPT1' ), 
		  'grp_num' => array('/^\d{1,3}$/',1),
		  'doctype' => array('/^\d+$/',1),
		  'fotodir' => array('/^(fotos|mfotos)$/','fotos'),
		  'integer' => array('/^\d+$/',1),
		  'phone_number' => array('/^\+?(\d|\s){8,20}$/','+31877877777'),
		  'signed_integer' => array('/^(\+|-)\d+$/',0),
		  'split_minute' => array('/^(\d+\s*days?\s*)?(\d+?(:\d{2}){1,2}|\d{1,2})?$/','0 00:00:00'),
		  'timestamp' => array('/^\s*\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/','1955-03-18 21:58:12'),
		  'duration' => array('/^\d{2}:\d{2}:\d{2}$/','00:00:00'),
		  'cword4' => array('/^\w{1,4}$/','unkn'),
		  'cword6' => array('/^\w{1,6}$/','unknow'),
		  'email' => array( '/^\w+(\w|\-|\.)*\@[a-zA-Z0-9][a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/','unknown@mythical.com'),
		  'project.grp_num' => array( '/^(\w+)?\.\d+$/','dfelt.1'),
		  'year_month' => array('/^\d{4}:\d{1,2}$/','2005-01'),
		  'grp_num_contact' => array('/^\d+:\d{4,8}$/','1:879417'),
		  'countrycode2' => array('/^[A-Z]{2}$/','NL'),
		  );
/**
 * @param $value to be validated
 * @param $typename : type to validate against
 * @param $replacement : replacement in $value is invalid
 * @return $value or replacement if $typename known, 0 if typename unknown
 */
function validate($value, $typename, $replacement ) {
    global $validators;
    if ( isset( $validators[$typename] ) ) {
	if (preg_match($validators[$typename][0],$value)) return $value;
	else return $replacement;
    } else return 0;
}
/**
 * date tester.
 */
function validate_date($value, $replacement='1970-01-01') {
    $match=array(); // 0=full string, 1= year, 2=month, 3=day
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/',$value,$match)) return $replacement;
    //    echo "<pre>"; print_r($match); echo "</pre><br/>\n";
    if (checkdate($match[2],$match[3],$match[1])) return $value;
    else return $replacement;
}
?>