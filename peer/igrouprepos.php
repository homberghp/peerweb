<?php
/* $Id: igrouprepos.php 1761 2014-05-24 13:17:31Z hom $ */
requireCap(CAP_SUBVERSION);
require_once('simplequerytable.php');
require_once('makeinput.php');
require_once('tutorhelper.php');
require_once 'navigation2.php';
$judge=$snummer;
$sql="select * from student_email where snummer=$judge";
$resultSet=$dbConn->Execute($sql);
if ($resultSet === false) {
 }
if (!$resultSet->EOF) extract($resultSet->fields,EXTR_PREFIX_ALL,'judge');

$page_opening='Group repositories '." for $judge_roepnaam $judge_tussenvoegsel $judge_achternaam ($judge_snummer)";
$page=new PageContainer();
$page->setTitle($page_opening);
$nav=new Navigation($tutor_navtable, basename(__FILE__), $page_opening);
$nav->setInterestMap($tabInterestCount);

$nav->addLeftNavText(file_get_contents('news.html'));
ob_start();
tutorHelper($dbConn,$isTutor);
$page->addBodyComponent(new Component(ob_get_clean()));
$page->addBodyComponent($nav);
ob_start();

?>
<table width='100%'><tr><td valign='top'>
<div style='padding:1em'>
<h2>This page informs you about the repositories to which you have access</h2>
<fieldset><legend>Repositories</legend>
  <?php 
  $sql = "select "
  ."'<a href=\"$svnserver_url'||url_tail||'\">'||mpr.description||'</a>' as repo_link,'{$svnserver_url}'||url_tail as url,\n"
  ." afko as project_name, pr.description \n"
  ." from my_project_repositories mpr join project pr using(prj_id) where snummer=$snummer order by prj_id desc\n";

simpletable($dbConn,$sql,
		"<table width='100%' summary='visited colloquia' ".
		"border='0' >\n");

?>
</fieldset>
</div>
</td></tr></table>
<!-- db_name=<?=$db_name?> -->
<?php
$page->addBodyComponent( new Component(ob_get_clean()));
$page->show();
?>
