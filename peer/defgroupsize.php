<?php
include_once('./peerlib/peerutils.php');
include_once('navigation2.php');
require_once 'prjMilestoneSelector2.php';
require_once 'SpreadSheetWriter.php';

requireCap(CAP_TUTOR);
$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
extract($_SESSION);

$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
extract($prjSel->getSelectedData());
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;
$sqlhead = "select distinct prj_id,milestone,afko,year,grp_num,tutor,rtrim(alias) as alias,long_name,productname,\n"
        . "  snummer as gm_snumber,roepnaam||coalesce(' '||voorvoegsel||' ',' ')||achternaam as gm_name,rtrim(email1) as gm_email,\n"
        . "  website,"
        . " youtube_link \n"
        . " from all_prj_tutor_y apt \n"
        . " left join (select sr.*,s.achternaam,s.roepnaam,s.voorvoegsel,s.email1 from student_role \n"
        . " join (select snummer,prjtg_id from prj_grp join all_prj_tutor using(prjtg_id)\n"
        . " where prjm_id=$prjm_id) sr using(snummer) join student s using(snummer) where prjm_id=$prjm_id and rolenum=1) gm using (prjtg_id)\n"
        . " where (now()::date < valid_until) and (apt.prjm_id = $prjm_id)\n"
        . " order by grp_num ";
$spreadSheetWriter = new SpreadSheetWriter($dbConn
        , $sqlhead);
$filename = 'group_defs';
$title = 'Group definitions';
$spreadSheetWriter->setFilename($filename)
        ->setLinkUrl($server_url . $PHP_SELF . '?prjm_id=' . $prjm_id)
        ->setTitle($title)
        ->setAutoZebra(true);

$spreadSheetWriter->processRequest();

$spreadSheetWidget = $spreadSheetWriter->getWidget();

//$dbConn->log("prj mile etc $prj_id, $prjm_id, $milestone");
pagehead('Select group count and tutors');
$grp_count = 5;
$tutor_id = $peer_id;
//include 'prjm_idRequest.php';
// determine the number of groups currently present 
// to advise about the number of tutur-grp pairs
$isTutorOwner = checkTutorOwnerMilestone($dbConn, $prjm_id, $peer_id);
//// check if this is tutor_owner of this project
//$dbConn->log('istutorowner='.$isTutorOwner."<br/>\n");
$sql = "select count(distinct grp_num) as org_grp_count from prj_grp join prj_tutor using(prjtg_id) where prjm_id = $prjm_id";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    echo( "<br>Cannot get group count with with <pre>\"" . $sql . '"</pre>, cause ' . $dbConn->ErrorMsg() . "<br>");
    stacktrace(1);
    die();
}
extract($resultSet->fields);

