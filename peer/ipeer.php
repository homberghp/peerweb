<?php
/* $Id: ipeer.php 1825 2014-12-27 14:57:05Z hom $ */
include_once('./peerlib/peerutils.inc');
require_once 'groupassessmenttable.php';
include_once('tutorhelper.inc');
include_once 'navigation2.inc';
require_once 'GroupPhoto.class.php';
require_once 'studentPrjMilestoneSelector.php';
$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
$grp_num = 1;
$prjtg_id = 1;
extract($_SESSION);
$judge = $snummer;
$prjSel = new StudentMilestoneSelector($dbConn, $judge, $prjm_id);
$prjSel->setExtraConstraint(" and prjtg_id in (select distinct prjtg_id from assessment) ");
extract($prjSel->getSelectedData());
$_SESSION['prjtg_id'] = $prjtg_id;
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;
$_SESSION['grp_num'] = $grp_num;

// get data stored in session or added to session by helpers
$replyText = '';
$script = $lang = 'nl';
//echo "$user<br/>\n";

$sql = "select * from student where snummer=$judge";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    print "error fetching judge data with $sql : " . $dbConn->ErrorMsg() . "<br/>\n";
}
if (!$resultSet->EOF)
    extract($resultSet->fields, EXTR_PREFIX_ALL, 'judge');
$lang = strtolower($judge_lang);
$student_data = "$judge_roepnaam $judge_voorvoegsel $judge_achternaam ($judge_snummer)";
$page_opening = "Assessment entry form for $student_data";
$page = new PageContainer();
$page->setTitle('Peer assessment entry form');
$page->addHeadText("<script language='JavaScript' type='text/javascript'>
/**
 * validate input
 */
function validateGrade(el) {
  var rex;
  /* el is the element with the value to be tested
   */
  var locvar = el.value;
  el.value = locvar;
  rex = locvar.search(/^[0-9]{1,2}$/);
  if (rex == -1 ) {
    alert(el.value+': Only digits (and whole numbers) are allowed!');
    return false;
  }
  if ( locvar < 1 || locvar > 10 ) {
    alert(el.value + 
	  ' is not a correct grade, use an integer between 1 and 10');
    return false;
  }
  return true;
}
</script>");
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
ob_start();
tutorHelper($dbConn, $isTutor);
$page->addBodyComponent(new Component(ob_get_clean()));
$page->addBodyComponent($nav);
ob_start();

/**
 * Use peer database function try_close to try and close the group.
 * @global type $dbConn
 * @param type $gid group id
 * @param type $stid student/judge
 * @return boolean true is this call closed the group.
 */
function tryClose($gid, $stid) {
    global $dbConn;
    $sql = "select try_close($gid,$stid)";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die("<br>Cannot execute \"" . $sql . '", cause ' . $dbConn->ErrorMsg() . "<br>");
    } else {
        return $resultSet->fields['try_close'] === 't';
    }
    return false;
}

// see if there is a reopen request
if ($isTutor && isSet($_REQUEST['reopen'])) {

    $sql = "begin work;\n" .
            "update prj_grp set prj_grp_open=true,written=false where prjtg_id=$prjtg_id and snummer=$judge ;\n" .
            "update prj_tutor set prj_tutor_open=true,assessment_complete=false where prjtg_id=$prjtg_id;\n" .
            "update prj_milestone set prj_milestone_open=true where prjm_id=$prjm_id;\n" .
            "commit;";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die("<br>Cannot update prj_grp table with \"" . $sql . '", cause ' . $dbConn->ErrorMsg() . "<br>");
    }
    $dbConn->log('reopen' + $sql);
}

