<?php
requireCap(CAP_SYSTEM);
  /* $Id: reverseView.php 1825 2014-12-27 14:57:05Z hom $ */
require_once('tutorhelper.php');
require_once 'navigation2.php';
require_once 'GroupPhoto.class.php';
require_once 'studentPrjMilestoneSelector.php';
$prj_id=1;
$milestone=1;
$prjm_id = 0;
$grp_num=1;
$prjtg_id=1;
extract($_SESSION);
$judge=$snummer;
$prjSel=new StudentMilestoneSelector($dbConn,$judge,$prjtg_id);
$prjSel->setExtraConstraint(" and prjtg_id in (select distinct prjtg_id from assessment) ");
extract($prjSel->getSelectedData());
$_SESSION['prjtg_id'] = $prjtg_id;
$_SESSION['prj_id']=$prj_id;
$_SESSION['prjm_id']=$prjm_id;
$_SESSION['milestone']=$milestone;
$_SESSION['grp_num'] = $grp_num;

// get data stored in session or added to session by helpers
$replyText='';
$script=
$lang='nl';
//echo "$user<br/>\n";

$sql="select * from student_email where snummer=$judge";
$resultSet=$dbConn->Execute($sql);
if ($resultSet === false) {
    print "error fetching judge data with $sql : ".$dbConn->ErrorMsg()."<br/>\n";
 }
