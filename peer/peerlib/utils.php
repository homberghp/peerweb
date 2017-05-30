<?php
/* $Id: utils.php 1723 2014-01-03 08:34:59Z hom $ */
/**
 * @param timestamp $startDay  as created by php mktime (second since unix epoch)
 * @param int $month : for which month is this week
 * @param string $thisMonthClass string used in class=\"$thisMonthClass\" to produce class tag
 * @param string $otherMonthClass string used in class=\"$otherMonthClass\" to produce class tag
 * @param $rowtype. either th or td, untested
 * @paran $indentLevel number of tabs to start from the left edge. Only for html formatting
 * For weeks that cross month boundaries, the student_class can differ in the style 
 * defined by start and end class. switching occurs if the month changes.
 * for a week starting on mar 28, and ending in april the row produced would look like
 * <td clas=\"$thisMonthClass">28<td> 
 * <td clas=\"$thisMonthClass">29<td>
 * <td clas=\"$thisMonthClass">30<td>
 * <td clas=\"$thisMonthClass">31<td>
 * <td clas=\"$otherMonthClass"> 1<td>
 * <td clas=\"$otherMonthClass"> 2<td>
 * <td clas=\"$otherMonthClass"> 3<td>
 */
function calenderWeekRow( $startDay, $forMonth,$monthClass, $otherMonthClass, $rowtype,$indentLevel){
  $today = getdate();
  $today_ts = mktime(0,0,0,$today['mon'],$today['mday'],$today['year']);
  $month= date("n",$startDay);
  $tabbing = "";
  for ($i = 0; $i < $indentLevel; $i++ ) {
    $tabbing .= "\t";
  }
  for ($i = 0; $i < 7; $i++) {
    $month= date("n",$startDay);
    $cellClass = ($month==$forMonth)?$monthClass:$otherMonthClass;
    if ($startDay == $today_ts) $cellClass='today';
    $dn = date("j",$startDay);
    echo "$tabbing<$rowtype class=\"$cellClass\">$dn</$rowtype>\n";
    $startDay += 86400; // add a day in seconds
  }
}

/**
 * @param timestamp $startDay  as created by php mktime (second since unix epoch)
 * @param int $month : for which month is this week
 * @param string $thisMonthClass string used in class=\"$thisMonthClass\" to produce class tag
 * @param string $otherMonthClass string used in class=\"$otherMonthClass\" to produce class tag
 * @param $rowtype. either th or td, untested
 * @paran $indentLevel number of tabs to start from the left edge. Only for html formatting
 * For weeks that cross month boundaries, the student_class can differ in the style 
 * defined by start and end class. switching occurs if the month changes.
 * for a week starting on mar 28, and ending in april the row produced would look like
 * <td clas=\"$thisMonthClass">28<td> 
 * <td clas=\"$thisMonthClass">29<td>
 * <td clas=\"$thisMonthClass">30<td>
 * <td clas=\"$thisMonthClass">31<td>
 * <td clas=\"$otherMonthClass"> 1<td>
 * <td clas=\"$otherMonthClass"> 2<td>
 * <td clas=\"$otherMonthClass"> 3<td>
 */
function calenderWeekDayNamesRow( $startDay, $forMonth,$monthClass, $otherMonthClass, $rowtype,$indentLevel){
  $dayNames = array("Maa","Din","Woe","Don","Vri","Zat","Zon");
  $today = getdate();
  $today_ts = mktime(0,0,0,$today['mon'],$today['mday'],$today['year']);
  $month= date("n",$startDay);
  $tabbing = "";
  for ($i = 0; $i < $indentLevel; $i++ ) {
    $tabbing .= "\t";
  }
  for ($i = 0; $i < 7; $i++) {
    $month= date("n",$startDay);
    $cellClass = ($month==$forMonth)?$monthClass:$otherMonthClass;
    if ($startDay == $today_ts) $cellClass='today';
    $dn = $dayNames[$i];
    echo "$tabbing<$rowtype class=\"$cellClass\">$dn</$rowtype>\n";
    $startDay += 86400; // add a day in seconds
  }
}

function monthName($i) {
  $monthNamesL = array("Januari","Februari","Maart","April","Mei",
		       "Juni","Juli","Augustus","September","October",
		       "November","December");
  return $monthNamesL[(($i -1) % 12)];
}
function monthNameShort($i) {
  $monthNames = array("Jan","Feb","Mrt","Apr","Mei",
		       "Jun","Jul","Aug","Sep","Oct",
		       "Nov","Dec");
  return $monthNames[(($i -1) % 12)];
}
/**
 * returns &nbsp; if $string is empty or '0'
 */
function bwz($string) {
  if ($string == '' || $string=='0') {
    return '&nbsp;';
  } else {
    return $string;
  }
}
/**
 * capitalize and remove underscores
 */
function nicerName( $s ){
  $result =$s;
  $result=ucfirst(strtolower($result));
  $result=str_replace('_',' ',$result);
  return $result;
}
?>