//echo "2 found $prj_id, $milestone,$judge for tutor</br>";
// test of voting is still open for this group
$prjtg_id = (isSet($prjtg_id)) ? $prjtg_id : 0;
$grp_open = grpOpen2($dbConn, $judge, $prjtg_id);
$dbConn->log("group open test $grp_open <br/>");
if ($grp_open && isSet($_POST['peerdata'])) {
    if ($_POST['peerdata'] == 'grade' && isSet($_POST['grade'])) {
        $continuation = '';
        $sql = "begin work;\n";
        $c = count($_POST['criterium']);
        $cc = intval($c / count($_POST['contestant']));
        $ci = 0;
        // every 'criteria-count' times , increment contestent index.
        for ($i = 0; $i < $c; $i++, $ci+=($i % $cc) ? 0 : 1) {
            $contestant = $_POST['contestant'][$ci];
            $criterium = $_POST['criterium'][$i];
            $grade = $_POST['grade'][$i];
            $grade = ctype_digit($grade) ? round($grade) : 0;
            $grade = ($grade != 0) ? $grade : 10;
            // limit grade
            $grade = max($grade, 1);
            $grade = min($grade, 10);
            $sql .= "update assessment set grade=$grade where "
                    . "prjtg_id=$prjtg_id and "
                    . "contestant=$contestant and judge=$judge and "
                    . "criterium=$criterium;\n";
        }
        $cc = count($_POST['contestant']);
        // drop old remarks
        $sql .= "delete from assessment_remarks where prjtg_id=$prjtg_id and judge=$judge;\n";

        for ($i = 0; $i < $cc; $i++) {
            $remark = pg_escape_string($_POST['remark'][$i]);
            if (isSet($remark) && $remark != '') {
                $sql .= "insert into assessment_remarks (contestant,judge,prjtg_id,remark) "
                        . "values({$_POST['contestant'][$i]},$judge,$prjtg_id,'$remark');\n";
            }
        }
        $sql .="insert into assessment_commit values($judge,now(),$prjtg_id);\n"
                . "update prj_grp set written=true,prj_grp_open=false where prjtg_id=$prjtg_id and snummer=$judge;\n"
                . "commit;";
//        $dbConn->log($sql);
//      echo "<pre style='text-align:left'>$sql</pre>\n";
        $resultSet = $dbConn->Execute($sql);
        if ($resultSet === false) {
            print 'error updating: ' . $dbConn->ErrorMsg() . '<BR>';
        } else {
            $replyText = '<span style=\'color:#080;font-weight:bold\'>' . $langmap['thanks'][$lang] . '</span>';
        }
        // now check if group needs to be closed
        if (tryClose($prjtg_id, $judge)) {
            // close group
            $dbConn->log('close group ' . $prjtg_id);
            // if so, notify tutor and members.
            $sql = "select email1 as altemail from project_tutor_owner where prj_id=$prj_id";
            $resultSet = $dbConn->Execute($sql);
            if ($resultSet === false) {
                print 'error getting tutor_owner email data for closing prj_grp with $sql ' . $dbConn->ErrorMsg() . '<BR>';
            } else {
                extract($resultSet->fields);
            }
            // and mail tutor
            $sql = "select  email1 as email,roepnaam,achternaam,voorvoegsel,afko,description,grp_num \n" .
                    "from tutor t join student s on(t.userid=s.snummer) join prj_tutor pt on(pt.tutor_id=t.userid) \n" .
                    "join prj_milestone using(prjm_id)\n" .
                    "join project using(prj_id) \n" .
                    "where prjtg_id=$prjtg_id";
            $resultSet = $dbConn->Execute($sql);
            if ($resultSet === false) {
                print 'error getting tutor email data for closing prj_grp with $sql ' . $dbConn->ErrorMsg() . '<BR>';
            } else {
                extract($resultSet->fields);
                $achternaam = trim($achternaam);
                $roepnaam = trim($roepnaam);
                $voorvoegsel = trim($voorvoegsel);
                $to = trim($email);
                $subject = "The assessment is complete for project $afko group $grp_num milestone $milestone";
                $body = "Beste $roepnaam $voorvoegsel $achternaam,\n\n" .
                        "Alle studenten van groep $grp_num in project $afko ($description)hebben " .
                        "hun beoordeling ingegeven.\n" .
                        "U kunt de gegevens op de bekende plaats " .
                        "($server_url$root_url/groupresult.php?prjtg_id=" .
                        "$prjtg_id) inzien.\n" .
                        "U ontvangt dit bericht omdat u als tutor staat geregistreerd voor deze groep.\n" .
                        "---\nMet vriendelijke groet,\nHet peerassessment systeem";
                $headers = "Reply-To: hom@fontysvenlo.org\n";
                dopeermail($to, $subject, $body, $headers, $altemail);
                //	    $dbConn->log("email $body");
            }
            // and mail other members
            $sql = "select roepnaam,voorvoegsel,achternaam,email1,email2 \n"
                    . "from student \n"
                    . "left join alt_email using(snummer)\n"
                    . " join prj_grp using (snummer) \n"
                    . "join prj_tutor using(prjtg_id)\n"
                    . " join prj_milestone using(prjm_id)\n"
                    . "where prjtg_id=$prjtg_id";
            $resultSet = $dbConn->Execute($sql);
            if ($resultSet === false) {
                print 'error getting student email data for closing prj_grp with $sql ' . $dbConn->ErrorMsg() . '<BR>';
            } else {
                $sroepnaam = '';
                $to = '';
                $continue = '';
                while (!$resultSet->EOF) {
                    extract($resultSet->fields);
                    $sroepnaam .= $continue . trim($roepnaam);
                    $to .= $continue . trim($email1);
                    $continue = ', ';
//                    if (isSet($email2)) {
//                        $to .= $continue . trim($email2);
//                    }
                    $resultSet->moveNext();
                }
                $subject = "The assessment is complete for project $afko group $grp_num milestone $milestone";
                $body = "Beste " . $sroepnaam . ",\n\n" .
                        "Alle studenten van groep $grp_num in project $afko ($description) hebben " .
                        "hun beoordeling ingegeven.\n" .
                        "Je kunt de gegevens bekijken op de bekende plaats " .
                        "(https://www.fontysvenlo.org/peerweb/iresult.php) inzien.\n" .
                        "---\nMet vriendelijke groet,\nHet peerassessment systeem";
                $headers = "Reply-To: hom@fontysvenlo.org\n";
                dopeermail($to, $subject, $body, $headers, $altemail);
            }
        }
    }
}

