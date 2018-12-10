<?php

requireCap(CAP_TUTOR);

/**
 * prints a birtday calander for today
 */
class BirthDaysToDay {

    public function __construct() {
        
    }

    public function __toString() {

//	global $dbConn;
//	$result= "<div class='birthday' onclick='new Effect.Puff(this);'>
//<h3 style='color:white' align='center'><i>And a happy birthday to</i></h3>
//<table border='0' rules='groups' frame='box' width='100%' style='color:white;text-align:left' class='birthday' summary='Birthdays today'>
//<thead>
//<tr><th colspan='1'>Name</th><th>Class/faculty</th></tr>
//</thead>";
//
//	$sql = "select distinct roepnaam,tussenvoegsel,achternaam, rtrim(email1) as email1,\n".
//	    "rtrim(email2||' or') as email2, sc.sclass as sclass,age(gebdat),\n".
//	    "faculty_short from birthdays \n".
//	    " join student_class sc using(class_id)\n".
//	    "left join alt_email using(snummer)\n"
//	    ." where "
//	  ."sc.sclass  not ilike 'UIT%' and sc.class_id <>0 and sc.sclass !~* E'^.+?(dump|uit)\\\\s*$'"
//	  ."order by achternaam,roepnaam";
//	$resultSet= $dbConn->Execute($sql);
//	if ($resultSet=== false) {
//	    $dbConn->log('tt Error: '.$dbConn->ErrorMsg().' with '.$sql);
//	} else while (!$resultSet->EOF) {
//	    extract($resultSet->fields);
//	    $has_doc=isSet($has_doc)?'D':'';
//	    $has_assessment=isSet($has_assessment)?'A':'';
//	    $result .= "<tr>".
//		"<td style='font-weight:bold;'>$roepnaam $tussenvoegsel $achternaam</td>".
//		"<td >$sclass/$faculty_short</td>".
//		"</tr>\n";
//	    $resultSet->moveNext();
//	}
//	$result .= "</table>\n</div>\n";
        return "<div></div>"; //$result;
    }

}

?>
