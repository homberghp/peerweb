<?php

include_once('./peerlib/peerutils.inc');
requireCap(CAP_TUTOR);
include_once './peerlib/component.php';
include_once('navigation2.inc');

//require_once './peerlib/simplequerytable.inc';
require_once './peerlib/querytotable.inc';
require_once './peerlib/validators.inc';
require_once './peerlib/SimpleTableFormatter.php';
require_once './peerlib/classSelector.php';

$newhoofdgrp = '';
$slb = 0;
$newclass_id = $oldclass_id = 1;
extract($_SESSION);
if (isSet($_REQUEST['oldclass_id'])) {
    $_SESSION['oldclass_id'] = $oldclass_id = $_REQUEST['oldclass_id'];
}
if (isSet($_POST['newclass_id'])) {
    $_SESSION['newclass_id'] = $newclass_id = $_POST['newclass_id'];
}
if (isSet($_POST['update']) && isSet($_POST['studenten'])) {
    $memberset = '\'' . implode("','", $_POST['studenten']) . '\'';
    $sql = "update student set class_id='$newclass_id' " .
            "where snummer in ($memberset)";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die("<br>Cannot update student with " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>");
    }
}

if (isSet($_POST['slb']) && preg_match('/^\d+$/', $_POST['slb'])) {
    //$newslb= preg_replace('/\W+/g','',$_POST['slb']);
    $_SESSION['slb'] = $slb = $_POST['slb'];
}

if (isSet($_POST['setslb']) && isSet($slb) && isSet($_POST['studenten'])) {
    $memberset = '\'' . implode("','", $_POST['studenten']) . '\'';
    $sql = "update student set slb=$slb " .
            "where snummer in ($memberset)";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die("<br>Cannot update student  with " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>");
    }
}
$class_sql = "select distinct student_class.sclass||'#'||class_id||' (#'||coalesce(student_count,0)||')'  as name,\n"
        . "class_id as value, \n"
        . "  trim(faculty_short)||'.'||trim(coalesce(cluster_name,'')) as namegrp, \n"
        . " faculty_short,\n"
        . " case when class_cluster=(select class_cluster from student join student_class using(class_id) where snummer=$peer_id) then 0 else 1 end as myclass "
        . " from student_class "
        . " natural left join class_cluster\n"
        . " left join faculty  using(faculty_id) \n"
        . " left join class_size using(class_id) \n"
        . "order by myclass,namegrp,name";

$pp = array();
$classSelectorClass = new ClassSelectorClass($dbConn,$oldclass_id);
$pp['oldClassOptionsList'] = $classSelectorClass->setSelectorName('oldclass_id')->addConstraint('sort1 < 10 and student_count <>0')->setAutoSubmit(true)->getSelector();

$page_opening = "Get and set Student Study coach (SLB) by class.";
$page = new PageContainer();
$page->setTitle("Set/check SLB");
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$sql_slb = "select achternaam||','||roepnaam||' ['||tutor||']' as name,\n"
        . " snummer as value,faculty_short||'-'||course_short as namegrp \n"
        . " from tutor_join_student tjs left join faculty using(faculty_id)\n"
        . " left join fontys_course fc on (tjs.opl=fc.course)\n"
        . " order by namegrp,faculty,achternaam,roepnaam ";

$pp['slbList'] = getOptionListGrouped($dbConn, $sql_slb, $slb);

$css = '<link rel=\'stylesheet\' type=\'text/css\' href=\'' . SITEROOT . '/style/tablesorterstyle.css\'/>';
$page->addScriptResource('js/jquery.js');
$page->addScriptResource('js/jquery.tablesorter.js');
$page->addHeadText($css);
$page->addJqueryFragment('$("#myTable").tablesorter({widgets: [\'zebra\'],headers: {0:{sorter:false}}});');

//echo "<pre>\n";print_r($_REQUEST); echo"</pre>\n";
$page->addBodyComponent($nav);

$sql = "SELECT '<input type=''checkbox''  name=''studenten[]'' value='''||st.snummer||'''/>' as chk,"
        . "'<a href=''student_admin.php?snummer='||snummer||'''>'||st.snummer||'</a>' as snummer,"
        . "'<img src='''||photo||''' style=''height:24px;width:auto;''/>' as foto,\n"
        . "achternaam||', '||roepnaam||coalesce(' '||voorvoegsel,'') as naam,pcn,"
        . "t.tutor as slb,"
        . "sclass as klas,"
        . " hoofdgrp,"
        . " cohort,course_short sprogr,studieplan_short as splan,lang,sex,gebdat,"
        . " land,plaats,pcode\n"
        . " from student st \n"
        . "join student_class cl using(class_id)\n"
        . "natural left join studieplan \n"
        . "left join fontys_course fc on(st.opl=fc.course)\n"
        . "left join tutor t on (st.slb=t.userid)\n"
        . "natural left join portrait \n"
        . "where class_id='$oldclass_id' "
        . "order by hoofdgrp,st.opl,sclass asc,achternaam,roepnaam";
//simpletable($dbConn,$sql,"<table id='myTable' class='tablesorter' summary='your requested data'"
//		." style='empty-cells:show;border-collapse:collapse' border='1'>");

$tableFormatter = new SimpleTableFormatter($dbConn, $sql, $page);
$tableFormatter->setCheckName('studenten[]');
$tableFormatter->setCheckColumn(0);
$tableFormatter->setTabledef("<table id='myTable' class='tablesorter' summary='your requested data'"
        . " style='empty-cells:show;border-collapse:collapse' border='1'>");
$pp['cTable'] = $tableFormatter;
$page->addHtmlFragment('templates/slb.html', $pp);
$page->show();
?>
 