$sql = "select count(snummer) as student_count from prj_grp join prj_tutor using(prjtg_id) where prjm_id=$prjm_id";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    echo( "<br>Cannot set prj tutors with <pre>\"" . $sql . '"</pre>, cause ' . $dbConn->ErrorMsg() . "<br>");
    stacktrace(1);
    die();
}
extract($resultSet->fields);
$oldmaxgrp = 0;
if ($isTutorOwner && isSet($_REQUEST['bgcount'])) {
    $grp_count = $_REQUEST['grp_count'];
    // to prevent orphaning of groups, make the new group count the minimally equal to the
    // original group count
    if ($grp_count < $org_grp_count) {
        $grp_count = $org_grp_count;
    }
    // first get max group
    $sql = "select max(grp_num) as oldmaxgrp from prj_tutor where prjm_id=$prjm_id group by prjm_id";
    //    echo "<pre>\n";
    //    echo "$sql\n";
    //    echo "<pre>\n";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        echo( "<br>Cannot set prj tutors with <pre>\"" . $sql . '"</pre>, cause ' . $dbConn->ErrorMsg() . "<br>");
        stacktrace(1);
        die();
    }
    if (!$resultSet->EOF)
        extract($resultSet->fields);
    //    echo "grp count=$grp_count old max = $oldmaxgrp<br/>";
    $newmaxgrp = $grp_count;
    $grp_num = $oldmaxgrp;
    $sql = "begin work;\n" .
            "delete from prj_tutor pt where prjm_id=$prjm_id \n" .
            " and (grp_num > $newmaxgrp)\n" .
            " and grp_num not in (select distinct pt.grp_num \n" .
            "from prj_grp pg join prj_tutor pt using(prjtg_id) " .
            "where pt.prjm_id=$prjm_id) ;\n";
    $i = 0;
    //    $dbConn->log($sql);
    $oldmaxgrp++;
    $grp_num = $oldmaxgrp;
    while ($grp_num <= $newmaxgrp) {
        $grp_name = ($grp_num == $newmaxgrp)?'Attic':"g{$grp_num}";
        $sql .="insert into prj_tutor (prjm_id,tutor_id,grp_num,grp_name) " .
                "select $prjm_id,$tutor_id,$grp_num,'$grp_name' from tutor where userid='$tutor_id';\n";
        $grp_num++;
    }

    $sql .= "commit\n";

    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        echo( "<br>Cannot set number of project tutors with <pre>\"" .
        $sql . '"</pre>, cause ' . $dbConn->ErrorMsg() . "<br>");
        stacktrace(1);
        $resultSet = $dbConn->Execute("rollback");
    } else {
        $resultSet = $dbConn->Execute("commit");
    }
}
$sql = "select count( distinct grp_num) as grp_count from prj_tutor where prjm_id=$prjm_id group by prjm_id";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    echo( "<br>Cannot get group count with <pre>\"" . $sql . '"</pre>, cause ' . $dbConn->ErrorMsg() . "<br>");
    stacktrace(1);
    die();
}
if (!$resultSet->EOF) {
    extract($resultSet->fields);
}
//echo "grp_num count=$grp_count<br/>\n";
if ($grp_count > 0) {
    $sql = "select count(*)/$grp_count as grp_size from prj_grp join prj_tutor using(prjtg_id) where prjm_id=$prjm_id";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        echo( "<br>Cannot get prj tutors with <pre>\"" . $sql . '"</pre>, cause ' . $dbConn->ErrorMsg() . "<br>");
        stacktrace(1);
        die();
    }
    extract($resultSet->fields);
}
else
    $grp_size = 0;
if ($isTutorOwner && isSet($_REQUEST['btutor'])) {
    $tutors = $_REQUEST['tutor_id'];
    $prjtg_ids = $_REQUEST['prjtg_id'];
    $grp_names = $_REQUEST['grp_name'];
    $sql = "begin work;\n";
    for ($i = 0; $i < count($tutors); $i++) {
        $grp_names[$i] = pg_escape_string($grp_names[$i]);
        $sql .="update prj_tutor set tutor_id= {$tutors[$i]},grp_name='{$grp_names[$i]}' where prjtg_id=$prjtg_ids[$i];\n";
    }

    $sql .="commit;";
    //echo "<pre>$sql</pre>";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        echo( "<br>Cannot set prj tutors with <pre>\"" . $sql . '"</pre>, cause ' . $dbConn->ErrorMsg() . "<br>");
        stacktrace(1);
        die();
    }
}
$sql = "select assessment_due from prj_milestone where prjm_id=$prjm_id";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    $dbConn->log('<br>Cannot set prj tutors with <pre>' . $sql . '</pre> cause ' . $dbConn->ErrorMsg() . "<br/>" .
            stacktracestring(1));
} else if (!$resultSet->EOF)
    extract($resultSet->fields);
$page_opening = "Select the number of groups and allocate the tutors. prjm_id $prjm_id prj_id $prj_id milestone $milestone";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$sqltut = "select prjtg_id,t.tutor,pt.tutor_id,pt.grp_num, "
        . "gs.size as scount, rtrim(grp_name) as grp_name\n"
        . " from prj_tutor pt \n"
        . " join tutor t on (t.userid=pt.tutor_id)\n"
        //. " natural left join grp_alias  ga \n"
        . " left join grp_size gs using(prjtg_id)\n"
        . "where prjm_id=$prjm_id \n"
        . "order by grp_num asc";
//echo "<pre>$sqltut</pre>";

