<?php
include_once('peerutils.php');
require_once('validators.php');
include_once('tutorhelper.php');
include_once('navigation2.php');
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
extract($prjSel->getSelectedData());
$_SESSION['prjtg_id'] = $prjtg_id;
$_SESSION['prj_id']=$prj_id;
$_SESSION['prjm_id']=$prjm_id;
$_SESSION['milestone']=$milestone;
$_SESSION['grp_num'] = $grp_num;

tutorHelper($dbConn,$isTutor);
pagehead('group photos');
$prjSel->setSubmitOnChange(true);
$prj_id_selector=$prjSel->getSelector();


$page_opening="Group photos for project $afko: $description $year-".($year+1).
    "<br/><span style='font-size:6pt;'> prj_id=$prj_id  milestone $milestone (prjm_id=$prjm_id) group $grp_num (prjtg_id=$prjtg_id) $grp_alias </span>";
$nav=new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$pg = new GroupPhoto($dbConn, $prjtg_id);
//$pg->setWhereConstraint("prjtg_id in (select prjtg_id from prj_grp where snummer=$peer_id)");

?>
<?=$nav->show()?>
<div id='navmain' style='padding:1em;'>
<div class='nav'>
<form method="get" name="project" action="<?=$PHP_SELF;?>">
  Project : <?=$prj_id_selector?><input type='submit' name='get' value='Get'/>
</form>
</div>
  <?=$pg->getGroupPhotos()?>
</div>
<?=$dbConn->getLogHtml()?>
<?php echo "<!-- db_name=".$db_name."-->\n"?>
<!-- $Id: igroupphoto.php 1825 2014-12-27 14:57:05Z hom $-->
</body>
</html>
<?php
?>
