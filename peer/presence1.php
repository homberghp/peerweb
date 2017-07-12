<?php

echo "<fieldset><legend>Presence in the current course period of $roepnaam $tussenvoegsel $achternaam, <span style='font-size:6pt;'>($snummer)</span></legend>";
?>
<table><tr><td width='50%'>
<?php
$sql ="select snummer,date_trunc('seconds',since) as since,ch.start_time,ch.stop_time ,from_ip,".
    "case when from_ip << '145.85/16' then 'P' else 'Q' end as location_ok,".
    "shortname,day,\n".
    "hourcode,course_week_no from course_hours ch join weekdays w using(day) left join  (select * from logon_map_on_timetable where snummer=$snummer) lm\n".
    "using(course_week_no,day,hourcode)\n".
    " where (snummer=$snummer or snummer is null) order by day,hourcode,course_week_no";
$resultSet=$dbConn->Execute($sql);
if ($resultSet == false) {
    die( "<br>Cannot get presence data with <pre>$sql</pre> cause".$dbConn->ErrorMsg()."<br>");
 }
echo "<table border='1' style='border-collapse:collapse'>\n";
$hourcode=$resultSet->fields['hourcode'];
$day=-1;
$row="<tr>\n\t<th>Day</th>\n\t<th>hour</th>\n";
while (!$resultSet->EOF && $resultSet->fields['hourcode'] == $hourcode) {
    $row .= "\t<th>w{$resultSet->fields['course_week_no']}</th>\n";
    $resultSet->moveNext();
 }
echo $row."\n</tr>\n";
$resultSet->moveFirst();
$hourcode='-1';
$day=-1;
$course_week_no=-1;
$row='';
while (!$resultSet->EOF) {
    if ($hourcode != $resultSet->fields['hourcode']) {
	if ($row != '' ) echo $row."\n</tr>\n";
	$row="<tr>\n<th>{$resultSet->fields['shortname']}</th><th align='right'".
	    " title='{$resultSet->fields['start_time']} - {$resultSet->fields['stop_time']}'>{$resultSet->fields['hourcode']}</th>\n";
    }
    if ($hourcode       != $resultSet->fields['hourcode'] ||
	$day            != $resultSet->fields['day'] ||
	$course_week_no != $resultSet->fields['course_week_no']
	) {
	extract($resultSet->fields);
	if (isSet($snummer)) {
	    $imgsrc=($location_ok == 'P')?(IMAGEROOT.'/fonicosquare.gif'):(IMAGEROOT.'/gnome-globe.png');
	    $location_remark=($location_ok=='P')?(''):(', not at Fontys');
	    $row .="\t<td title='on {$since} from {$from_ip}{$location_remark}' style='halign:center;valaign:center'><img src='$imgsrc' alt='V'/></td>\n";
	} else {
	    $row .="\t<td>&nbsp;</td>\n";
	}
    }
    $resultSet->moveNext();
 }
echo $row."\n</tr>\n";
echo "</table>\n";

?>
</td><td valign='top'>
<h2>Presence</h2>
<p>If you login to peerweb , the time and source(ip address) of the login is recorded. This information can be used to record your presence
at the computer facilities of Fontys.</p>
</td>
</tr>
</table>
</fieldset>
<?php
  //end
?>