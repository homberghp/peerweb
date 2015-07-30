<?php
$sql ="select distinct afko||':'||description as name, prj_id as value,afko\n"
    ." from all_prj_tutor join prj_grp using(prjtg_id) where snummer=$snummer and prj_grp_open=true order by afko";
$prjList = "<select name='presence_prj_id' onchange='submit()'>\n". 
    getOptionList($dbConn,$sql,$presence_prj_id).
    "</select>\n";
$pdayList= "<select name='presence_day' onchange='submit()'>\n".
    getOptionList($dbConn,"select shortname as name, day as value from weekdays where day in (1,2,3,4,5) order by day ",$presence_day).
    "\n</select>\n";
$hourList = "<select name='hourcode' onchange='submit()'>\n". 
    getOptionList($dbConn,"select distinct hourcode as name, hourcode as value from timetableweek order by hourcode",$hourcode).
    "</select>\n";
?>
<fieldset><legend>Presence in the current course period</legend>
<form method='get' name='coursehourform' action='<?=$PHP_SELF;?>'>
Project <?=$prjList?> day <?=$pdayList?> hour <?=$hourList?><input type='submit' name='bsubmit'/>
</form>
<?php
$sql = "select snummer,sclass ,achternaam,roepnaam,voorvoegsel,course_week_no,\n".
    "shortname,day,hourcode,date_trunc('seconds',since) as since,from_ip,\n".
    "case when from_ip << '145.85/16' then 'P' else 'Q' end as location_ok".
    " from participant_present_list join student using(snummer)\n".
    " join student_class using(class_id)\n".
    " join weekdays using(day) where prj_id=$presence_prj_id and day=$presence_day and hourcode=$hourcode\n".
    " order by sclass,achternaam,roepnaam,snummer,course_week_no";
$resultSet=$dbConn->Execute($sql);
if ($resultSet == false) {
    die( "<br>Cannot get presence data with <pre>$sql</pre> cause".$dbConn->ErrorMsg()."<br>");
 }
echo "<table border='1' style='border-collapse:collapse'>\n";
$snummer=$resultSet->fields['snummer'];
$day=-1;
$row="<tr>\n\t<th>snummer</th>\n".
    "<th>class</th>\n".
    "\t<th>name</th>\n".
    "\t<th>day</th>\n".
    "\t<th>hour</th>\n";
while (!$resultSet->EOF && $resultSet->fields['snummer'] == $snummer) {
    $row .= "\t<th>w{$resultSet->fields['course_week_no']}</th>\n";
    $resultSet->moveNext();
 }
echo $row."\n</tr>\n";
$snummer=0;
$resultSet->moveFirst();
$hourcode='-1';
$day=-1;
$course_week_no=-1;
$row='';
while (!$resultSet->EOF) {
    if ($snummer != $resultSet->fields['snummer']) {
	if ($row != '' ) echo $row."\n</tr>\n";
	$row="<tr>\n<td>{$resultSet->fields['snummer']}</td>".
	    "<td>{$resultSet->fields['sclass']} ".
	    "<td>{$resultSet->fields['roepnaam']} ".
	    "{$resultSet->fields['voorvoegsel']} ".
	    "{$resultSet->fields['achternaam']}</td>".
	    "<td align='right'>{$resultSet->fields['shortname']}</td>\n".
	    "<td align='right'>{$resultSet->fields['hourcode']}</td>\n";
    }
    if ($hourcode       != $resultSet->fields['hourcode'] ||
	$day            != $resultSet->fields['day'] ||
	$course_week_no != $resultSet->fields['course_week_no']
	) {
	extract($resultSet->fields);
	if (isSet($from_ip)) {
	    $imgsrc=($location_ok == 'P')?(IMAGEROOT.'/fonicosquare.gif'):(IMAGEROOT.'/gnome-globe.png');
	    $location_remark=($location_ok=='P')?(''):(', not at Fontys');
	    $row .="\t<td title='on {$since} from {$from_ip}{$location_remark}'><img src='$imgsrc' alt='V'/></td>\n";
	} else {
	    $row .="\t<td>&nbsp;</td>\n";
	}
    }
    $resultSet->moveNext();
 }
echo $row."\n</tr>\n";
echo "</table>\n";

?>
</fieldset>
