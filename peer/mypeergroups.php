<?php
/* $Id: mypeergroups.php 1761 2014-05-24 13:17:31Z hom $ */
include_once('./peerlib/peerutils.inc');
include_once('./peerlib/simplequerytable.inc');
include_once('makeinput.inc');
include_once('tutorhelper.inc');
include_once 'navigation2.inc';
$judge = $snummer;
$sql = "select * from student where snummer=$judge";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    print "error fetching judge data with $sql : " . $dbConn->ErrorMsg() . "<br/>\n";
}
if (!$resultSet->EOF)
    extract($resultSet->fields, EXTR_PREFIX_ALL, 'judge');

$page_opening = 'The groups of ' . "$judge_roepnaam $judge_voorvoegsel $judge_achternaam ($judge_snummer)";
$page = new PageContainer();
$page->setTitle($page_opening);
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);

$nav->addLeftNavText(file_get_contents('news.html'));
ob_start();
tutorHelper($dbConn, $isTutor);
$page->addBodyComponent(new Component(ob_get_clean()));
$page->addBodyComponent($nav);
if (hasCap(CAP_TUTOR)) {
    $sql = "select year,'<a href=\"grouplist.php?prjm_id='||prjm_id||'\" target=\"_blank\">'||afko||'.'||milestone||'</a>' as \"project/m\",\n"
            . "description,prj_id,milestone,milestone_name,grp_name as group_name,prjtg_id,grp_name,coalesce(alias,'G'||grp_num) as group_alias,\n"
            . " long_name as group_description,tutor as group_tutor,tutor as project_owner"
            . " from prj_grp join all_prj_tutor using(prjtg_id) \n"
            . " where snummer=$snummer order by year desc,prj_id,afko,milestone";
} else {
    $sql = "select year,prjm_id,description,prj_id,milestone,milestone_name,\n"
            . "grp_num,prjtg_id,grp_name as group_name,coalesce(alias,'G'||grp_num) as group_alias,\n"
            . " long_name as group_description,tutor as group_tutor,tutor_owner as project_owner"
            . " from prj_grp join all_prj_tutor using(prjtg_id) \n"
            . " where snummer=$peer_id order by year desc,prj_id,afko,milestone";
}
//echo "<pre>$sql</pre>";
ob_start();
?>
<table width='100%'><tr><td valign='top'>
            <div style='padding:1em'>
                <h2>This page informs you about your peerweb project group memberships</h2>
                <fieldset><legend>Group membership</legend>
                    <a href='membershipreport.php' target='_blank'>Print a report in pdf</a>
                    <?php
                    $resultSet = $dbConn->Execute($sql);
                    if ($resultSet === false) {
                        $dbConn->log('Error ' . $dbConn->ErrorMsg() . " with " . $sql);
                    } else {
                        simpletable($dbConn, $sql, "<table summary='group memership' " .
                                "border='1'  style='border-collapse:collapse;background:white;border:1px 1px;' >\n");
                    }
                    ?>
                </fieldset>
            </div>
        </td></tr></table>
<!-- db_name=<?= $db_name ?> -->
<?php
$page->addBodyComponent(new Component(ob_get_clean()));
$page->show();
?>
