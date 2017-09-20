<?php
include_once('./peerlib/peerutils.php');
requireCap(CAP_TUTOR);
include_once('navigation2.php');
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

ob_start();
// get tutor email address
$tutor = $_SESSION['tutor_code'];
$tutor_id = $_SESSION['tutor_id'];
$sql = "select email1 as email from tutor join student on(userid=snummer) where tutor='$tutor'";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    die("<br>Cannot read tutor email address " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>");
}
$replyto = $resultSet->fields['email'];
$snmailto = array();
$formsubject = "Please fill in your peer assessment data for project \$afko: \$description";
$templatefile = "templates/mailbodytemplate.html.inc";
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

$mailbody = file_get_contents($templatefile, true);
$mailbody .=$signature;
if (isSet($_POST['mailbody'])) {
    $mailbody = preg_replace('/"/', '\'', $_POST['mailbody']);
    //    $dbConn->log($mailbody);
}
if (isSet($_POST['formsubject'])) {
    $formsubject = $_POST['formsubject'];
}


if (isSet($_POST['snmailto']) && isSet($_POST['domail'])) {

    $snmailto = $_POST['snmailto'];
    $mailset = '\'' . implode("','", $snmailto) . '\'';

    $sql = "select distinct email1,email2, tutor_email, \n"
            . "s.roepnaam ||' '||coalesce(s.tussenvoegsel,'')||' '||s.achternaam as name\n"
            . ", afko, description,milestone,assessment_due as due \n"
            . " from prj_grp pg \n"
            . " join student s on (s.snummer=pg.snummer) \n"
            . " join prj_tutor pt on(pt.prjtg_id=pg.prjtg_id) \n"
            . " join tutor t on(userid=tutor_id) \n"
            . " join prj_milestone pm on(pt.prjm_id=pm.prjm_id) \n"
            . " join project p on (pm.prj_id=p.prj_id)\n"
            . " join tutor_data td on (pt.tutor_id=td.tutor_id)"
            . " left join alt_email aem on (s.snummer=aem.snummer)\n"
            . "where s.snummer in ($mailset) and pm.prjm_id=$prjm_id";
    $dbConn->log($sql);
    formMailer($dbConn, $sql, $formsubject, $mailbody, $sender, $sender_name);
}
$page_opening = "These students are overdue with filling in their peer assessment forms.";
$nav = new Navigation(array(), basename($PHP_SELF), $page_opening);
$page = new PageContainer();
$page->addBodyComponent($nav);
ob_end_clean();
if (hasCap(CAP_SYSTEM)) {
    $tutor_select = "";
} else {
    $tutor_select = " and (tutor='$tutor' or tutor_owner='$tutor') ";
}

ob_start();

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
        . " join student s on (jnr.snummer=s.snummer) \n"
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
        . "join student s using(snummer)\n"
        . "where prjm_id=$prjm_id"
        . " and snummer in" . $sqllate . "\n"
        . " order by afko,grp_num,achternaam,roepnaam";
echo "<div>\n";
?><table>
    <tr>
        <td>To mail to (almost) all students, select <input type='hidden' name='peerdata' value='prj_id_milestone'/>
            <button name='checkAll' type='button' onclick='javascript:checkThem("snmailto[]")'>Select All</button></td>

        <td> To mail to a few choose 
            <button name='checkNone' type='button' onclick='javascript:unCheckThem("snmailto[]")'>Select None</button></td>
    </tr>
</table>
<?php
queryToTableChecked($dbConn, $sql, true, 2, new RainBow(0x46B4B4, 64, 32, 0), 3, 'snmailto[]', $snmailto);
echo "</div>\n";
$selecttable = ob_get_clean();
$templatefile = 'templates/dueassessment.html.inc';
$template_text = file_get_contents($templatefile, true);
if ($template_text === false) {
    $page->addText("<strong>cannot read template file $templatefile</strong>");
} else {
    eval("\$text = \"$template_text\";");
    $page->addBodyComponent(new Component($text));
}
$page->addHeadText(
        '<script language="javascript" type="text/javascript" src="' . SITEROOT . '/js/tiny_mce/tiny_mce.js"></script>
 <script language="javascript" type="text/javascript">
   tinyMCE.init({
        theme: "advanced",
        auto_resize: true,
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
  <script type="text/javascript">
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
?>