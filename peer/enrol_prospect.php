<?php

requireCap(CAP_ENROL);
require_once 'component.php';
require_once('navigation2.php');
require_once 'querytotable.php';
require_once 'validators.php';
require_once 'classSelector.php';
require_once 'ClassSelectorClass.php';

require_once 'SimpleTableFormatter.php';
require_once 'SpreadSheetWriter.php';
require_once 'maillists.inc.php';
$getAll = isSet($_POST['get']) ? 'checked' : '';
$newclass_id = $oldclass_id = 1;
$hoofdgrp = 'SEBINL2018';
extract($_SESSION);

$pp = array();
$pp['newhoofdgrp'] = '';


$prefix = 'noprefix';

if (isSet($_REQUEST['hoofdgrp'])) {
    $_SESSION['hoofdgrp'] = $hoofdgrp = $_REQUEST['hoofdgrp'];
}
if (isSet($_POST['newclass_id'])) {
    $_SESSION['newclass_id'] = $newclass_id = validate($_POST['newclass_id'], 'integer', 0);
}
$selector_name = 'hoofdgrp';
$hgquery = "select distinct hoofdgrp as name, hoofdgrp as value from prospects order by hoofdgrp";
$oldClassSelector = "<select name='{$selector_name}' id='{$selector_name}' >\n" . getOptionList($dbConn, $hgquery, $hoofdgrp)
        . "</select>\n";

if (isSet($_POST['update']) && isSet($_POST['studenten'])) {
    $memberset = implode(",", $_POST['studenten']);
    $sql = "begin work;\n"
            . "update prospects set class_id={$newclass_id } where snummer in ({$memberset}) and pcn notnull and email1 notnull;\n"
            . "with enrol as (delete from prospects p "
            . "where snummer in ($memberset) and pcn notnull and email1 notnull and not exists(select 1 from student_email where snummer=p.snummer)  returning * )"
            . "insert into student_email select * from enrol;\n"
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

    $sql = "update student_email set hoofdgrp=substr('$newhoofdgrp',1,10) " .
            "where snummer in ($memberset)";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die("<br>Cannot update student  with " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>");
    }
}


$pp['mailalias'] = $prefix . '@fontysvenlo.org';
$pp['oldClassSelector'] = $oldClassSelector; //oclassSelectorClass->setSelectorName('oldclass_id')->addConstraint('student_count <>0')->setAutoSubmit(true)->getSelector();

$nclassSelectorClass = new ClassSelectorClass($dbConn, $newclass_id);
$pp['newClassSelector'] = $nclassSelectorClass->setSelectorName('newclass_id')->addConstraint('sort1=1')->getSelector();

$page = new PageContainer();
$page_opening = "Enrol Prospect Students from SV05 into Peerweb.";
$page->setTitle($page_opening);
$nav = new Navigation($tutor_navtable, basename(__FILE__), $page_opening);
$nav->setInterestMap($tabInterestCount);

$page->addBodyComponent($nav);
$css = '<link rel=\'stylesheet\' type=\'text/css\' href=\'' . SITEROOT . '/style/tablesorterstyle.css\'/>';
$page->addScriptResource('js/jquery.min.js');
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
$pp['plist']="<a href='prospectpresenceform.php?hoofdgrp={$hoofdgrp}'>presencelist</a>";
$filename = '/home/maillists/' . $prefix . '.maillist';
$pp['filetime'] = 'never';
if (file_exists($filename)) {
    $pp['filetime'] = date("Y-m-d H:i:s", filemtime($filename));
}
$sql = "SELECT '<input type=''checkbox''  name=''studenten[]'' value='''||st.snummer||'''  class=''checker'' onChange=''updateCount()'' />' as chk,"
        . "'<a href=''prospect_admin.php?snummer='||snummer||'''>'||st.snummer||'</a>' as snummer,"
        . "'<img src='''||photo||''' style=''height:24px;width:auto;''/>' as foto,\n"
        . "achternaam||', '||roepnaam||coalesce(' '||tussenvoegsel,'') as naam,pcn,"
        . "email1 as email,hoofdgrp,sclass,cohort,course_short sprogr,studieplan_short as splan,lang,sex,gebdat,"
        . " land,plaats,pcode\n"
        . " from prospects st "
        . "left join student_class cl using(class_id)\n"
        . "natural left join studieplan \n"
        . "left join fontys_course fc on(st.opl=fc.course)\n"
        . " natural join prospect_portrait\n"
        . "where hoofdgrp='{$hoofdgrp}' "
        . " and not exists (select 1 from student_email where snummer=st.snummer)"
        . "order by hoofdgrp,opl,sclass asc,achternaam,roepnaam";
$tableFormatter = new SimpleTableFormatter($dbConn, $sql, $page);
$pp['cardsLink'] = "<a href='classtablecards.php?rel=prospects&hoofdgrp={$hoofdgrp}'>table cards for prospects</a>";
$tableFormatter->setCheckName('studenten[]');
$tableFormatter->setCheckColumn(0);
$tableFormatter->setTabledef("<table id='myTable' class='tablesorter' summary='your requested data'"
        . " style='empty-cells:show;border-collapse:collapse' border='1'>");

$pp['classTable'] = $tableFormatter->getTable();

$page->addHtmlFragment('../templates/enrol_prospect.html', $pp);
$page->show();
?>
