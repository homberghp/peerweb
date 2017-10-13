<?php

/* $Id: imail.php 1825 2014-12-27 14:57:05Z hom $ */
//session_start();
include_once('./peerlib/peerutils.php');
include_once('tutorhelper.php');
include_once 'navigation2.php';
include_once './peerlib/SimpleTableFormatter.php';
require_once './peerlib/studentPrjMilestoneSelector.php';
$judge = 1;
$prjm_id = 0;
$prjtg_id=1;
extract($_SESSION);

$prjSel = new StudentMilestoneSelector($dbConn, $judge, $prjm_id);
$pp = array();

extract($prjSel->getSelectedData());
$_SESSION['prjtg_id'] = $prjtg_id;
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;
$_SESSION['grp_num'] = $grp_num;

// get data stored in session or added to session by helpers
$milestone = 1;
/* get name, lang etc */
$sql = "SELECT roepnaam, tussenvoegsel,achternaam,lang,rtrim(email1) as email1,rtrim(email2) as email2,\n"
        . "coalesce(signature,'sent by the peerweb service on behalf of '||roepnaam||coalesce(' '||tussenvoegsel,'')||' '||achternaam)\n"
        . "  as signature\n"
        . "FROM student left join alt_email using(snummer) left join email_signature using(snummer) WHERE snummer=$peer_id";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    die('Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql);
}
extract($resultSet->fields);
$lang = strtolower($lang);
if (isSet($resultSet->fields['email2'])) {
    $email2 = $resultSet->fields['email2'];
}
else
    $email2 = '';

$mailto = array();
$pp['formsubject'] = 'Hello world';
$pp['mailbody'] = 'This is a test mail<br/>' . $signature;
$afko = $description = '';
$judge_grp_num = $grp_num = -1;

if (isSet($_POST['mailbody'])) {
    $pp['mailbody'] = $_POST['mailbody'];
}
if (isSet($_POST['formsubject'])) {
    $pp['formsubject'] = $_POST['formsubject'];
}
if (isSet($_POST['mail'])) {
    $mail = $_POST['mail'];
    //    print_r($mailto);
    $toAddress = '';
    $mailset = '\'' . implode("','", $mail) . '\'';
    $replyto = getEmailAddress($dbConn, $_SESSION['peer_id'], false);
    $sql = "select distinct rtrim(email1,' ') as email1 ,rtrim(email2,' ') as email2,\n"
            . " s.roepnaam ||coalesce(' '||s.tussenvoegsel,'')||' '||s.achternaam as recipient,\n"
            . " td.tutor,td.tutor_email,grp_num \n"
            . "from \n"
            . "  student s join prj_grp pg using(snummer) join all_prj_tutor apt using (prjtg_id) \n"
            . "  join tutor_data td using(tutor_id)\n"
            . "  left join alt_email ae on (ae.snummer=s.snummer)\n"
            . "where s.snummer in ({$mailset}) and prjm_id={$prjm_id} order by tutor,grp_num";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die("<br>Cannot read project,student data with <pre>" . $sql . "</pre><br/>\n reason " . $dbConn->ErrorMsg() . "<br>");
    }
    // tutors get a CC.
    //$dbConn->log($sql);
    $tutors = array();
    $oldTutor = '';
    $toAddress = '';
    $ccAddress = '';
    $cccon = ''; // continue on cc list
    $tocon = ''; // continue on to list
    $triggerList = '';
    $trigCon = ''; // continue on triggerlist.
    // get email adresses and tutor 
    while (!$resultSet->EOF) {
        extract($resultSet->fields);
        if ($oldTutor != $tutor) {
            if (!in_array($tutor, $tutors))
                array_push($tutors, $tutor);
            $oldTutor = $tutor;
        }
        if (isSet($email2)) {
            $triggerList .= $trigCon . $email2;
            $trigCon = ', ';
        }
        $toAddress .= $tocon . $recipient . ' <' . "$email1" . '>';
        $tocon = ', ';
        $resultSet->movenext();
    }
    $sqlsender = "select rtrim(email1) as sender,roepnaam||coalesce(' '||tussenvoegsel,'')||' '||achternaam as sender_name," .
            "coalesce(signature," .
            "'sent by the peerweb service on behalf of '||roepnaam||coalesce(' '||tussenvoegsel,'')||' '||achternaam)\n" .
            "  as signature from student left join email_signature using(snummer) where snummer='$peer_id'";
    $rs = $dbConn->Execute($sqlsender);
    if (!$rs->EOF) {
        extract($rs->fields);
    } else {
        $replyto = 'Pieter.van.den.Hombergh@fontysvenlo.org';
        $sender_name = 'Pieter van den Hombergh';
        $signature = '';
    }
    $headers = htmlmailheaders($sender, $sender_name, $email1, $tutor_email);
    if (!in_array($tutor, $tutors))
        array_push($tutors, $tutor);
    foreach ($tutors as $tutor) {
        $ccAddress .= $cccon . getEmailAddress($dbConn, $tutor, true);
        $cccon = ', ';
    }
    if (isSet($email2))
        $email1 .= ', ' . $email2;
    $subject = $pp['formsubject'];
    $bodyprefix = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>' . $subject . '</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" >
