<?php

/* $Id: groupemail.php 1845 2015-03-19 11:56:26Z hom $ */
include_once('./peerlib/peerutils.inc');
include_once 'navigation2.inc';
require_once 'prjMilestoneSelector2.php';
include './peerlib/simplequerytable.inc';


$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
$snummer = 1;
extract($_SESSION);
$pp = array();
$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
extract($prjSel->getSelectedData());
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;
$isTutor = true; //hasCap( CAP_TUTOR );
// get data stored in session or added to session by helpers

/* get name, lang etc */
$sql = "SELECT roepnaam, voorvoegsel,achternaam,lang,rtrim(email1) as email1,rtrim(email2) as email2,\n" .
        "coalesce(signature,'sent by the peerweb service on behalf of '||roepnaam||coalesce(' '||voorvoegsel,'')||' '||achternaam)\n" .
        "  as signature\n" .
        "FROM student left join alt_email using(snummer) left join email_signature using(snummer) WHERE snummer=$peer_id";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    die('Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql);
}
extract($resultSet->fields);
$lang = strtolower($lang);
if (isSet($resultSet->fields['email2'])) {
    $email2 = $resultSet->fields['email2'];
} else
    $email2 = '';

$mailto = array();
$pp['formsubject'] = 'Hello world';
$pp['mailbody'] = 'This is a test mail<br/>' . $signature;
$afko = $description = '';
if (isSet($_POST['mailbody'])) {
    $mailbody = $_POST['mailbody'];
}
if (isSet($_POST['formsubject'])) {
    $formsubject = $_POST['formsubject'];
}
$mailto = array();
if (isSet($_POST['mailto'])) {
    $mailto = $_POST['mailto'];
    //    print_r($mailto);
    $toAddress = '';
    $mailset = '\'' . implode("','", $mailto) . '\'';
    $replyto = getEmailAddresses($dbConn, array($_SESSION['peer_id']));
    $toAddress = getEmailAddresses($dbConn, $_POST['mailto']);
    $headers = "From: peerweb@fontysvenlo.org\n" .
            "Reply-To: $replyto\n" .
            "Cc: $replyto\n";
    $headers = htmlmailheaders($replyto, $sender_name, $toAddress, $ccAddress);
    $subject = $formsubject;
    $bodyprefix = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>' . $subject . '</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" >
</head>
<body>
';
    $message = $bodyprefix . $mailbody . "\n</body>\n</html>\n";
    domail($toAddress, $subject, $message, $headers);
    // send author a copy, so he 'll know he is confirmed of sending the email.
    $recipients = htmlentities(preg_replace('/,/', ",\n", $toAddress));
    domail($replyto, $subject . ', your copy', $bodyprefix
            . $mailbody
            . "\n<br/><hr/>The above mail has been sent to the following recipients:\n<pre>"
            . $recipients
            . "\n</pre>\n"
            . "\n</body>\n</html>\n", $headers);
    if ($triggerList != '') {
        $subject = 'You have mail at your fontys email address';
        domail($triggerList, $subject, "See the subject.\n" .
                "One way to read your mail there is to visit " .
                "http://webmail.fontys.nl\n---\nKind Regards,\n Peerweb services", 'From: peerweb@fontysvenlo.org'); //$headers
    }
}
$prjSel->setJoin('milestone_grp using (prj_id,milestone)');
$prjList = $prjSel->getSelector();

$sql = "select * from student\n" .
        "where snummer=$peer_id";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    print "error fetching judge data with $sql : " . $dbConn->ErrorMsg() . "<br/>\n";
}
if (!$resultSet->EOF)
    extract($resultSet->fields);
