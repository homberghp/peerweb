<?php

include_once('./peerlib/peerutils.php');
requireCap(CAP_TUTOR);
include_once './peerlib/component.php';
include_once('navigation2.php');
require_once './peerlib/querytotable.php';
require_once './peerlib/validators.php';
//require_once './peerlib/classSelector.php';
require_once 'ClassSelectorClass.php';

require_once './peerlib/SimpleTableFormatter.php';
require_once 'SpreadSheetWriter.php';
require_once 'maillists.inc.php';
$getAll = isSet($_POST['get']) ? 'checked' : '';
$newclass_id = $oldclass_id = 1;
extract($_SESSION);

$pp = array();
$pp['newhoofdgrp'] = '';


$prefix = 'noprefix';

if (isSet($_REQUEST['oldclass_id'])) {
    $_SESSION['oldclass_id'] = $oldclass_id = $_REQUEST['oldclass_id'];
}
if (isSet($_POST['newclass_id'])) {
    $_SESSION['newclass_id'] = $newclass_id = $_POST['newclass_id'];
}
if (isSet($oldclass_id)) {
    $sql = "select trim(faculty_short) as faculty_short,trim(sclass) as sclass,\n"
            . "lower(rtrim(faculty_short)||'.'||rtrim(sclass)) as prefix\n"
            . " from student_class join faculty using(faculty_id) where class_id=$oldclass_id";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet !== false) {
        extract($resultSet->fields);
    }
}

$sqlhead = "select distinct snummer,"
        . "achternaam||rtrim(coalesce(', '||tussenvoegsel,'')::text) as achternaam ,roepnaam, "
        . "pcn,gebdat as birth_date,t.tutor as slb,rtrim(email1) as email1,\n"
        . "studieplan_short as studieplan,sclass,hoofdgrp ,\n"
        . "straat,huisnr,plaats,phone_gsm,phone_home\n"
        . " from \n";
$sqltail = " join student_class using(class_id) left join tutor t on (s.slb=t.userid)\n"
        . " left join studieplan using(studieplan)\n"
        . "where class_id='$oldclass_id' order by achternaam,roepnaam";

$fdate = date('Y-m-d');
$filename = 'class_list_' . $faculty_short . '_' . $sclass . '-' . $fdate;

$spreadSheetWriter = new SpreadSheetWriter($dbConn, $sqlhead . ' student s ' . $sqltail);

$spreadSheetWriter->setTitle("Class list  $faculty_short $sclass $fdate")
        ->setLinkUrl($server_url . $PHP_SELF . '?oldclass_id=' . $oldclass_id)
        ->setFilename($filename)
        ->setAutoZebra(true);

$spreadSheetWriter->processRequest();
$pp['spreadSheetWidget'] = $spreadSheetWriter->getWidget();

//pagehead2( 'Class administration', $scripts );
if (isSet($_POST['update']) && isSet($_POST['studenten'])) {
    $memberset = implode(",", $_POST['studenten']);
    $sql = "begin work;\n"
            . "update student set class_id=$newclass_id where snummer in ($memberset);\n"
            . "commit;";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die("<br>Cannot update student with " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>");
    }
    createGenericMaillistByClassid($dbConn, $oldclass_id);
    createGenericMaillistByClassid($dbConn, $newclass_id);
}

if (isSet($_POST['newhoofdgrp'])) {
    //$newhoofdgrp= preg_replace('/\W+/g','',$_POST['newhoofdgrp']);
    $_SESSION['newhoofdgrp'] = $newhoofdgrp = $_POST['newhoofdgrp'];
}
if (isSet($_POST['sethoofdgrp']) && isSet($newhoofdgrp) && isSet($_POST['studenten'])) {
    $memberset = '\'' . implode("','", $_POST['studenten']) . '\'';

    $sql = "update student set hoofdgrp=substr('$newhoofdgrp',1,10) " .
            "where snummer in ($memberset)";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die("<br>Cannot update student  with " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>");
    }
}


if (isSet($_POST['maillist'])) {
    createGenericMaillistByClassid($dbConn, $oldclass_id);
}
$pp['mailalias'] = $prefix . '@fontysvenlo.org';
$oclassSelectorClass = new ClassSelectorClass($dbConn, $oldclass_id);
$pp['oldClassSelector'] = $oclassSelectorClass->setSelectorName('oldclass_id')->addConstraint('student_count <>0')->setAutoSubmit(true)->getSelector();

$nclassSelectorClass = new ClassSelectorClass($dbConn, $newclass_id);
$pp['newClassSelector'] = $nclassSelectorClass->setSelectorName('newclass_id')->getSelector();

$page = new PageContainer();
$page_opening = "Move students between student_class.";
$page->setTitle($page_opening);
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);

$page->addBodyComponent($nav);
$css = '<link rel=\'stylesheet\' type=\'text/css\' href=\'' . SITEROOT . '/style/tablesorterstyle.css\'/>';
$page->addScriptResource('js/jquery.js');
$page->addScriptResource('js/jquery.tablesorter.js');
$page->addHeadText($css);
$page->addJqueryFragment('$("#myTable").tablesorter({widgets: [\'zebra\'],headers: {0:{sorter:false}}});
   var table = $("#myTable");
   table.bind("sortEnd",function() { 
    var i = 0;
    table.find("tr:gt(0)").each(function(){
        $(this).find("td:eq(0)").text(i);
        i++;
    });
});  ');

$filename = '/home/maillists/' . $prefix . '.maillist';
$pp['filetime'] = 'never';
if (file_exists($filename)) {
    $pp['filetime'] = date("Y-m-d H:i:s", filemtime($filename));
}
$sql = "SELECT '<input type=''checkbox''  name=''studenten[]'' value='''||st.snummer||''' class=''checker'' onChange=''updateCount()''/>' as chk,"
        . "'<a href=''student_admin.php?snummer='||snummer||'''>'||st.snummer||'</a>' as snummer,"
        . "'<img src='''||photo||''' style=''height:24px;width:auto;''/>' as foto,\n"
        . "achternaam||', '||roepnaam||coalesce(' '||tussenvoegsel,'') as naam,pcn,"
        . "email1 as email,t.tutor as slb,hoofdgrp,cohort,course_short sprogr,studieplan_short as splan,lang,sex,gebdat,"
        . " land,plaats,pcode\n"
        . " from student st "
        . "left join student_class cl using(class_id)\n"
        . "natural left join studieplan \n"
        . "left join fontys_course fc on(st.opl=fc.course)\n"
        . "left join tutor t on (st.slb=t.userid)\n"
        . " natural join portrait\n"
        . "where class_id='$oldclass_id' "
        . "order by hoofdgrp,opl,sclass asc,achternaam,roepnaam";
$tableFormatter = new SimpleTableFormatter($dbConn, $sql, $page);
$tableFormatter->setCheckName('studenten[]');
$tableFormatter->setCheckColumn(0);
$tableFormatter->setTabledef("<table id='myTable' class='tablesorter' summary='your requested data'"
        . " style='empty-cells:show;border-collapse:collapse' border='1'>");

$pp['classTable'] = $tableFormatter->getTable();

$page->addHtmlFragment('templates/classmaker.html', $pp);
$page->show();
?>
