<?php

requireCap(CAP_SYSTEM);
require_once 'validators.php';
include_once 'navigation2.php';
include_once 'simplequerytable.php';
require_once 'prjMilestoneSelector2.php';
require_once 'presencetable.php';
require_once 'CheckTable.class.php';
require_once 'maillists.inc.php';
require_once 'TemplateWith.php';
requireScribeCap( $peer_id );

// get group tables for a project
$prj_id = 0;
$prjm_id = 0;
$milestone = 1;
$afko = 'PRJ00';
$description = '';
extract( $_SESSION );
$prjSel = new PrjMilestoneSelector2( $dbConn, $peer_id, $prjm_id );

extract( $prjSel->getSelectedData() );
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;


if ( isSet( $_REQUEST['createmaillists'] ) ) {
  createGroupMaillists( $dbConn, $prjm_id );
  createMaillists( $dbConn, $prjm_id );
  //  @system('/bin/kickaliasappender');
}


$prj_id_selector = $prjSel->getSelector();
$selection_details = $prjSel->getSelectionDetails();
$sql = "select distinct grp_num,grp_name, trim(email_to_href(maillist||'@fontysvenlo.org')) as maillist, size as members from prj_grp_email natural join prjtg_size\n"
        . " where prjm_id=$prjm_id\n"
        . " union "
        . "select distinct grp_num,grp_name, trim(email_to_href(maillist||'@fontysvenlo.org')) as maillist, size as members from prj_grp_email_g0 natural join prjm_size\n"
        . " where prjm_id=$prjm_id\n"
        . " union\n"
        . "select distinct grp_num,'tutors'::text as grp_name, trim(email_to_href(maillist||'@fontysvenlo.org')) as maillist,"
        . " size as members from prj_tutor_email cross join (select count(distinct tutor_id) as size from prj_tutor where prjm_id=$prjm_id) ptes \n"
        . " where prjm_id=$prjm_id\n"
        . "order by grp_num";

$page = new PageContainer();

$page->setTitle( 'Create of view maillists for peerweb project' );
$page_opening = "Mail lists for project $afko $description prjm_id $prjm_id prj_id $prj_id milestone $milestone";
$nav = new Navigation( $tutor_navtable, basename( $PHP_SELF ), $page_opening );
$page->addBodyComponent( $nav );
ob_start();
simpletable( $dbConn, $sql, "<table summary='maillists for project ' style='border-collapse:collapse' border='1'>" );
$maillist_table = ob_get_clean();

$templatefile = 'templates/createmaillists.html';
$template_text = file_get_contents( $templatefile, true );
$text = '';
if ( $template_text === false ) {
  $text = "<strong>cannot read template file $templatefile</strong>";
} else {
   $text= templateWith($template_text, get_defined_vars());
}

$page->addBodyComponent( new Component( $text ) );
$page->show();
?>