// after processing build (new) page
// first assure that grp_num is (still) open
$prjtg_id = (isSet($prjtg_id)) ? $prjtg_id : 0;
$grp_open = grpOpen2($dbConn, $judge, $prjtg_id);
$sql = "select count(*) as assessment_count from assessment where judge=$judge";
//$dbConn->log($sql);
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    echo ("Cannot get assessment data with <pre>$sql</pre> Cause: " . $dbConn->ErrorMsg() . "\n");
}
extract($resultSet->fields);
if ($assessment_count != 0) {
    $widget = $prjSel->getWidget();
} else {
    $widget = "<h1>Sorry, you are not enlisted for an assessment</h1>";
}
//
if ($grp_open)
    $gradetype = $langmap['gradetype'][$lang];
else
    $gradetype = $langmap['closed'][$lang];
//    echo "post 6 prj_id_milestone = $prj_id:$milestone<br/>"; 
// show photos of group members
$pg = new GroupPhoto($dbConn, $prjtg_id);
$pg->setWhereConstraint(" not snummer=$snummer ");
$pg->setPictSize('84', '126');
$pg->setMaxCol(8);
$criteria = getCriteria($prjm_id);
$rainbow = new RainBow(STARTCOLOR, COLORINCREMENT_RED, COLORINCREMENT_GREEN, COLORINCREMENT_BLUE);
if ($isTutor) {
    $tutor_opener = "<fieldset style='background:#fff'>
	<legend>For tutors</legend>
	If you are a tutor you could use this page and the next to enter a participant's data, or just simply assume any participant's role.
	  <form name='reopenform' method='post' action='$PHP_SELF'>
	  <input type='hidden' name='prjtg_id' value='$prjtg_id'/>
	  <input type='hidden' name='judge' value='$judge'/>
          To let this person of a group correct his or her values, re-open the assessment for the group by clicking this button.
	  <input type='submit' name='reopen' value='Re open'/>
	  </form>
	</fieldset>";
} else {
    $tutor_opener = '<br/>';
}

