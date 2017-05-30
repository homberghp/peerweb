<?php

//session_start();
include_once('./peerlib/peerutils.php');
include_once('tutorhelper.php');
include_once('./peerlib/simplequerytable.php');
include_once('documentfolders.php');
include_once'navigation2.php';
require_once 'studentPrjMilestoneSelector.php';

$prj_id = 1;
$milestone = 1;
$afko = 'undef';
$prjm_id = 0;
extract( $_SESSION );

$prjSel = new StudentMilestoneSelector( $dbConn, $judge, $prjm_id );
$prjSel->setExtraConstraint( " and prjm_id in (select prjm_id from project_deliverables) " );
extract( $prjSel->getSelectedData() );
$_SESSION['prjtg_id'] = $prjtg_id;
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;
$_SESSION['grp_num'] = $grp_num;

if ( isSet( $_REQUEST['prj_id_milestone'] ) ) {
  list($prj_id, $milestone) = explode( ':',
          validate( $_REQUEST['prj_id_milestone'], 'prj_id_milestone', $prj_id, ':' . $milestone ) );
  $_SESSION['prj_id'] = $prj_id;
  $_SESSION['milestone'] = $milestone;
}
$sql = "select prjm_id from prj_milestone where prj_id=$prj_id and milestone=$milestone";
$resultSet = $dbConn->Execute( $sql );
if ( $resultSet === false ) {
  die( 'Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql );
}
extract( $resultSet->fields );

$lang = strtolower( $lang );
$today = date( 'Y-m-d' );

$page = new PageContainer();
$page->setTitle( 'Shared group files' );
$page_opening = "Welcome to the files of the groups of $roepnaam $voorvoegsel $achternaam ($snummer)";
$page_opening = "Files uploaded for projects in which $roepnaam $voorvoegsel $achternaam ($snummer) participates on $today";
$nav = new Navigation( $tutor_navtable, basename( $PHP_SELF ), $page_opening );
$_SESSION['referer'] = $PHP_SELF;
ob_start();
tutorHelper( $dbConn, $isTutor );
$page->addBodyComponent( new Component( ob_get_clean() ) );
$page->addBodyComponent( $nav );
$sql = "SELECT distinct apt.prj_id||':'||apt.milestone as value, \n" .
        "apt.afko||': '||apt.description||'('||apt.year::text||')'||' milestone '||apt.milestone as name\n" .
        ", apt.prj_id,apt.milestone,apt.afko, apt.year as namegrp,apt.year, apt.description,apt.grp_num \n" .
        "FROM all_prj_tutor apt join prj_grp pg using(prjtg_id) " .
        " join project_deliverables pd using(prjm_id)\n" .
        " where snummer=$snummer order by year desc,afko";
$resultSet = $dbConn->Execute( $sql );
if ( $resultSet === false ) {
  die( 'Error: ' . $dbConn->ErrorMsg() . ' with <pre>' . $sql . "</pre>\n" );
}
extract( $resultSet->fields );
$preload = array( '0' => array( 'name' => '&nbsp;', 'value' => '1:1' ) );

$prjList = "<form name='prjmil' action='$PHP_SELF' method='get'>\n" .
        $prjSel->getSelector()
        . "</select>&nbsp;<input type='submit' value='Get'/>\n</form>";

$pp = array( );
$pp['prjList'] = $prjList;
$sql = "SELECT roepnaam, voorvoegsel,achternaam,lang,prjtg_id FROM student \n"
        . "join prj_grp using(snummer) join prj_tutor pt using(prjtg_id) \n"
        . "WHERE snummer=$snummer and pt.prjm_id=$prjm_id";
$resultSet = $dbConn->Execute( $sql );
if ( $resultSet === false ) {
  die( 'Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql );
}
if ( !$resultSet->EOF ) {
  extract( $resultSet->fields );
}
$isTutorBool = $isTutor ? 'true' : 'false';

$sqlfolders = "select ddd.*,doc_count from (select rtrim(afko) as afko,rtrim(description) as description,
            dd.prj_id,dd.year,dd.milestone,
            dd.prjtg_id as authorgrp,dd.grp_num as author_grp_num,long_name,title,rel_file_path,
            to_char(uploadts,'YYYY-MM-DD HH24:MI')::text as uploadts,dd.due,rtrim(mime_type) as mime_type,
            case when dd.uploadts::date > dd.due then 'late' else 'early' end as late_or_early,
            vers,dd.doctype,dd.dtdescr,upload_id,
            dd.snummer, roepnaam,voorvoegsel,achternaam,sclass,critique_count as crits,dd.rights,filesize,
	        (coalesce($peer_id = aud.reader,false) or $isTutorBool) as link, viewergrp
             from document_data3 dd 
                left join (select upload_id,prjm_id,reader,reader_role,viewergrp 
	    	      	      from document_audience where prjm_id=$prjm_id and reader=$peer_id) aud using(upload_id,prjm_id)
                          
		   where dd.prjm_id=$prjm_id ) ddd
             join grp_upload_count2 guc on(ddd.authorgrp=guc.prjtg_id)

    order by afko,milestone,authorgrp";

$pp['documentFolders'] = getDocumentFolders( $dbConn, $sqlfolders );

if ( $isTutor ) {
  $pp['tutorText'] = "Tutors can always read all files without limitation.
            The extra files visable to the tutor have <span style='font-weight: bold; color:#800;'>red</span> due dates.
            The others are <span style='font-weight: bold; color:#080;'>green</span>
            (And these last remarks are only visible because you appear to be a tutor. )";
} else {
  $pp['tutorText'] = '';
}

$page->addHtmlFragment( 'templates/uploadviewer.html', $pp );
$page->show();
?>
