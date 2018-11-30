<?php
/**
 * To help the dba an programmers this page shows the (syntax highlighted) sources of the
 * application.
 * @package peerweb
 */
?>
<!DOCTYPE public "-//w3c//dtd html 4.01 transitional//en"
		"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<link rel="stylesheet" type="text/css" href="style.css">
<?php
require_once("peerutils.php");
require_once("utils.php");
requireCap(CAP_SYSTEM);
//require_once("nav6.php");
/* if ($am_user_blocked == 'J') {
 include("blocked_tail.inc");
 exit;
 }
*/
if (isSet($_GET['filename'])) {
	$filename=trim($_GET['filename']);
} else {
  $filename=__FILE__;
}
$navTitle= "Peerweb source of ".$filename;
?>
<title>
<?php echo $navTitle; ?>
</title>
</head>
<body>
<?php
  //navstart($navTitle,__FILE__);
echo 'Het include path ='.ini_get('include_path');
?>
<h2>Source van php files</h2>
Hiermee kun je source files (.php, .inc, .html en .css) van de applicatie bekijken.<br/>
<form name="gettable" method="GET" action="showsource.php">
<table>
<tr>
<td>Naam van de file</td>
<td>
<select name="filename">
<?php
$filecount=0;
$arr=array();
if ($dir = @opendir("./")) {
  while (($file = readdir($dir)) != false) {
    if (!is_dir($file) &&( ereg('.php$',$file) || ereg('.inc$',$file) || ereg('.html$',$file)|| ereg('.css$',$file))) {
      $arr[$filecount++] = $file;
    }
  }
  closedir($dir);
  sort($arr);
  for ($i=0; $i < $filecount; $i++) {
    $file=$arr[$i];
    echo "<option name=\"$file\" value=\"$file\" ".($filename==$file?"selected":"").">$file</option>\n";
  }
}
?>
</select>
</td>
<td><input class="button" type="submit" name="submit" value="haal op"></td>
</tr>
</table>
</form>
<hr>
<?php
if ($filename != '') {?><br/>
<h3> source of <?=$filename?></h3>
    <div style="background-color:#FFFFFF; text-width=400px;">
      <?php show_source($filename);?>
    </div>
<?php
}?>
<?php
  //navEnd("showsource");
?>
</body>
<!-- $Id: showsource.php 439 2010-08-24 08:09:12Z hom $ -->
</html>
