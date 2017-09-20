<?php
require_once('./peerlib/peerutils.php');
require_once('./peerlib/validators.php');
include_once('navigation2.php');
require_once 'studentpicker.php';
require_once'prjMilestoneSelector2.php';
requireCap(CAP_TUTOR);
$prjm_id=$prj_id = $milestone=1;
$newauditor=0;
unset($_SESSION['newauditor']);
extract($_SESSION);
$prjSel=new PrjMilestoneSelector2($dbConn,$peer_id,$prjm_id);
extract($prjSel->getSelectedData());

$_SESSION['prj_id']=$prj_id;
$_SESSION['prjm_id']=$prjm_id;
$_SESSION['milestone']=$milestone;

if (isSet($_GET['newauditor'])) {
    unset($_POST['newauditor']);
    $_REQUEST['newauditor']=$newauditor =validate($_GET['newauditor'],'snummer','0'); 
    //    $dbConn->log('GET '.$newauditor);
 } else if (isSet($_POST['newauditor'])){
    unset($_GET['newauditor']);
    $_REQUEST['newauditor']=$newauditor =validate($_POST['newauditor'],'snummer','0'); 
    //    $dbConn->log('POST '.$newauditor);
 } else {
    unset($_POST['newauditor']);
    unset($_REQUEST['newauditor']);
    unset($_GET['newauditor']);
 }
$searchname='';
$studentPicker= new StudentPicker($dbConn,$newauditor,'Search and select auditor.');
$studentPicker->setInputName('newauditor');
if (isSet($_REQUEST['searchname'])) {
    if (!preg_match('/;/',$_REQUEST['searchname'])) { 
	$searchname=$_REQUEST['searchname'];
	$studentPicker->setSearchString($searchname);
	if (!isSet($_REQUEST['newauditor'])) {
	    $newauditor=$studentPicker->findStudentNumber();
	}
    } else { 
	$searchname='';
    }
    $_SESSION['searchname']=$searchname;
 } else {
    $studentPicker->setSearchString($_SESSION['searchname']);
 }

$_SESSION['searchname']=$searchname;


// test if this owner can update this project
$isTutorOwner = checkTutorOwner($dbConn,$prj_id,$tutor_code);
if ( ($isTutorOwner || $isGroupTutor )  && isSet($_REQUEST['bsetgid']) && $newauditor != 0 ) {
  $gids = join(',', $_REQUEST['gid']); 
    $sql= 
       "begin work;"
      ." insert into project_auditor (snummer,prjm_id,gid) \n"
      ."  select $newauditor,prjm_id,grp_num as gid from \n"
      ."(select $newauditor as snummer ,prjm_id,0 as grp_num from prj_tutor where prjm_id=$prjm_id \n"
      ."    union select  $newauditor as snummer,prjm_id,grp_num from prj_tutor where prjm_id=$prjm_id) pt \n"
      ." where grp_num in ($gids) "
      ."    and ($newauditor,prjm_id,grp_num) not in (select snummer,prjm_id,gid from project_auditor);\n"
      ."commit";
    $dbConn->Execute($sql);
    //    $dbConn->log($sql);
    //    $dbConn->log($dbConn->ErrorMsg());
 }
//
pagehead('Add project auditor.');
$page_opening="Add project auditor to a project. prj_id $prj_id milestone $milestone prjm_id $prjm_id";
$nav=new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);

?>
<?=$nav->show()?>
<div id='navmain' style='padding:1em;'>
<p>Add a project auditor to a project /group.</p>
<p>Project auditors have the privilege to access the groups resources such as svn and trac. Use case: extra 
    readers of project artifacts without having to add (empty) project groups with these readers as tutor.
</p>
  <?=$prjSel->getWidget()?>
<?php
$studentPicker->setPresentQuery("select snummer from project_auditor where prjm_id=$prjm_id");
$studentPicker->show();
$sql = "select snummer,achternaam,roepnaam,tussenvoegsel from student where snummer=$newauditor";
$resultSet = $dbConn->Execute($sql);
extract($resultSet->fields,EXTR_PREFIX_ALL,'auditor');
if ($newauditor !=0 ) {
?>
<fieldset><legend>Select groups to audit.</legend>
<form name='set auditgroups' method='post' action='<?=$PHP_SELF?>'>
<p>Choose groups in project <b><?=$afko?> <?=$year?> milestone <?=$milestone?></b> to be audited by <b><?=$auditor_roepnaam?> <?=$auditor_tussenvoegsel?> <?=$auditor_achternaam?>(<?=$auditor_snummer?>)</b>.</p>
<p>Group 0 gives access to all.</p>
<?php
$sql ="select 0 as gid, 'all' as alias union select grp_num as gid, alias from prj_tutor natural join grp_alias where prjm_id=$prjm_id order by gid";
$resultSet = $dbConn->Execute($sql);
if ( $resultSet === false ) {
  die( "<br>Cannot get max grp_num with ".$sql." reason ".$dbConn->ErrorMsg()."<br>");
 } else {
  echo "<table><tr>\n";
  while (!$resultSet->EOF) {
    extract ($resultSet->fields);
    echo "<td><input type='checkbox' name='gid[]' value='$gid' >$gid:$alias</input></td>\n";
    $resultSet->moveNext();
  }      
 }
?>
<td><input type='submit' name='bsetgid' value='Set audit groups'/></td>
</tr></table>
<input type='hidden' name='newauditor' value='<?=$newauditor?>'/>
<input type='hidden' name='prjm_id' value='<?=$prjm_id?>'/>
</form>
</fieldset>
<?php
   }
$sql ="select afko,year,milestone, prjm_id,gid,snummer,achternaam,roepnaam,tussenvoegsel \n".
    " from project_auditor natural join student natural join prj_milestone natural join project\n".
  " where prjm_id=$prjm_id\n".
  " order by year desc,afko,gid,achternaam,roepnaam\n";
//$dbConn->log($sql);
$rainbow= new RainBow(STARTCOLOR,COLORINCREMENT_RED,COLORINCREMENT_GREEN,COLORINCREMENT_BLUE);
?><div align='center'>
<fieldset><legend>The current list of project auditors in the system</legend>
<?php
queryToTableChecked($dbConn,$sql,true,0,$rainbow,-1,'','');
?>
</fieldset>
</div>
</div>
<!-- db_name=<?=$db_name?> -->
<!-- $Id: projectauditor.php 1723 2014-01-03 08:34:59Z hom $ -->
</body>
</html>