if (isSet($prjtg_id)) {
    $sql = "SELECT ca.contestant,roepnaam||coalesce(' '||voorvoegsel,'')||' '||achternaam||coalesce(' ('||role||')','') as naam ,ca.prj_id,\n" .
            "grp_num,criterium,milestone,grade,coalesce(remark,'') as remark from contestant_assessment ca \n" .
            " left join student_role sr on(ca.prjm_id=sr.prjm_id and ca.contestant=sr.snummer)\n" .
            " left join project_roles pr on(ca.prj_id=pr.prj_id and sr.rolenum=pr.rolenum)\n" .
            " natural left join assessment_remarks ar  \n" .
            "where ca.judge=$judge and ca.prjtg_id=$prjtg_id \n" .
            "order by achternaam,contestant,criterium";

    //    $dbConn->log($sql);
    // echo "<pre style='text-align:left'>$sql</pre>\n";

    $tableString = groupAssessmentTableHelper($dbConn, $sql, $grp_open, true, $criteria, $lang, $rainbow);
} else {
    $tableString = "<p>No project group selected</p>";
}
if ($grp_open) {
    $colspan = 2 + count($criteria);
} else {
    $colspan = count($criteria);
}
$bottomrow = '';
if ($grp_open) {
    $toprow = "<tr><th></th><td align='right' colspan='$colspan'>$replyText<input type='reset' name='resetlow' value='Reset form'/></td></tr>";
    $bottomrow = "<tr><td><input type='hidden' name='peerdata' value='grade'/>
                                    <input type='hidden' name='prjtg_id' value='$prjtg_id'/>
                                </td>
                                <td align='right' colspan='$colspan'><input type='submit' name='submit'/>
                                </td></tr>";
} else {
    $toprow = '';
    $bottomromw = '';
}
?>
<div id="content" style='padding:1em;'>
    <?= $prjSel->getWidget() ?>
    <?php
    if (!$prjSel->isEmptySelector()) {
        ?><div class='navleft selected' style='padding-left:0pt;'>

            <fieldset class="control">
                <legend>Assessment form</legend>
                <h2 align='center'>Assessment for <?= $afko ?> <?= $year ?> <?= $description ?>
                    <br/>group <?= $grp_num ?> (<?= $grp_alias ?>)
                    <br/>for Student <?= $student_data ?>
                </h2>
                <form method="post" name="assessment" action="<?= $PHP_SELF ?>" onsubmit="return confirm('Are you sure you want to submit these data?')">
                    <h4 align='center'><?= $gradetype ?></h4>
                    <?= $pg->getGroupPhotos() ?>
                    <table align='center' class='navleft'>
                        <tr><th><?= $langmap['criteria'][$lang] ?></th>
                            <th><?= $langmap['verklaring'][$lang] ?></th></tr>
                        <?= getCriteriaList($criteria, $lang, $rainbow) ?>

                    </table>
                    <table align='center' class='tabledata' border='1'>
                        <?= $toprow ?>
                        <?= $tableString ?>
                        <?= $bottomrow ?>
                    </table>
                </form>
                <?= $tutor_opener ?>
            </fieldset>
        </div>
    </div>
    <!-- db_name=<?= $db_name ?> $Id: ipeer.php 1825 2014-12-27 14:57:05Z hom $ -->
    <?php
}
$page->addBodyComponent(new Component(ob_get_clean()));
$page->addBodyComponent($dbConn->getLogHtml());
$page->show();
?>