if (!$resultSet->EOF) extract($resultSet->fields,EXTR_PREFIX_ALL,'judge');
$lang=strtolower($judge_lang);
$page_opening="Assessment entry form for $judge_roepnaam $judge_tussenvoegsel $judge_achternaam ($judge_snummer)";
$page=new PageContainer();
$page->setTitle('Peer assessment entry form');
$page->addHeadText("<script language='JavaScript' type='text/javascript'>
/**
 * validate input
 */
function validateGrade(el) {
  var rex;
  /* el is the element with the value to be tested
   */
  var locvar = el.value;
  el.value = locvar;
  rex = locvar.search(/^[0-9]{1,2}$/);
  if (rex == -1 ) {
    alert(el.value+': Only digits (and whole numbers) are allowed!');
    return false;
  }
  if ( locvar < 1 || locvar > 10 ) {
    alert(el.value + 
	  ' is not a correct grade, use a whole figure between 1 and 10');
    return false;
  }
  return true;
}
</script>");
$nav=new Navigation($tutor_navtable, basename(__FILE__), $page_opening);
$nav->setInterestMap($tabInterestCount);
$nav->addLeftNavText(file_get_contents('news.html'));
ob_start();
tutorHelper($dbConn,$isTutor);
$page->addBodyComponent(new Component(ob_get_contents()));
ob_clean();
$page->addBodyComponent($nav);
ob_start();

// see if there is a reopen request
if($isTutor && isSet($_REQUEST['reopen'])) {

    $sql="begin work;\n".
	"update prj_grp set prj_grp_open=true,written=false where prjtg_id=$prjtg_id and snummer=$judge ;\n".
	"update prj_tutor set prj_tutor_open=true,assessment_complete=false where prjtg_id=$prjtg_id;\n".
	"update prj_milestone set prj_milestone_open=true where prjm_id=$prjm_id;\n".
	"commit;";
    $resultSet=$dbConn->Execute($sql);
    if ( $resultSet === false ) {
	die( "<br>Cannot update prj_grp table with \"".$sql .'", cause '.$dbConn->ErrorMsg()."<br>");
    }
    //    $dbConn->log('reopen');
 }

//echo "2 found $prj_id, $milestone,$judge for tutor</br>";

// test of voting is still open for this group
$grp_open=grpOpen2($dbConn,$judge,$prjtg_id);
//$dbConn->log("group open test $grp_open <br/>");
if ($grp_open && isSet($_POST['peerdata'])) {
    if ($_POST['peerdata'] == 'grade' && isSet($_POST['grade'])) {
	// get grp
	$sql = "select distinct grp_num from assessment where\n".
	    " prjtg_id=$prjtg_id and judge=$judge";
	//	$dbConn->log( $sql);
	$resultSet=$dbConn->Execute($sql);
	if ($resultSet === false) {
	    print 'error getting prjtg_id with $sql, reason '.$dbConn->ErrorMsg().'<br/>';
	}
	$grp_num= $resultSet->fields['grp_num'];
	$continuation='';
	$sql="begin work;\n";
	$c=count($_POST['criterium']);
	for ( $i=0; $i < $c ; $i++ ){
	    $contestant= $_POST['contestant'][$i];
	    $criterium= $_POST['criterium'][$i];
	    $grade =  $_POST['grade'][$i];
	    $grade = ctype_digit($grade)?round($grade):0;
	    $grade = ($grade!=0)?$grade:10;
	    // limit grade
	    $grade = max($grade,1);
	    $grade = min($grade,10);
	    $sql .= $continuation."update assessment set grade=$grade where\n".
		"prjtg_id=$prjtg_id and \n".
		"contestant=$contestant and judge=$judge and \n".
		"criterium=$criterium;\n";
	    $continuation=";\n";
	}
	$sql .="insert into assessment_commit values($judge,$prj_id,$milestone,now());\n"
	  ."update prj_grp set written=true,prj_grp_open=false where prjtg_id=$prjtg_id and snummer=$judge;\n"
	  ."commit;";
	//	$dbConn->log( $sql);
	$resultSet=$dbConn->Execute($sql);
	if ($resultSet === false) {
	    print 'error updating: '.$dbConn->ErrorMsg().'<BR>';
	} else {
	    $replyText= '<span style=\'color:#080;font-weight:bold\'>'.$langmap['thanks'][$lang].'</span>';
	}
	// now check if group needs to be closed
	$sql = "select should_close from should_close_group_tutor where prjtg_id=$prjtg_id";
	//	$dbConn->log($sql);
	$resultSet=$dbConn->Execute($sql);
	if ($resultSet === false) {
	    print 'error getting zerocount with $sql: '.$dbConn->ErrorMsg().'<BR>';
	}
	//	if (!$resultSet->EOF) $dbConn->log(" must close=".$resultSet->fields['should_close']);
	if ( !$resultSet->EOF  && ($resultSet->fields['should_close'] =='t')) {
		// close group
	  //	  $dbConn->log('close group '.$prjtg_id);
	  $sql ="update prj_tutor pt set prj_tutor_open = false,assessment_complete=true where prjtg_id=$prjtg_id;\n";
	  $resultSet=$dbConn->Execute($sql);
	  if ($resultSet === false) {
	    print 'error closing prj_grp with $sql '.$dbConn->ErrorMsg().'<BR>';
	  }
	  
	  $sql = "select email1 as altemail from project_tutor_owner where prj_id=$prj_id";
	  $resultSet=$dbConn->Execute($sql);
	  if ($resultSet === false) {
	    print 'error getting tutor_owner email data for closing prj_grp with $sql '.$dbConn->ErrorMsg().'<BR>';
	  } else {
	    extract($resultSet->fields);
	  }
	  // and mail tutor
	  $sql="select  email1 as email,roepnaam,achternaam,tussenvoegsel,afko,description,grp_num \n".
	    "from tutor join student_email on(userid=snummer) join prj_tutor using(tutor) \n".
	    "join project using(prj_id) \n".
	    "where prjtg_id=$prjtg_id";
	  $resultSet=$dbConn->Execute($sql);
	  if ($resultSet === false) {
	    print 'error getting tutor email data for closing prj_grp with $sql '.$dbConn->ErrorMsg().'<BR>';
	  } else {
	    extract($resultSet->fields);
	    $achternaam=trim($achternaam);
	    $roepnaam=trim($roepnaam);
	    $tussenvoegsel=trim($tussenvoegsel);
	    $to=trim($email);
	    $subject="The assessment is complete for project $afko group $grp_num milestone $milestone";
	    $body="Beste $roepnaam $tussenvoegsel $achternaam,\n\n".
	      "Alle studenten van groep $grp_num in project $afko ($description)hebben ".
	      "hun beoordeling ingegeven.\n".
	      "U kunt de gegevens op de bekende plaats ".
	      "($server_url$root_url/groupresult.php?prjtg_id=".
	      "$prjtg_id) inzien.\n".
	      "U ontvangt dit bericht omdat u als tutor staat geregistreerd voor deze groep.\n".
	      "---\nMet vriendelijke groet,\nHet peerassessment systeem";
	    $headers = "Reply-To: hom@fontysvenlo.org\n";
	    dopeermail($to,$subject,$body,$headers,$altemail);
	    //	    $dbConn->log("email $body");
	  }
	  // and mail other members
	  $sql ="select roepnaam,tussenvoegsel,achternaam,email1 from student_email left join alt_email using(snummer)\n".
	    " join prj_grp using (snummer) where prj_id=$prj_id and milestone=$milestone and grp_num='$grp_num'";
	  $resultSet=$dbConn->Execute($sql);
	  if ($resultSet === false) {
	    print 'error getting student_email email data for closing prj_grp with $sql '.$dbConn->ErrorMsg().'<BR>';
	  } else {
	    $sroepnaam ='';
	    $to ='';
	    $continue ='';
	    while ( !$resultSet->EOF ) {
	      extract($resultSet->fields);
	      $sroepnaam .= $continue .trim($roepnaam);
	      $to .= $continue . trim($email1);
	      $continue =', ';
	      $resultSet->moveNext();
	    }
	    $subject="The assessment is complete for project $afko group $grp_num milestone $milestone";
	    $body="Beste ".$sroepnaam.",\n\n".
	      "Alle studenten van groep $grp_num in project $afko ($description) hebben ".
	      "hun beoordeling ingegeven.\n".
	      "Je kunt de gegevens bekijken op de bekende plaats ".
	      "(https://peerweb.fontysvenlo.org/iresult.php) inzien.\n".
	      "---\nMet vriendelijke groet,\nHet peerassessment systeem";
	    $headers = "Reply-To: hom@fontysvenlo.org\n";
	    dopeermail($to,$subject,$body,$headers,$altemail);
	  }
	  
	}
    }
 }
  
// after processing build (new) page
// first assure that grp_num is (still) open
$grp_open=grpOpen2($dbConn,$judge,$prjtg_id);
$sql="select count(*) as assessment_count from assessment where judge=$judge";
//$dbConn->log($sql);
$resultSet=$dbConn->Execute($sql);
if ($resultSet=== false) {
    echo ("Cannot get assessment data with <pre>$sql</pre> Cause: ".$dbConn->ErrorMsg()."\n");
}
extract($resultSet->fields);
if ( $assessment_count != 0 ) {
  $widget = $prjSel->getWidget();
 } else {
  $widget= "<h1>Sorry, you are not enlisted for an assessment</h1>";
 }
//
$judge_grp=$grp_num; 
?>
<div id="content" style='padding:1em;'>
  <?=$prjSel->getWidget()?>
<?php
if (!$prjSel->isEmptySelector()) {
?><div class='navleft selected' style='padding-left:0pt;'>
<?php
if ($grp_open ) $gradetype=$langmap['gradetype'][$lang];
 else $gradetype=$langmap['closed'][$lang];
?>

<fieldset class="control">
<legend>Assessment form</legend>
<h2 align='center'>Assessment for <?=$afko?> <?=$year?> <?=$description?> <br/>group <?=$grp_num?> (<?=$grp_alias?>)</h2>
<form method="post" name="assessment "action="<?=$PHP_SELF?>">
<h4 align='center'><?=$gradetype?></h4>
<?php
    //    echo "post 6 prj_id_milestone = $prj_id:$milestone<br/>"; 
    // show photos of group members
$pg=new GroupPhoto($dbConn, $prjtg_id);
 $pg->setWhereConstraint(" not snummer=$snummer ");
 echo $pg->getGroupPhotos();
?>
<table align='center' class='navleft'>
<tr><th><?=$langmap['criteria'][$lang]?></th>
<th><?=$langmap['verklaring'][$lang]?></th></tr>
<?php
$criteria=getCriteria($prjm_id);
$rainbow= new RainBow(STARTCOLOR,COLORINCREMENT_RED,COLORINCREMENT_GREEN,COLORINCREMENT_BLUE);
criteriaList($criteria, $lang, $rainbow );
?>
</table>
<table align='center' class='tabledata' border='1'>
<?php 
if ($grp_open) { 
?>
<tr><th></th><td align='right' colspan='<?=2+count($criteria)?>'>
<?=$replyText?>
<input type='reset' name='resetlow' value='Reset form'/></td></tr>
<?php
}
?>
<?php

if ($isTutor) {
  $tutor_opener="<fieldset style='background:#fff'>
	<legend>For tutors</legend>
	If you are a tutor you could use this page and the next to enter a participant's data, or just simply assume any participant's role.
	  <form name='reopenform' method='post' action='$PHP_SELF'>
	  <input type='hidden' name='prjtg_id' value='$prjtg_id'/>
	  <input type='hidden' name='judge' value='$judge'/>
          To let this person of a group correct his or her values, re-open the assessment for the group by clicking this button.
	  <input type='submit' name='reopen' value='Re open'/>
	  </form>
	</fieldset>";
 } else {
  $tutor_opener='<br/>';
 }

$sql = "SELECT judge,roepnaam||coalesce(' '||tussenvoegsel,'')||' '||achternaam||coalesce(' ('||role||')','') as naam ,prj_id,\n".
      "grp_num,criterium,milestone,grade from judge_assessment\n".
      " natural left join student_role natural left join project_roles \n".
    "where contestant=$judge and prjtg_id=$prjtg_id \n".
    "order by achternaam,judge,criterium";
//$dbConn->log($sql);
groupAssessmentTable($dbConn,$sql,$grp_open,$criteria,$lang,$rainbow);

?>
<?php 
if ($grp_open) { 
?>
<tr><td><input type='hidden' name='peerdata' value='grade'/>
<input type='hidden' name='prjtg_id' value='<?=$prjtg_id?>'/>
</td>
<td align='right' colspan='<?=2+count($criteria)?>'><input type='submit' name='submit'/>
</td></tr>
<?php 
 } 
?>
</table>
</form>
<?=$tutor_opener?>
    </fieldset>
</div>
</div>
<!-- db_name=<?=$db_name?> $Id: reverseView.php 1825 2014-12-27 14:57:05Z hom $ -->
<?php
    }
$nav->addBodyComponent( new Component(ob_get_contents()));
ob_clean();
// for some tests
ob_start();
// echo "<br/> _POST<br/>";
// var_dump($_POST);
ob_clean();
$nav->addBodyComponent( new Component($dbConn->getLogHtml())); 
$page->show();
?>
