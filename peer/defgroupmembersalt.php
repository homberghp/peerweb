<?php

requireCap(CAP_TUTOR);
include_once('navigation2.php');
require_once 'prjMilestoneSelector2.php';
require_once 'simplequerytable.php';
require_once 'querytotable.php';
require_once 'validators.php';
require_once 'SimpleTableFormatter.php';

$newgrp_num = $oldgrp_num = 1;
$newprjtg_id = $oldprjtg_id = 1;
extract($_SESSION);
$old_prjm_id = $prjm_id;
$pp = array();
$pp['prjSel'] = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
extract($pp['prjSel']->getSelectedData());

$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;

if ($prjm_id != $old_prjm_id) {
    $sql = "select min(prjtg_id) as oldprjtg_id from prj_tutor where prjm_id=$prjm_id";
    $resultSet = $dbConn->Execute($sql);
    $oldprjtg_id = $resultSet->fields['oldprjtg_id'];
}



if (isSet($_REQUEST['oldprjtg_id'])) {
    $_SESSION['oldprjtg_id'] = $oldprjtg_id = $_REQUEST['oldprjtg_id'];
}

if (isSet($_POST['newprjtg_id'])) {
    $pp['newprjtg_id'] = $_SESSION['newprjtg_id'] = $newprjtg_id = $_POST['newprjtg_id'];
}
$pp['newprjtg_id'] = $_SESSION['newprjtg_id'];
$pp['oldprjtg_id'] = $_SESSION['oldprjtg_id'];
if (isSet($_POST['update']) && isSet($_POST['studenten'])) {
    $memberset = '\'' . implode("','", $_POST['studenten']) . '\'';
    $sql0 = "select grp_num from prj_tutor where prjtg_id=$newprjtg_id";
    $resultSet0 = $dbConn->Execute($sql0);
    if ($resultSet0 === false) {
        die("<br>Cannot read grp_num with " . $sql0 . " reason " . $dbConn->ErrorMsg() . "<br>");
    }
    $grp_num = $resultSet0->fields['grp_num'];
    $sql = "BEGIN work;\n" .
            "DELETE FROM assessment where prjtg_id=$oldprjtg_id \n" .
            " AND (judge IN ($memberset) OR contestant IN ($memberset));\n" .
            "UPDATE prj_grp set prj_grp_open=false,prjtg_id=$newprjtg_id where prjtg_id=$oldprjtg_id \n" .
            " AND snummer IN ($memberset);\n" .
            "update prj_milestone set prj_milestone_open=false where prjm_id=$prjm_id;\n" .
            "COMMIT";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die("<br>Cannot update project groups with " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>");
    }
} else if (isSet($_POST['delete']) && isSet($_POST['studenten'])) {
    $memberset = '\'' . implode("','", $_POST['studenten']) . '\'';
    $sql = "BEGIN work;\n" .
            "DELETE FROM assessment where prjtg_id=$oldprjtg_id \n" .
            " AND (judge IN ($memberset) OR contestant IN ($memberset));\n" .
            "delete from prj_grp where prjtg_id=$oldprjtg_id \n" .
            " AND snummer IN ($memberset);\n" .
            "update prj_milestone set prj_milestone_open=false where prjm_id=$prjm_id;\n" .
            "COMMIT";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die("<br>Cannot delete project groups with " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>");
    }
}
$grp_sql = "select afko||'.'||year||':'||'g'||to_char(pt.grp_num,'FM09')||': '||coalesce(grp_name,'')||' '||"
        . "tutor||' ('||pt.prjtg_id||') count='||coalesce(size,'0') as name,\n"
        . "pt.prjtg_id as value,  afko||'.'||year as namegrp\n"
        . "from project p join prj_milestone pm on(p.prj_id=pm.prj_id) \n"
        . "join prj_tutor pt on(pt.prjm_id=pm.prjm_id) \n"
        . " join tutor t on (t.userid=pt.tutor_id)\n"
        . "left join prjtg_size gs on(gs.prjtg_id=pt.prjtg_id) \n"
        . "left join grp_alias ga on (pt.prjtg_id=ga.prjtg_id) where pt.prjm_id =$prjm_id order by pt.grp_num";
$pp['oldGroupOptionsList'] = getOptionListGrouped($dbConn, $grp_sql, $oldprjtg_id);
$pp['newGroupOptionList'] = getOptionListGrouped($dbConn, $grp_sql, $newprjtg_id);

$page = new PageContainer();
$page_opening = "Move students between project groups.";
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


$getAll = '';
//$getAll = isSet($_REQUEST['get']) ? 'checked' : '';
$sql = "SELECT '<input type=''checkbox''  name=''studenten[]'' value='''||st.snummer||''' $getAll/>' as chk,\n"
        . "'<a href=''student_admin.php?snummer='||snummer||'''>'||st.snummer||'</a>' as snummer,"
        . "'<img src='''||photo||''' style=''height:24px;width:auto;''/>' as foto,\n"
        . "achternaam||coalesce(', '||tussenvoegsel,'') as achternaam,\n"
        . "roepnaam,cl.sclass as klas,"
        . "cohort,"
        . "st.opl as opl_code"
        . ",lang"
        . ",sex"
        . ",tutor as slb"
        . ",gebdat,"
        . "email1,"
        //. "email2,"
        . "hoofdgrp "
        //. ",studieplan_omschrijving as studieplan  "
        //. ",plaats,straat||coalesce(' '||huisnr,'') as adres,pcode,land\n"
        . " from student st "
        . "join student_class cl using(class_id)\n"
        . "join prj_grp pg using (snummer)\n"
        . "left join fontys_course fc on(st.opl=fc.course)\n"
        . "left join studieplan sp using(studieplan)\n"
        . "left join tutor t on (st.slb=t.userid)\n"
        . "left join alt_email using(snummer)\n"
        . "natural join portrait \n"
        . "where prjtg_id=$oldprjtg_id "
        . "order by hoofdgrp,opl_code,sclass asc,achternaam,roepnaam";
$dbConn->log($sql);
$tableFormatter = new SimpleTableFormatter($dbConn, $sql, $page);
$tableFormatter->setCheckName('studenten[]');
$tableFormatter->setCheckColumn(0);
$tableFormatter->setTabledef("<table id='myTable' class='tablesorter' summary='your requested data'"
        . " style='empty-cells:show;border-collapse:collapse' border='1'>");

$pp['memberTable'] = $tableFormatter->getTable();
$page->addHtmlFragment('templates/defgroupmembersalt.html', $pp);

$page->show();
?>