$resultSet = $dbConn->Execute($sqltut);
if ($resultSet === false) {
    echo( "<br>Cannot get groups with \"" . $sqltut . '", cause ' . $dbConn->ErrorMsg() . "<br>");
    stacktrace(1);
    die();
}
$rowCounter = 1;
$rows = '';
while (!$resultSet->EOF) {
    extract($resultSet->fields);
    $rowClass = (($rowCounter % 2) === 0) ? 'even' : 'odd';
    if ($isTutorOwner) {
        $tutorList = "\t\t<select name='tutor_id[]'>\n" .
                getOptionListGrouped($dbConn, "select achternaam||', '||roepnaam||' '||coalesce(voorvoegsel,'')" .
                        "||' ('||tutor||')'||t.userid as name,\n" .
                        " t.userid as value,\n" .
                        " f.faculty_short||'-'||team   as namegrp" .
                        " from tutor t join student s on (userid=snummer)\n" .
                        " join faculty f on (t.faculty_id=f.faculty_id)\n" .
                        " order by namegrp,achternaam,roepnaam", $tutor_id) .
                "\t\t</select>\n";
    } else {
        $sql = "select achternaam||', '||roepnaam||' '||coalesce(voorvoegsel,'')||' ('||tutor||')' as name\n" .
                " from tutor join student on (userid=snummer)\n" .
                "where tutor='$tutor'";
        $resultSet2 = $dbConn->doOrDie($sql);
        $tutorList = $resultSet2->fields['name'];
    }

    $rows .= "\t<tr class='$rowClass'>"
            . "<td rowspan='1'>$grp_num <input type='hidden' name='prjtg_id[]' value='$prjtg_id'/></td>\n"
            . "<td rowspan='1'>\n"
            . "\t\t\t" . $tutorList
            . "</td>\n"
            . "<td align='right' rowspan='1'>$scount</td>\n"
            . "<td rowspan='1'>$prjtg_id</td>\n"
            . "<td rowspan='1'><input type='text' name='grp_name[]' value='$grp_name' title='short name' size='9' maxlength='15'/></td>"
            . "\n\t</tr>\n";

    $resultSet->moveNext();
    $rowCounter++;
}
if ($isTutorOwner) {
    $rows.="<tr><td>&nbsp;</td>\n"
            . "<td>"
            . "    <input type='hidden' name='grp_count' value='<?= $grp_count ?>'/>"
            . "  <input type='hidden' name='prjm_id' value='<?= $prjm_id ?>'/>"
            . "  <input type='submit' name='btutor' value='Apply'/>"
            . "</td><td colspan='3' align='right'><input type='reset' name='reset' value='Reset form'/></td>\n"
            . "</tr>";
}

$thead = "               <thead><tr><th>G</th><th>Tutor</th><th align='right'>no</th><th>prjtg</th><th>group name</th></tr></thead>";
?>
<?= $nav->show() ?>
<div id='navmain' style='padding:1em;'>
    <p>Use this page to determine the size of the groups and to allocate the tutors.</p>
    <?= $prjSel->getWidget() ?>
    <?php if ($isTutorOwner) { ?>
        <fieldset><legend>Select number of group tutors</legend>
            <form name='grpcount' method='post' action='<?= $PHP_SELF ?>'>
                <input type='text' size='2' align='right' name='grp_count' value='<?= $grp_count ?>'/>
                <input type='hidden' name='prjm_id' value="<?= $prjm_id ?>"/>
                <input type='submit' name='bgcount' value='set number of tutors/groups' />
                (Typical group size =<?= $grp_size ?>, current number of studentgroups is <?= $org_grp_count ?> )
            </form>
            <p style='color:#303'>Note that you cannot set the number of groups below the currently assigned groups. This prevents orphaning groups
                by setting the number of tutors below the number of groups. If you do want to reduce the number of groups, (re)move the group members
                from the higher numbered groups. Once a group is not used (no members, thus empty) you can remove that group.</p>
            <p style='color:#303'>Attic: A best practice has developed, which says that you should add one group named <b>Attic</b>, which helps a lot when organising 
                a project cohort into project groups.</p>
            <p><strong>If you use group names, make sure they are unique in this project, milestone combination</strong>.</p>
        </fieldset>
    <?php } ?>
    <fieldset>
        <legend>Select tutors</legend>
        <form name='grpnameandtutor' method='post' action='<?= $PHP_SELF ?>'>
            <table frame='box' border='3' style='border-collapse:3d;' rules='groups' cellpadding='3' summary='project groups and tutors'>
                <?= $thead ?>
                <?= $rows ?>
                <?php ?>
            </table>
        </form>
    </fieldset>
    <form name='spreadsheet' method='post' action='<?= $PHP_SELF ?>'>
        <?= $spreadSheetWidget ?>
    </form>
</div>
</body>
<?php echo "<!-- db_name=" . $db_name . "-->" ?>

</html>