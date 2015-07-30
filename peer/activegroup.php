<?php

/* $Id: activegroup.php 1825 2014-12-27 14:57:05Z hom $ */
include_once('./peerlib/peerutils.inc');
include_once('./peerlib/simplequerytable.inc');
include_once('makeinput.inc');
include_once('tutorhelper.inc');
include_once 'navigation2.inc';
require_once 'studentPrjMilestoneSelector.php';
$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
$grp_num = 1;
$prjtg_id = 1;
extract($_SESSION);
$judge = $snummer;
$prjSel = new StudentMilestoneSelector($dbConn, $judge, $prjtg_id);
extract($prjSel->getSelectedData());
$_SESSION['prjtg_id'] = $prjtg_id;
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;
$_SESSION['grp_num'] = $grp_num;

$may_change = hasStudentCap($snummer, CAP_SET_PROJECT_DATA, $prjm_id, $grp_num);

if ($may_change && isSet($_POST['submit_data'])) {
    $long_name = substr(pg_escape_string($_POST['long_name']), 0, 40);
    $alias = substr(pg_escape_string($_POST['alias']), 0, 15);
    $productname = substr(pg_escape_string($_POST['productname']), 0, 128);
    $website = substr(pg_escape_string($_POST['website']), 0, 128);
    $youtube_link = substr(pg_escape_string($_POST['youtube_link']), 0, 128);
    $youtube_icon_url = substr(pg_escape_string($_POST['youtube_icon_url']), 0, 128);
    $sql = "select count(*) from grp_alias where prjtg_id=$prjtg_id";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die('Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql);
    }
    if ($resultSet->fields['count'] == 0) {
        $sql = "insert into grp_alias (alias,long_name,productname,website,youtube_link,prjtg_id,youtube_icon_url)\n"
                . "values('$alias','$long_name','$productname','$website','$youtube_link',$prjtg_id,'$youtube_icon_url')";
    } else {
        $sql = "update grp_alias set alias='$alias',long_name='$long_name',\n"
                . "  productname='$productname',website='$website',youtube_link='$youtube_link'\n"
                . ", youtube_icon_url='$youtube_icon_url'\n"
                . " where prjtg_id=$prjtg_id";
    }
    //    $dbConn->log($sql);
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die('Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql);
    }
}
if (!isSet($_SESSION['prj_id'])) {
    $sql = "select prj_id,milestone, grp_num,prjtg_id from all_prj_tutor join prj_grp using(prjtg_id) where snummer=$snummer\n"
            . "order by prjtg_id desc limit 1";
    $resultSet = $dbConn->Execute($sql);
    if (!$resultSet->EOF) {
        extract($resultSet->fields);
        $_SESSION['prj_id'] = $prj_id;
        $_SESSION['milestone'] = $milestone;
        $_SESSION['grp_num'] = $grp_num;
        $_SESSION['prjtg_id'] = $prjtg_id;
    }
}
$pp = array();
$sql = "select afko,grp_num,rtrim(description) as description,rtrim(coalesce(ga.alias,'g'||grp_num)) as alias,year,\n"
        . " rtrim(productname) as productname, rtrim(long_name) as long_name,\n"
        . " rtrim(website) as website,\n"
        . "rtrim(youtube_link) as youtube_link,"
        . "rtrim(youtube_icon_url) as youtube_icon_url,"
        . "role,prj_id,milestone,\n"
        . " roepnaam,voorvoegsel,achternaam,rtrim(email1) as email1, snummer as gmnumber\n"
        . " from prj_grp pg join all_prj_tutor_y apt using(prjtg_id) \n"
        . "join grp_alias ga using(prjtg_id) join student using(snummer) \n"
        . " join student_role using(prjm_id,snummer)\n"
        . " join project_roles using (prj_id,rolenum)\n"
        . " where pg.prjtg_id=$prjtg_id and rolenum=1 limit 1";
$resultSet = $dbConn->Execute($sql);
//$dbConn->log($sql);
if ($resultSet === false) {
    echo('error getting project data with <strong><pre>' . $sql . '</pre></strong> reason : ' .
    $dbConn->ErrorMsg() . '<BR>');
} else if (!$resultSet->EOF) {
    $pp = array_merge($pp, $resultSet->fields);
    extract($resultSet->fields);
}
// get definition of role 1 for this project
$sql = "select coalesce('That is '||short||': '||role||' for this project','') as role_one \n" .
        "from project_roles where prj_id=$prj_id and rolenum=1";
//$dbConn->log($sql);
$resultSet = $dbConn->Execute($sql);
if ($resultSet !== false && !$resultSet->EOF) {
    extract($resultSet->fields);
    $pp = array_merge($pp, $resultSet->fields);
}
$page_opening = "Data for active group $grp_num";
$page = new PageContainer();
$page->setTitle('Project group data');
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);

ob_start();
tutorHelper($dbConn, $isTutor);

$page->addBodyComponent(new Component(ob_get_clean()));
$page->addBodyComponent($nav);
$pp['prjList'] = $prjSel->getWidget();
$sqlm = "select '<a href=''mailto:'||rtrim(email1)||'''>'||roepnaam||coalesce(' '||voorvoegsel||' ',' ')||achternaam||'</a>' as name,"
        . "role\n"
        . " from prj_grp pg join student using (snummer)\n"
        . "join all_prj_tutor using(prjtg_id)\n"
        . "  join student_role using (prjm_id,snummer)\n"
        . "  join project_roles using(prj_id,rolenum)\n"
        . " where pg.prjtg_id=$prjtg_id order by achternaam,roepnaam";
$pp['memberTable'] = simpleTableString($dbConn, $sqlm);
if (!$resultSet->EOF) {
    $pp = array_merge($pp, $resultSet->fields);
    extract($resultSet->fields);
    $pp['field_alias'] = makeinputfor('alias', $alias, $may_change, 15);
    $pp['field_long_name'] = makeinputfor('long_name', $long_name, $may_change, 40);
    $pp['field_productname'] = makeinputfor('productname', $productname, $may_change, 60);
    $pp['field_website'] = $may_change ? makeinputfor('website', $website, $may_change, 80) : "<a href='$website'>$website</a>";
    $pp['field_youtube_link'] = $may_change ? makeinputfor('youtube_link', $youtube_link, $may_change, 80) : "<a href='$youtube_link'>$youtube_link</a>";
    $icon ="<a href='$youtube_link' target='_blank'><img src='$youtube_icon_url' alt='youtube_icon_url' border='0'/></a>";
            $pp['field_youtube_icon_url'] = $may_change ? $icon.'<br/>'.
            makeinputfor('youtube_icon_url', $youtube_icon_url, $may_change, 80) : $icon;
    $weblink = '';
    if ($website != '') {
        $weblink = '<a href=\'' . $website . '\' target=\'_blank\'>' . $long_name . '</a>';
    }
}
$pp['changeButton'] = $may_change ? "<input type='reset'/> To update, press <input type='submit' name='submit_data' value='submit'/>" : '';

$sqlp = "select pi_description,pi_name,pi_value,interpretation"
        . " from project_attributes_def "
        . "join (select prj_id from all_prj_tutor where prjtg_id=$prjtg_id) apt "
        . "using(prj_id) natural left join ( select * from project_attributes_values where prjtg_id=$prjtg_id) pav";
if (isSet($prjtg_id)) {
    $pp['attributeTable'] = simpleTableString($dbConn, $sqlp);
}

$page->addHtmlFragment('templates/activegroup.html', $pp);
$page->show();
?>
