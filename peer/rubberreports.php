<?php
requireCap(CAP_SYSTEM);
require_once('validators.php');
require_once('rubberstuff.php');
require_once('navigation2.php');

$tutor_navtable=array();
$activeRubberProject='';
extract($_SESSION);
$css='<style type=\'text/css\'><!--
  .flink {text-align:right}
  th.flink > a {text-decoration:none;color:#008;}
-->
</style>';
pagehead2('Rubber',$css);
$page_opening="Rubber reports at $rubberbase";
$nav=new Navigation($tutor_navtable, basename(__FILE__), $page_opening);
$nav->show();
$_SESSION['conf_editor_basedir'] = $rubberbase;
echo "<br/>\n";
$rubberTree = array();
if ($handle1 = opendir($rubberbase)) {
  while (false !== ($subdirname = readdir($handle1))) { // build tree
    $subdirpath="$rubberbase/$subdirname";
    if ( !isset($rubberSystemDirs[$subdirname]) && is_dir($subdirpath)) {
      if ( $activeRubberProject=='') {
	$_SESSION['activeRubberProject'] = $activeRubberProject = $subdirname;
      }
      if ( $subdirname == $activeRubberProject ) {
	$cssClass= 'activeRubber'; 
      } else {
	$cssClass= 'rubber'; 
      }
      $rubberTree[$subdirname] = array();
      if ($handle2 = opendir($subdirpath)) {
	while (false !== ($pfilename = readdir($handle2))) {
	  if (substr($pfilename,0,1) != '.' ) {
	    $pfilepath = "$subdirpath/$pfilename";
	    //	    echo "seen $pfilepath<br/>";
	    if (is_dir($pfilepath)) {
	      //	      echo "\t$pfilepath isdir<br/>";
	      if ($handle3 = opendir($pfilepath)) {
		while (false !== ($projectoutfilename = readdir($handle3))) {
		  if (isSet($projectoutfilename) && substr($projectoutfilename,0,1) != '.') {
                      $parts=explode('.',$projectoutfilename);
		    $extension= end($parts);
		    $projectRun = basename($projectoutfilename,'.'.$extension);
		    $hrefTarget ="$subdirname/$pfilename/$projectoutfilename";
		    $rubberTree[$subdirname][$projectRun][$extension] = $hrefTarget;
		    //		    echo "<a href='getrubber.php?rubberproduct=$hrefTarget'>$projectoutfilename</a><br/>";
		  }
		}
		closedir($handle3);
	      }
	    } else {
	      //	      echo "\t$pfilepath not isdir<br/>";
	    }
	  }
	}
	closedir($handle2);
      }
    }}
//   echo "<pre>\n";
//   print_r($rubberTree);
//   echo "</pre>\n";
  foreach ($rubberTree as $projectName => $subTree) {
  echo "<fieldset class='$cssClass'><legend style='font-size:140%;font-weight:bold;'>$projectName</legend><br/>\n";
    // $projectName is a subdir of rubberbase
    $subdirpath="$rubberbase/$projectName";
    $confFiles = array();
//     echo $subdirpath;
    if ($handle2 = opendir($subdirpath)) {
      while (false !== ($projectConfFile = readdir($handle2))) {
	if ((substr($projectConfFile,0,1) != '.') && !is_dir($subdirpath.'/'.$projectConfFile)) {
	  $confFiles[] = $projectConfFile;
	  //	  $editorFile=$subdirpath.'/'.$projectConfFile;
	}
      }
      echo "<table style='border-collapse:collapse' border='1'><tr>";
      asort($confFiles);
      foreach ($confFiles as $projectConfFile ) {
	  echo "<td style='padding:5px 5px'><form name='edit$subdirname' action='rubberedit.php' method='get'>"
	    ."<b>$projectConfFile</b><button name='rubberEditFile' type='submit' value='$projectName/$projectConfFile'>E</button></form></td>\n";
      }
      echo "</tr>\n</table>\n";
      closedir($handle2);
      //      echo implode(';',$confFiles);
    }
  echo "<form method='get' name='run$projectName' action='rubberrun.php'>\n".
    "<button type='submit' name='bsubmit'>$run_icon</button> ".
    "<input type='hidden' name='project' value='$projectName'/>".
    "<b style='font-size:14pt;'>$projectName</b>".
    "</form>\n";
    echo "<table summary='Report products' style='border-collapse:collapse' border='1'>\n".
      "<tr><th>Run</th><th>PDF</th><th>Process log</th><th>TeX File</th>".
      "<th>LaTex log</th><th>CSV file</th><th>ZIP file</th><th>Delete</th></tr>\n";
    arsort($subTree);
    $rowCounter=0;
    $rowColor=($rowCounter%2)?'#ccf':'#fff';
    foreach( $subTree as $subTreeName => $runTree ) {
      $rowCounter++;
      if ( isset($runTree['pdf']) ) {
	$hrefTarget = $runTree['pdf'];
	$filesize = filesize($rubberbase.'/'.$hrefTarget);
	$filesize = number_format($filesize ,0,',','.');
	$pdf= "<th class='flink'><a href='getrubber.php?rubberproduct=$hrefTarget'>&nbsp;$filesize b&nbsp;$pdf_icon </a></th>\n";
      } else {
	$pdf= "<th>&nbsp;</th>";
      }
      if ( isset($runTree['plog']) ) {
	$hrefTarget  = $runTree['plog'];
	$filesize = filesize($rubberbase.'/'.$hrefTarget);
	$filesize = number_format($filesize ,0,',','.');
	$plog = "<th class='flink'><a href='getrubber.php?rubberproduct=$hrefTarget'>&nbsp;$filesize b&nbsp;$plog_icon </a></th>";
	// assuming there is always a plog, create the delete target from that.
	$deleteTarget = preg_replace('/\.plog$/','',$hrefTarget);
	$delete = "<th class='flink'><a href='burnrubber.php?rubberproduct=$deleteTarget'>$trash_icon</a></th>\n";
      } else {
	$delete = 
	$plog= "<th>&nbsp;</th>";
      }
      if ( isset($runTree['tex']) ) {
	$hrefTarget  = $runTree['tex'];
	$filesize = filesize($rubberbase.'/'.$hrefTarget);
	$filesize = number_format($filesize ,0,',','.');
	$tex = "<th class='flink'><a href='getrubber.php?rubberproduct=$hrefTarget'>&nbsp;$filesize b&nbsp;$latex_icon</a></th>\n";
      } else {
	$tex = "<th>&nbsp;</th>";
      }
      if ( isset($runTree['log']) ) {
	$hrefTarget  = $runTree['log'];
	$filesize = filesize($rubberbase.'/'.$hrefTarget);
	$filesize = number_format($filesize ,0,',','.');
	$log = "<th class='flink'><a href='getrubber.php?rubberproduct=$hrefTarget'>&nbsp;$filesize b&nbsp;$latexlog_icon</a></th>\n";
      } else {
	$log= "<th>&nbsp;</th>";
      }
      if ( isset($runTree['csv']) ) {
	$hrefTarget  = $runTree['csv'];
	$filesize = count(file($rubberbase.'/'.$hrefTarget));
	if ($filesize ) $filesize--;
	$filesize = number_format($filesize ,0,',','.');
	$csv = "<th class='flink'><a href='getrubber.php?rubberproduct=$hrefTarget'>&nbsp;$filesize records$csv_icon</a></th>\n";
      } else {
	$csv= "<th>&nbsp;</th>";
      }
      if ( isset($runTree['zip']) ) {
	$hrefTarget  = $runTree['zip'];
	$zip = "<th class='flink'><a href='getrubber.php?rubberproduct=$hrefTarget'>$zip_icon</a></th>\n";
      } else {
	$zip= "<th>&nbsp;</th>";
      }
      $rowColor=($rowCounter%2)?'#ccf':'#fff';
      echo "<tr style='background:$rowColor;valign:center;'>\n\t<th>$subTreeName</th>\n$pdf$plog$tex$log$csv$zip$delete</tr>\n";
    }
    echo "</table>\n";
    echo "</fieldset>\n";
  }
 } else {
  echo "cannot open $subdirpath<br/>";
 }
?>