$page_opening = "Email to group members From: $roepnaam $voorvoegsel $achternaam <span style='font-family: courier'>&lt;$email1&gt;</span>";
$page = new PageContainer();
$page->setTitle('Mail-list page');
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$page->addBodyComponent($nav);
$page->addFileContentsOnce('templates/tinymce_include.html');
$page->addHeadText(
        '<script type="text/javascript">
 function checkThem(ref,state){
  var checks = document.getElementsByName(ref);
  var boxLength = checks.length;
      for ( i=0; i < boxLength; i++ ) {
        checks[i].checked = state;
      }
}
 function unCheckThem(ref){
  var checks = document.getElementsByName(ref);
  var boxLength = checks.length;
  for ( i=0; i < boxLength; i++ ) {
     checks[i].checked = false;
  }
}
function selectByClass(ref,cl,state){
  var checks = document.getElementsByName(ref);
  var boxLength = checks.length;
  for ( i=0; i < boxLength; i++ ) {
     if (checks[i].classList.contains(cl)) {
          checks[i].checked = state;
     }
  }
}
</script>
');

function roleTable($dbConn, $prjm_id) {
    $result = "\n<table border='1' style='border-collapse: collapse;'>\n"
            . "\t<tr style='background:rgba(240,240,240,0.4)'><th>num</th><th>select</th><th>Role</th><th>Abbr</th></tr>";

    $sql = "select pr.* from project_roles pr join prj_milestone using(prj_id) where prjm_id=$prjm_id"
            . "union select prj_id,'TUTOR' as role,999,7,'TUTR' from prj_milestone where prjm_id=$prjm_id"
            . " order  by rolenum";
    //simpletable($dbConn, $sql,"<table border='1' caption='roles in project'>");
    $resultSet = $dbConn->Execute($sql);
    $rb = RainBow::zebra();
    $bg = $rb->restart();
    while (!$resultSet->EOF) {
        extract($resultSet->fields);
        $result .= "\t\t<tr style='background:$bg'>"
                . "<td align='right'>$rolenum</td>\n"
                . "\t\t<td><input type='checkbox' name='roles[]' "
                . "value='$rolenum' "
                . "onclick='javascript:selectByClass(\"mailto[]\",\"role$rolenum\",this.checked)'/></td>\n"
                . "\t\t<td>$role</td><td>$short</td>"
                . "</tr>\n";
        $bg = $rb->getNext();
        $resultSet->moveNext();
    }
    return $result . "</table>\n";
}

function classTable($dbConn, $prjm_id) {
    $result = "\n<table border='1' style='border-collapse: collapse;'>\n"
            . "\t<tr style='background:rgba(240,240,240,0.4)'><th>num</th><th>select</th><th>Class</th></tr>";

    $sql = "select distinct class_id,trim(sclass) as sclass from student join prj_grp using(snummer) join student_class using(class_id)"
            . " join prj_tutor using(prjtg_id) where prjm_id={$prjm_id} order by sclass";
    //simpletable($dbConn, $sql,"<table border='1' caption='roles in project'>");
    $resultSet = $dbConn->Execute($sql);
    $rb = RainBow::zebra();
    $bg = $rb->restart();
    while (!$resultSet->EOF) {
        extract($resultSet->fields);
        $result .= "\t\t<tr style='background:$bg'>"
                . "<td align='right'>{$class_id}</td>\n"
                . "\t\t<td><input type='checkbox' name='sclass[]' "
                . "value='{$class_id}' "
                . "onclick='javascript:selectByClass(\"mailto[]\",\"{$sclass}\",this.checked)'/></td>\n"
                . "<td>{$sclass}</td>"
                . "</tr>\n";
        $bg = $rb->getNext();
        $resultSet->moveNext();
    }
    return $result . "</table>\n";
}

function emailTable($dbConn, $prjm_id, $isTutor, $mailto) {
    $counter = 1;
    $result = "
        <table border='1' style='border-collapse: collapse'>
            <tr style='background:rgba(240,240,240,0.4)'><td colspan='1'>&nbsp;</td>
               <th><input name='checkAll' type='checkbox' onclick='javascript:checkThem(\"mailto[]\",this.checked)'/></th>
               <th align='left' colspan='7'>Select All</th></tr>
         <tr><th>#</th><th>mail</th><th>role</th>
             <th>snummer</th><th>achternaam</th><th>roepnaam</th><th>class</th>
             <th>grpnum</th><th>grp name</th><th>tutor</th>
         </tr>
    ";
    if ($isTutor) {
        $grpSelect = '';
    } else {
        $grpSelect = "and grp_num='$judge_grp_num' ";
    }
    $sql = "select afko,pt.grp_num,coalesce('g'||pt.grp_num,pt.grp_name) as grp_name,\n"
            . "pg.snummer as mail,rtrim(role) as role, pg.snummer,\n"
            . "achternaam||coalesce(', '||voorvoegsel,'') as achternaam,roepnaam,\n"
            . "trim(sclass) as sclass, tutor, 'role'||sr.rolenum as checkclass, 0 as lo\n"
            . "from\n"
            . "student join prj_grp pg using(snummer)\n"
            . "join student_class using (class_id)\n"
            . " join prj_tutor pt on(pg.prjtg_id=pt.prjtg_id)\n"
            . " join tutor t on(userid=tutor_id)\n"
            . " join prj_milestone pm on(pt.prjm_id=pm.prjm_id)\n"
            . " join project p on(p.prj_id=pm.prj_id) \n"
            . " left join student_role sr on(pt.prjm_id=sr.prjm_id and sr.snummer=pg.snummer)\n"
            . " left join project_roles pr on (pm.prj_id=pr.prj_id and pr.rolenum=sr.rolenum)\n"
            . " left join grp_alias ga on(pg.prjtg_id=ga.prjtg_id)\n"
            . " where pt.prjm_id=$prjm_id $grpSelect and pm.prj_id>1";
    $sql2 = "\n union\n"
            . "select apt.afko,grp_num,'tutor' as grp_name,\n"
            . "apt.tutor_id as mail, 'TUTOR' as role, apt.tutor_id as snummer,ts.achternaam||coalesce(', '||ts.voorvoegsel,'') as achternaam,ts.roepnaam,\n"
            . "'TUTOR' as sclass, tutor, 'role'||'999' as checkclass,1 as lo \n"
            . "from all_prj_tutor apt join student  ts on(apt.tutor_id=ts.snummer) left join grp_alias gat using(prjtg_id) \n"
            . "where apt.prjm_id =$prjm_id $grpSelect and apt.prj_id>1\n";
    $sql .= $sql2 . " order by grp_num,lo,achternaam,roepnaam";
    //    echo $sql;
    $resultSet = $dbConn->Execute($sql);
    $rb = new RainBow();
    $bg = $rb->getCurrent();
    $og = $resultSet->fields['grp_num'];
    while (!$resultSet->EOF) {
        extract($resultSet->fields);
        if ($og != $grp_num) {
            $bg = $rb->getNext();
            $og = $grp_num;
        }
        $checked = in_array($snummer, $mailto) ? 'checked' : '';
        $result .= "
                <tr style='background:$bg'><td align='right'>$counter</td>
                    <td align='center'><input type='checkbox' name='mailto[]' value='$snummer' class='$checkclass $sclass' $checked/></td>
                    <td>$role</td>
                    <td>$snummer</td><td>$achternaam</td><td>$roepnaam</td><td>{$sclass}</td>
                    <td class='num'>$grp_num</td><td>$grp_name</td><td>$tutor</td>
                </tr>  ";
        $resultSet->moveNext();
        $counter++;
    }
    return $result . "</table>";
}

$pp['eTable'] = emailTable($dbConn, $prjm_id, $isTutor, $mailto);
$pp['rTable'] = roleTable($dbConn, $prjm_id);
$pp['classTable'] = classTable($dbConn, $prjm_id);
$pp['selWidget'] = $prjSel->getWidget();
$page->addHtmlFragment('templates/groupemail.php', $pp);
$page->show();