<?php

/* $Id: activeprojects.php 1761 2014-05-24 13:17:31Z hom $ */
include_once('./peerlib/peerutils.php');
include_once('./peerlib/makeinput.php');
include_once('tutorhelper.php');
include_once 'navigation2.php';
require_once './peerlib/SimpleTableFormatter.php';

$page_opening = "Active projects and websites";
$page = new PageContainer();
$page->setTitle('Set peer roles in projects');
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);

ob_start();
tutorHelper($dbConn, $isTutor);
$page->addBodyComponent(new Component(ob_get_clean()));
$page->addBodyComponent($nav);
$css = '<link rel=\'stylesheet\' type=\'text/css\' href=\'' . SITEROOT . '/style/tablesorterstyle.css\'/>';
$page->addScriptResource('js/jquery.js');
$page->addScriptResource('js/jquery.tablesorter.js');
$page->addHeadText($css);
$page->addJqueryFragment('$("#myTable").tablesorter({widgets: [\'zebra\']});');

$sql = "select distinct prj_id,milestone,afko,year,grp_num,tutor,rtrim(alias) as alias,long_name,productname,\n" .
        "  pg.snummer as gm_snumber,roepnaam||coalesce(' '||voorvoegsel||' ',' ')||achternaam as gm_name,rtrim(email1) as gm_email,\n" .
        "  '<a href='''||website||''' target=''_blank''>'||website||'</a>' as website," .
        "'<a href='''||youtube_link||''' target=''_blank''>'||youtube_link||'</a>' as youtube_link \n" .
        "from prj_grp pg join all_prj_tutor_y apt join grp_alias using (prjtg_id) using(prjtg_id)\n" .
        " join student_role sr on  (sr.prjm_id=apt.prjm_id and pg.snummer=sr.snummer and rolenum=1)\n" .
        " join student s on(pg.snummer=s.snummer)\n" .
        "where website notnull\n" .
        " and now()::date < valid_until\n" .
        " order by year desc,afko,milestone desc,grp_num";
$pp = array();
$tableFormatter = new SimpleTableFormatter($dbConn, $sql, $page);
$tableFormatter->setCheckName('studenten[]');
//$tableFormatter->setCheckColumn( 0 );
$tableFormatter->setTabledef("<table id='myTable' class='tablesorter' summary='your requested data'"
        . " style='empty-cells:show;border-collapse:collapse' border='1'>");
$pp['tab'] = $tableFormatter->getTable();

$page->addHtmlFragment('templates/activeprojects.html', $pp);
$page->show();
?>
