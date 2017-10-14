<?php

include_once('./peerlib/peerutils.php');
include_once('navigation2.php');
include_once 'openBarChart2.php';
require_once 'prjMilestoneSelector2.php';
require_once 'mailFunctions.php';
//$dbConn->setSqlAutoLog( $db_name <> 'peer' );
requireCap(CAP_TUTOR);
$prjm_id = 0;
$prj_id = 1;
$milestone = 1;
extract($_SESSION);

$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
$prjSel->setWhere('valid_until > now()::date');
extract($prjSel->getSelectedData());
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;

// unknown project?

$grp_num = 1;
if (isSet($_POST['grp_num'])) {
    $_SESSION['grp_num'] = $grp_num = $_POST['grp_num'];
}
$isTutorOwner = checkTutorOwnerMilestone($dbConn, $prjm_id, $peer_id); // check if this is tutor_owner of this project
// process data
groupOpener($dbConn, $prjm_id, $isTutorOwner, $_POST);

$prjm_id_selector = $prjSel->getSimpleForm();
$mail_button = "&nbsp;";


$isAdmin = hasCap(CAP_SYSTEM) ? 'true' : 'false';


extract(getTutorOwnerData2($dbConn, $prjm_id), EXTR_PREFIX_ALL, 'ot');
//$_SESSION['prjm_id']=$prjm_id=$ot_prjm_id;
$mailsubject = "invitation to fill in your peer assessment data for project \$afko: \$description";
$templatefile = 'templates/invitemailBodyTemplate.html';
// create mailbody
$mailbody = file_get_contents($templatefile, true);
$sqlsender = "select rtrim(email1) as sender,roepnaam||"
        . "coalesce(' '||tussenvoegsel,'')||' '||achternaam as sender_name,"
        . "coalesce(signature,"
        . "'sent by the peerweb service on behalf of '||roepnaam"
        . "||coalesce(' '||tussenvoegsel,'')||' '||achternaam)\n"
        . "  as signature from student left join email_signature using(snummer)"
        . " where snummer='$peer_id'";
$rs = $dbConn->Execute($sqlsender);
if (!$rs->EOF) {
    extract($rs->fields);
} else {
    $replyto = 'Pieter.van.den.Hombergh@fontysvenlo.org';
    $sender_name = 'Pieter van den Hombergh';
    $signature = '';
}


$mailbody .= $signature;

if (isSet($_POST['mailbody'])) {
    $mailbody = preg_replace('/"/', '\'', $_POST['mailbody']);
}
if (isSet($_POST['mailsubject'])) {
    $mailsubject = $_POST['mailsubject'];
}

if (isSet($_POST['invite'])) {

    $sql = "select email1, email2,\n"
            . " roepnaam ||' '||coalesce(tussenvoegsel,'')||' '||achternaam as name,\n"
            . " afko,description,milestone,assessment_due as due,milestone_name \n"
            . "  from prj_grp join all_prj_tutor using(prjtg_id) \n"
            . " join student using(snummer) \n"
            . " left join alt_email using(snummer) where prjm_id=$prjm_id and prj_grp_open=true";
    formMailer($dbConn, $sql, $mailsubject, $mailbody, $sender, $sender_name);
}

$page = new PageContainer();
$page_opening = "Open or close an assessment" .
        "<span style='font-size:8pt;'>prj_id $prj_id milestone $milestone prjm_id $prjm_id</span>";
$page->setTitle('Open or close an assessment');
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);

$grpTutorString = groupOpenerBarChart2($dbConn, $prjm_id, $isTutorOwner);
$page->addBodyComponent($nav);
$templatefile = 'templates/openclose.html';
$template_text = file_get_contents($templatefile, true);
$text = '';
if ($template_text === false) {
    $page->addBodyComponent(new Component("<strong>cannot read template file $templatefile</strong>"));
} else {
    eval("\$text = \"$template_text\";");
    $page->addBodyComponent(new Component($text));
}
$page->addHeadText(
        '<script language="javascript" type="text/javascript" src="' . SITEROOT . '/js/tiny_mce/tiny_mce.js"></script>
 <script language="javascript" type="text/javascript">
   tinyMCE.init({
        theme: "advanced",
        /*auto_resize: true,*/
        gecko_spellcheck : true,
        theme_advanced_toolbar_location : "top",
	mode : "textareas", /*editor_selector : "mceEditor",*/

        theme_advanced_styles : "Header 1=header1;Header 2=header2;Header 3=header3;Table Row=tableRow1",
        plugins: "advlink,searchreplace,insertdatetime,table",
	plugin_insertdate_dateFormat : "%Y-%m-%d",
	plugin_insertdate_timeFormat : "%H:%M:%S",
	table_styles : "Header 1=header1;Header 2=header2;Header 3=header3",
	table_cell_styles : "Header 1=header1;Header 2=header2;Header 3=header3;Table Cell=tableCel1",
	table_row_styles : "Header 1=header1;Header 2=header2;Header 3=header3;Table Row=tableRow1",
	table_cell_limit : 100,
	table_row_limit : 5,
	table_col_limit : 5,
	theme_advanced_buttons1_add : "search,replace,insertdate,inserttime,tablecontrols",
/*        theme_advanced_buttons2 : "",
	theme_advanced_buttons3 : ""*/
    });
 </script>
');

$page->show();
?>
