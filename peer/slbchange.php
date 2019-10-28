<?php

requireCap(CAP_ASSIGN_SLB);
require_once('peerutils.php');
require_once 'component.php';
require_once('navigation2.php');

//require_once 'simplequerytable.php';
require_once 'querytotable.php';
require_once 'validators.php';
require_once 'SimpleTableFormatter.php';
require_once 'classSelector.php';

$newhoofdgrp = '';
$oldslb = $slb = 0;
$newclass_id = $oldclass_id = 1;
extract($_SESSION);

if (isSet($_POST['slb']) && preg_match('/^\d+$/', $_POST['slb'])) {
    $_SESSION['slb'] = $slb = $_POST['slb'];
}
if (isSet($_POST['oldslb']) && preg_match('/^\d+$/', $_POST['oldslb'])) {
    $_SESSION['oldslb'] = $oldslb = $_POST['oldslb'];
}

if (isSet($_POST['setslb']) && isSet($slb) && isSet($_POST['studenten'])) {
    $memberset = implode(",", $_POST['studenten']);
    $sql = "update student_email set slb=$slb " .
            "where snummer in ({$memberset})";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die("<br>Cannot update student_email  with " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>");
    }
}
$pp = array();

$page_opening = "Get and set Student Study coach (SLB) by class.";
$page = new PageContainer();
$page->setTitle("Set/check SLB");
$nav = new Navigation($tutor_navtable, basename(__FILE__), $page_opening);
$nav->setInterestMap($tabInterestCount);
$sql_slb = "select achternaam||','||roepnaam||' ['||tutor||']' as name,\n"
        . " snummer as value,faculty_short||'-'||course_short as namegrp \n"
        . " from tutor_join_student tjs left join faculty using(faculty_id)\n"
        . " left join fontys_course fc on (tjs.opl=fc.course)\n"
        . " order by namegrp,faculty,achternaam,roepnaam ";

$sql_oldslb = "select mine,namegrp,name,userid as value from tutor_selector($peer_id) \n"
        . "order by mine,namegrp,name";
$pp['oldslb'] = getOptionListGrouped($dbConn, $sql_oldslb, $oldslb);

$pp['slbList'] = getOptionListGrouped($dbConn, $sql_slb, $slb);


$css = '<link rel=\'stylesheet\' type=\'text/css\' href=\'' . SITEROOT . '/style/tablesorterstyle.css\'/>';
$page->addScriptResource('js/jquery.min.js');
$page->addScriptResource('js/jquery.tablesorter.js');
$page->addHeadText($css);
$page->addJqueryFragment('$("#myTable").tablesorter({widgets: [\'zebra\'],headers: {0:{sorter:false}}});');

//echo "<pre>\n";print_r($_REQUEST); echo"</pre>\n";
$page->addBodyComponent($nav);

$sql = "SELECT '<input type=''checkbox''  name=''studenten[]'' value='''||st.snummer||'''/>' as chk,"
        . "'<a href=''student_admin.php?snummer='||snummer||'''>'||st.snummer||'</a>' as snummer,"
        . "'<img src='''||photo||''' style=''height:24px;width:auto;''/>' as foto,\n"
        . "achternaam||', '||roepnaam||coalesce(' '||tussenvoegsel,'') as naam,pcn,"
        . "t.tutor as slb,"
        . "sclass as klas,"
        . " hoofdgrp,"
        . " cohort,course_short sprogr,studieplan_short as splan,lang,sex as gender,gebdat"
        //. ", land,plaats,pcode\n"
        . " from student_email st \n"
        . "join student_class cl using(class_id)\n"
        . "natural left join studieplan \n"
        . "left join fontys_course fc on(st.opl=fc.course)\n"
        . "left join tutor t on (st.slb=t.userid)\n"
        . "natural left join portrait \n"
        . "where st.slb={$oldslb} "
        . "order by hoofdgrp,st.opl,sclass asc,achternaam,roepnaam";
//simpletable($dbConn,$sql,"<table id='myTable' class='tablesorter' summary='your requested data'"
//		." style='empty-cells:show;border-collapse:collapse' border='1'>");

$tableFormatter = new SimpleTableFormatter($dbConn, $sql, $page);
$tableFormatter->setCheckName('studenten[]');
$tableFormatter->setCheckColumn(0);
$tableFormatter->setTabledef("<table id='myTable' class='tablesorter' summary='your requested data'"
        . " style='empty-cells:show;border-collapse:collapse' border='1'>");
$pp['cTable'] = $tableFormatter;
$page->addHtmlFragment('../templates/slbchange.html', $pp);
$page->show();
?>
 
