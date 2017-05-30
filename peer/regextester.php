<?php
/**
 * The simple table editor for the table menu. Menu is one of the tables that support
 * the simple table editor.
 *
 * @package prafda2
 * @author Pieter van den Hombergh
 * $Id: regextester.php 1723 2014-01-03 08:34:59Z hom $
 */
include_once("peerutils.php");
include_once('navigation2.php');
$regex='/.*/';
$string='This matches';
$regex_name='untitled';
if (isSet($_REQUEST['regex_name'])) {
    $regex_name=$_REQUEST['regex_name'];
    $sql= "select regex_name,regex from validator_regex where regex_name='$regex_name'\n";
    $resultSet= $dbConn->Execute($sql);
    if (!$resultSet->EOF){
	$regex=$resultSet->fields['regex'];
    }
 }
if (isSet($_POST['regex']) && $_POST['regex'] !='') $regex =$_POST['regex'];

if (isSet($_POST['string'])) $string =$_POST['string'];
$matches=preg_match($regex,$string);

if ($matches) {
  $matchStyle='color:white;background:#080';
  $matchst="<span style='$matchStyle'>Yes</span>"; 
}else {
  $matchStyle='color:white;background:#800;font-weight:bold;';
  $matchst="<span style='$matchStyle'>No</span>"; 
}
$navTitle= "Peerweb regex testscript ".$PHP_SELF." on DB ".$db_name;
$page = new PageContainer();
$page_opening='Regex tester';
$page->setTitle($page_opening);
//$dbConn->setSqlAutoLog(true);
$nav=new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$page->addBodyComponent($nav);
$regex_length=max(strlen($regex),40);
$formString="
<fieldset align='center' width='50%'><legend>PCRE regex tester</legend>
<h3>This is a tester for the PHP implementation of PERL compatible regular expressions.</h3>
</p>The peerweb application requires UTF8 conformance, which requires proper support from the regex library.
At least PHP 5.2.3 and it's bundled lebprce promise to provide that.</p>

<form name='regex' method='post' action='$PHP_SELF'>
<table>
<tr><th>regex_name</t><th>Regex</th><th>String</th><th>matches</th></tr>
<tr><th>$regex_name</th><td><input name='regex' value='$regex' size='$regex_length'/></td>
<td><input name='string' value='$string' size='40'/></td><td>$matchst</td></tr>
<tr><th>Result</th><th>'$regex'</th><th style='$matchStyle'>'$string'</th><th>&nbsp;</th></tr>
<tr><td colspan='3'><input type='submit' name='submit'/></td></tr>
</table>
</form>
</fieldset>
";

$page->addBodyComponent(new Component($formString));
$sql= "select regex_name,regex from validator_regex order by regex_name\n";
$resultSet= $dbConn->Execute($sql);
$table = "<table>\n\t<th>Regexname</th><th></th></tr>\n";
while (!$resultSet->EOF){
    extract($resultSet->fields);
    $table .= "\n<tr><td><a href='$PHP_SELF?regex_name=$regex_name'>$regex_name</a></td><td>$regex</td></tr>";
    $resultSet->moveNext();
}
$table .="</table>";
$page->addBodyComponent(new Component($table));
$page->show();
?>
