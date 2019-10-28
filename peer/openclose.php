<?php
requireCap(CAP_TUTOR);
require_once('navigation2.php');
require_once 'openBarChart2.php';
require_once 'prjMilestoneSelector2.php';
require_once 'mailFunctions.php';
//$dbConn->setSqlAutoLog( $db_name <> 'peer' );
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
$templatefile = '../templates/invitemailBodyTemplate.html';
// create mailbody
$mailbody = file_get_contents($templatefile, true);
$sqlsender = "select rtrim(email1) as sender,roepnaam||"
        . "coalesce(' '||tussenvoegsel,'')||' '||achternaam as sender_name,"
        . "coalesce(signature,"
        . "'sent by the peerweb service on behalf of '||roepnaam"
        . "||coalesce(' '||tussenvoegsel,'')||' '||achternaam)\n"
        . "  as signature from student_email left join email_signature using(snummer)"
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
    $SESSION['mailbody'] = $mailbody = $_POST['mailbody'];
} else if (isSet($SESSION['mailbody'])) {
    $mailbody = $_SESSION['mailbody'];
}
if (isSet($_POST['formsubject'])) {
    $SESSION['formsubject'] = $formsubject = $_POST['formsubject'];
} else if (isSet($SESSION['formsubject'])) {
    $formsubject = $SESSION['formsubject'];
}

$substitutions='{$email1}, {$roepnaam}, {$name},{$afko}, {$description}, {$milestone}, {$assessment_due}, {$prjm_id}, and {$milestone_name}' ;
if (isSet($_POST['invite'])) {

    $mailerQuery = <<<'SQL'
            select email1 as email,
            roepnaam ||' '||coalesce(tussenvoegsel||' ','')||achternaam as name,roepnaam as firstname,
            prjm_id,trim(afko) as afko,trim(description) as description,milestone,assessment_due as due,milestone_name
            from prj_grp join all_prj_tutor using(prjtg_id)
             join student_email using(snummer) 
             left join alt_email using(snummer) where prjm_id =$1 and prj_grp_open=true
SQL;
    //formMailer($dbConn, $sql, $mailsubject, $mailbody, $sender, $sender_name);
    $formMailer= new FormMailer($dbConn,$mailsubject,$mailbody,$peer_id);
    $formMailer->mailWithData($mailerQuery,[$prjm_id]);
}

$page = new PageContainer();
$page_opening = "Open or close an assessment" .
        "<span style='font-size:8pt;'>prj_id $prj_id milestone $milestone prjm_id $prjm_id</span>";
$page->setTitle('Open or close an assessment');
$nav = new Navigation($tutor_navtable, basename(__FILE__), $page_opening);

$grpTutorString = groupOpenerBarChart2($dbConn, $prjm_id, $isTutorOwner);
$page->addBodyComponent($nav);
$templatefile = '../templates/openclose.html';
$template_text = file_get_contents($templatefile, true);
$text = '';
$pp=array();
if ($template_text === false) {
    $page->addBodyComponent(new Component("<strong>cannot read template file $templatefile</strong>"));
} else {
    $page->addBodyComponent(new Component(templateWith($template_text, get_defined_vars())));
}
$page->addHtmlFragment('../templates/tinymce_include.html', $pp);
$page->show();

