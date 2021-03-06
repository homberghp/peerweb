<?php

requireCap(CAP_TUTOR);
require_once('navigation2.php');
require_once 'prjMilestoneSelector2.php';
require_once 'mailFunctions.php';
$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
extract($_SESSION);

$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
extract($prjSel->getSelectedData());
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;

$tutor = $_SESSION['tutor_code'];
//$tutor_id = $_SESSION['tutor_id'];
$sql = "select email1 as email from tutor join student_email on(userid=snummer) where tutor='$tutor'";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    die("<br>Cannot read tutor email address " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>");
}
$replyto = $resultSet->fields['email'];
$snmailto = array();
$formsubject = 'Please fill in your peer assessment data for project {$afko}: {$description}';
$templatefile = "../templates/duemailbodytemplate.html";
$sqlsender = "select rtrim(email1) as sender,roepnaam||coalesce(' '||tussenvoegsel,'')||' '||achternaam as sender_name," .
        "coalesce(signature," .
        "'sent by the peerweb service on behalf of '||roepnaam||coalesce(' '||tussenvoegsel,'')||' '||achternaam)\n" .
        "  as signature from student_email left join email_signature using(snummer) where snummer='$peer_id'";
$rs = $dbConn->Execute($sqlsender);
if (!$rs->EOF) {
    extract($rs->fields);
} else {
    $replyto = 'Pieter.van.den.Hombergh@fontysvenlo.org';
    $sender_name = 'Pieter van den Hombergh';
    $signature = '';
}

$mailbody = file_get_contents($templatefile, true);
$mailbody .= $signature;
if (isSet($_POST['mailbody'])) {
    $mailbody = preg_replace('/"/', '\'', $_POST['mailbody']);
}
if (isSet($_POST['formsubject'])) {
    $formsubject = $_POST['formsubject'];
}

if (isSet($_POST['snmailto']) && isSet($_POST['domail'])) {
    $snmailto = $_POST['snmailto'];
    $mailset = '\'' . implode("','", $snmailto) . '\'';
    $paramtext = setToParamList($snmailto, 2);
    $sql = <<<'SQL'
select distinct email1 as email, tutor_email,s.roepnaam as firstname,
    s.roepnaam ||' '||coalesce(s.tussenvoegsel,'')||' '||s.achternaam as name,
    trim(afko) as afko, trim(description) as description,milestone,assessment_due as due,milestone_name 
    from prj_grp pg 
    join student_email s on (s.snummer=pg.snummer)
    join prj_tutor pt on(pt.prjtg_id=pg.prjtg_id)
    join tutor t on(userid=tutor_id)
    join prj_milestone pm on(pt.prjm_id=pm.prjm_id)
    join project p on (pm.prj_id=p.prj_id)
    join tutor_data td on (pt.tutor_id=td.tutor_id)
    left join alt_email aem on (s.snummer=aem.snummer)
where  pm.prjm_id=$1 and s.snummer in
SQL;
    $sql .="($paramtext)";
    //$dbConn->log($sql);
    //formMailer($dbConn, $sql, $formsubject, $mailbody, $sender, $sender_name);
    $formMailer = new FormMailer($dbConn, $formsubject, $mailbody, $peer_id);
    $params = [$prjm_id];
    $params = array_merge($params, $snmailto);
    $formMailer->mailWithData($sql,$params);
}
$page_opening = "These students are overdue with filling in their peer assessment forms.";
$nav = new Navigation(array(), basename(__FILE__), $page_opening);
$page = new PageContainer();
$page->addBodyComponent($nav);
if (hasCap(CAP_TUTOR_OWNER)) {
    $tutor_select = "";
} else {
    $tutor_select = " and (tutor='$tutor' or tutor_owner='$tutor') ";
}

//ob_start();

$prjSel->setWhere("assessment_due <now() and pm.prj_milestone_open=true");
$prj_id_selector = $prjSel->getWidget();

$sqlhead = "select  afko as code,pm.milestone as milstn,pt.grp_num,\n" .
        "s.snummer as snmailto,s.snummer,\n" .
        "achternaam||coalesce(', '||tussenvoegsel,'') as achternaam\n" .
        ",roepnaam, s.snummer,pm.assessment_due as due,tutor\n";
$sqllate = "( select distinct snummer from prj_grp \n"
        . "natural join prj_tutor pt \n"
        . "join tutor t on(userid=tutor_id)\n"
        . "natural join prj_milestone \n"
        . "where written =false \n"
        . "and prj_milestone_open=true \n"
        . "and prj_grp_open=true \n"
        . "and assessment_due < now()::date \n"
        . "and prjm_id=$prjm_id)";
$sqltail = " \n"
        . " join milestone_open_past_due mopd on(jnr.prjtg_id=mopd.prjtg_id)"
        . " join prj_grp_open pgo on(pgo.prjtg_id=jnr.prjtg_id)\n"
        . " join student_email s on (jnr.snummer=s.snummer) \n"
        . " join prj_tutor pt on(jnr.prjtg_id=pt.prjtg_id)\n"
        . " join tutor t on(userid=tutor_id)\n"
        . " join prj_milestone pm on(pt.prjm_id=pm.prjm_id)\n"
        . " join project p on(p.prj_id=pm.prj_id)\n" .
        " where pm.prjm_id=$prjm_id\n";
//$dbConn->log($sql);
$latecountsql = "select count(*) as latecount from $sqllate foo";
$resultSet = $dbConn->Execute($latecountsql);
if ($resultSet === false) {
    echo( "<br>Cannot get latecount  with <pre>$latecountsql</pre>, cause" . $dbConn->ErrorMsg() . "<br>");
    stacktrace(1);
    die();
}
$latecount = $resultSet->fields['latecount'];
$mailbutton = ($latecount > 0) ? "<input type='submit' name='domail' value='Send Mail'/>" : "&nbsp;";

$sql = $sqlhead . " from  \n"
        . "prj_grp pg \n"
        . "join prj_tutor pt using(prjtg_id)\n"
        . "join tutor t on (userid=tutor_id)\n"
        . "join prj_milestone pm using(prjm_id)\n"
        . "join project p using(prj_id)\n"
        . "join student_email s using(snummer)\n"
        . "where prjm_id=$prjm_id"
        . " and snummer in" . $sqllate . "\n"
        . " order by afko,grp_num,achternaam,roepnaam";
$dueTable = getQueryToTableChecked($dbConn, $sql, true, 2, new RainBow(0x46B4B4, 64, 32, 0), 3, 'snmailto[]', $snmailto);
$templatefile = '../templates/dueassessment.html';
$template_text = file_get_contents($templatefile, true);
$pp=[];
if ($template_text === false) {
    $page->addText("<strong>cannot read template file $templatefile</strong>");
} else {
    $page->addBodyComponent(new Component(templateWith($template_text, get_defined_vars())));
}
$page->addHtmlFragment('../templates/tinymce_include.html', $pp);

$page->addHeadText(
        '<script type="text/javascript">
 function checkThem(ref){
  var checks = document.getElementsByName(ref);
  var boxLength = checks.length;
      for ( i=0; i < boxLength; i++ ) {
        checks[i].checked = true;
      }
}
 function unCheckThem(ref){
  var checks = document.getElementsByName(ref);
  var boxLength = checks.length;
      for ( i=0; i < boxLength; i++ ) {
        checks[i].checked = false;
      }
}
</script>
');

$page->show();