</head>
<body>
';
    $message = $bodyprefix . $pp['mailbody'] . "\n</body>\n</html>\n";
    domail($toAddress, $subject, $message, $headers);
    if ($triggerList != '') {
        $subject = 'You have mail at your fontys email address';
        domail($triggerList, $subject, "See the subject.\n" .
                "One way to read your mail there is to visit " .
                "http://webmail.fontys.nl\n---\nKind Regards,\n Peerweb services", 'From: peerweb@fontysvenlo.org'); //$headers
    }
}
$prjList = $prjSel->getWidget();
$sql = "select s.*,pt.* from student s join prj_grp using(snummer)\n" .
        "join all_prj_tutor pt using(prjtg_id) where prjm_id=$prjm_id and snummer=$snummer";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    print "error fetching student data with <pre>$sql </pre>: " . $dbConn->ErrorMsg() . "<br/>\n";
}
if (!$resultSet->EOF) {
    $pp = array_merge($pp, $resultSet->fields);
}
$page_opening = "Email to group members From: $roepnaam $tussenvoegsel $achternaam <span style='font-family: courier'>&lt;$email1&gt;</span>";
$page = new PageContainer();
$page->setTitle('Mail-list page');
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
if (isSet($_REQUEST['getall']) && (!isSet($mailto) || count($mailto) == 0)) {
    $sql = "select snummer as mailto \n" .
            "from prj_grp join all_prj_tutor using(prjtg_id)\n" .
            "where prjm_id=$prjm_id ";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        print "error fetching judge data with $sql : " . $dbConn->ErrorMsg() . "<br/>\n";
    }
    $mailto = array();
    while (!$resultSet->EOF) {
        array_push($mailto, $resultSet->fields['mailto']);
        $resultSet->moveNext();
    }
}
//$nav->addLeftNavText(file_get_contents('news.html'));
ob_start();
tutorHelper($dbConn, $isTutor);
$page->addBodyComponent(new Component(ob_get_clean()));
$page->addBodyComponent($nav);
$page->addHeadFragment('templates/tinymce_include.html');
$pp['prjList'] = $prjList;
if ($isTutor) {
    $grpSelect = '';
} else {
    $grpSelect = "and pg.prjtg_id=$prjtg_id ";
}
$pp['prjm_id']=$prjm_id;
$sql = "select afko,apt.grp_num||coalesce(': '||alias,'') as grp_num,\n"
        . "'<input type=\"checkbox\"  name=\"mail[]\" value=\"'||s.snummer||'\"/>' as chk,\n"
        . "rtrim(role) as role, s.snummer,\n"
        . "achternaam||coalesce(', '||tussenvoegsel,'') as achternaam,roepnaam,\n"
        . "sclass as class, tutor "
        . "from\n"
        . "student s join prj_grp pg using(snummer)\n"
        . "join student_class using (class_id)\n"
        . " join all_prj_tutor apt on(pg.prjtg_id=apt.prjtg_id)\n"
        . " left join student_role sr on(sr.prjm_id=apt.prjm_id and sr.snummer=s.snummer) "
        . "left join project_roles pr on(apt.prj_id=pr.prj_id and pr.rolenum=sr.rolenum)\n"
        . " where apt.prjm_id=$prjm_id $grpSelect and apt.prj_id>1 order by apt.grp_num,apt.milestone,s.achternaam,s.roepnaam";

$pp['rtable'] = new SimpleTableFormatter($dbConn, $sql, $page);
$pp['rtable']->setColorChangerColumn(1)
        ->setTabledef("<table id='myTable' summary='your requested data'"
                . " style='empty-cells:show;border-collapse:collapse' border='1'>")
        ->setCheckName('mail[]')
        ->setCheckColumn(2);
$page->addHtmlFragment('templates/imail.html', $pp);
$page->addHtmlFragment('templates/tinymce_include.html', $pp);
$page->show();
?>
