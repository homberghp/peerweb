<?php

requireCap(CAP_TUTOR);
include_once('navigation2.php');
require_once 'prjMilestoneSelector2.php';
require_once 'maillists.inc.php';
require_once 'TemplateWith.php';

if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
    include_once 'templates/getrealbrowser.html';
    exit(0);
}
$maillist_dir = '/home/maillists';
//$dbConn->setSqlAutoLog( $db_name <> 'peer' );
requireCap(CAP_TUTOR);
$prjm_id = 0;
$prj_id = 1;
$milestone = 1;
extract($_SESSION);

$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
$prjSel->setWhere(" exists (select * from prj_grp pg join prj_tutor pt on (pg.prjtg_id=pt.prjtg_id) where pt.prjm_id=pm.prjm_id)");
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
if (isSet($_POST['maillist'])) {
    createMaillists($dbConn, $prjm_id);
}

$prjm_id_selector = $prjSel->getWidget(); //getSelector();
$mail_button = "&nbsp;";

if ($isTutorOwner) {
    if (isSet($_POST['bmove'])) {
        if ($_POST['bmove'] == 'Move' && isSet($_POST['members'])) {
            $members = $_POST['members'];
            $grp_num = $_POST['grp_num'];
            $memberset = '\'' . implode("','", $_POST['members']) . '\'';
            // first test if move is allowed
            $sql = "select count(*) as assessment_count \n" .
                    "from assessment a join prj_tutor pt on (a.prjtg_id=pt.prjtg_id) where pt.prjm_id=$prjm_id and grade<>0 \n" .
                    " and (contestant in ($memberset) or judge in ($memberset))";
            $resultSet = $dbConn->Execute($sql);
            //$dbConn->log($sql);
            if ($resultSet === false) {
                echo ( "<br>Cannot read assessment_count with " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>");
                stacktrace(1);
                die();
            }
            // get target group id
            $sql = "select prjtg_id as new_prjtg_id from prj_tutor where prjm_id=$prjm_id and grp_num=$grp_num";
            $resultSet = $dbConn->Execute($sql);
            if ($resultSet === false) {
                echo ( "<br>Cannot execute with " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>");
                stacktrace(1);
                die();
            }
            extract($resultSet->fields);
            // only allow update if assessment_input from a student is empty (student hase not voted)
            //	    if ($resultSet->fields['assessment_count'] == 0 ) {
            $sql = "BEGIN work;\n" .
                    "DELETE FROM assessment where prjtg_id in (select prjtg_id from prj_tutor where prjm_id=$prjm_id) \n" .
                    " AND (judge IN ($memberset) or contestant in ($memberset));\n" .
                    "UPDATE prj_grp set prj_grp_open=false,prjtg_id=$new_prjtg_id \n" .
                    "where snummer in ($memberset) and prjtg_id in (select prjtg_id from prj_tutor where prjm_id=$prjm_id);\n" .
                    "update prj_milestone set prj_milestone_open=false where prjm_id=$prjm_id;\n" .
                    "COMMIT";
            //		$dbConn->log($sql);
            $resultSet = $dbConn->Execute($sql);

            if ($resultSet === false) {
                echo( "<br>Cannot update projecgroups with <pre>" . $sql . "</pre><br/>reason " . $dbConn->ErrorMsg() . "<br>");
                stacktrace(1);
                //die();
            }
            //	    }
        }
    }
    if (isSet($_POST['delete']) && isSet($_POST['members'])) {
        $memberset = '\'' . implode("','", $_POST['members']) . '\'';
        $sql = "begin work;\n" .
                "delete from assessment where prjtg_id in (select prjtg_id from prj_tutor where prjm_id = $prjm_id) \n" .
                " and judge in ($memberset);\n" .
                "delete from assessment where prjtg_id in (select prjtg_id from prj_tutor where prjm_id = $prjm_id) \n" .
                " and contestant in ($memberset);\n" .
                "delete from prj_grp where prjtg_id in (select prjtg_id from prj_tutor where prjm_id = $prjm_id)\n" .
                " and snummer in ($memberset);\n" .
                "commit";
        //    echo "<pre>$sql</pre>";
        $resultSet = $dbConn->Execute($sql);
        if ($resultSet === false) {
            print 'error deleting reason: ' . $dbConn->ErrorMsg() . '<br> with' . $sql . '<br>';
            $dbConn->Execute('rollback');
        }
    }
    $sql = "select lower(rtrim(afko)) as afko,year,lower(btrim(course_short)) as course from project natural join prj_milestone natural join fontys_course where prjm_id=$prjm_id";
    $resultSet = $dbConn->Execute($sql);
    extract($resultSet->fields);
}

$studentListQuery = "SELECT apt.grp_num||': '||achternaam||', '||roepnaam||' '||" .
        "coalesce(tussenvoegsel,'')||';'||coalesce(cl.sclass,'null-class')::text AS name,\n" .
        "st.snummer as value,'cohort='||cohort as title,\n" .
        "apt.grp_num||', '||tutor||coalesce(':'||grp_name,'')||' '\n" .
        "||(case when apt.prj_tutor_open=true then 'open' else 'closed' end) as namegrp,\n" .
        " apt.grp_num,nationaliteit\n" .
        " from (select prjtg_id,prjm_id,grp_num,prj_tutor_open,tutor_id,grp_name from prj_tutor where prjm_id=$prjm_id) apt\n".
        "  join prj_grp pg using(prjtg_id) join student_email st using (snummer)\n" .
        " join student_class cl using(class_id) \n" .
        " join tutor t on(userid=tutor_id)".
        " left join grp_alias using(prjtg_id)". 
        " WHERE apt.prjm_id=$prjm_id\n".
        " order by grp_num,achternaam,roepnaam";

$dbConn->log($studentListQuery);
$studentList = getOptionListGrouped($dbConn, $studentListQuery, $grp_num, 'grp_num');
$isAdmin = hasCap(CAP_SYSTEM) ? 'true' : 'false';

$sql = "select tutor,tutor_id from prj_tutor join tutor on(prj_tutor.tutor_id=tutor.userid)"
        . " where prjm_id=$prjm_id and grp_num='$grp_num'";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    print 'error selecting: ' . $dbConn->ErrorMsg() . '<br> with ' . $sql . ' <br/>';
}
if (!$resultSet->EOF)
    $tutor = $resultSet->fields['tutor'];
$sql = "select grp_num||' '||coalesce(grp_name,'g'||grp_num)||': '||achternaam||', '||roepnaam||' '" .
        "||coalesce(tussenvoegsel,'')||' ('||faculty.faculty_short||':'" .
        "||tutor.tutor||';'||tutor.userid||')' as name,\n" .
        " grp_num as value" .
        " from prj_tutor join tutor on(tutor.userid=prj_tutor.tutor_id)\n" .
        " join student_email on (userid=snummer)\n" .
        " join faculty on (faculty.faculty_id=tutor.faculty_id)\n" .
        " natural left join grp_alias \n " .
        " where prjm_id=$prjm_id order by grp_num";
//$dbConn->log($sql);
$grpList = getOptionList($dbConn, $sql, $grp_num);
// test to see if the tables are already filled with data

$sql = "select count(*) as rowcount from assessment a join prj_tutor pt on (a.prjtg_id=pt.prjtg_id) where pt.prjm_id=$prjm_id and grade != 0";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    print 'cannot get row count: ' . $dbConn->ErrorMsg() . '<br> with' . $sql . '<br>';
}
$rowcount = $resultSet->fields['rowcount'];
$fillButton = $clearButton = '&nbsp;';
$sql = "select count(*) as filled_count from assessment a join prj_tutor pt on (a.prjtg_id=pt.prjtg_id) where pt.prjm_id=$prjm_id ";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    print( "<br>Cannot get project data with <pre>$sql</pre>, cause" . $dbConn->ErrorMsg() . "<br>");
}
$filled_count = $resultSet->fields['filled_count'];
$filled = $resultSet->fields['filled_count'] ? 'filled' : '<span style=\'color:yellow;\'>not</span> filled';

$filledState = 0;
if ($filled_count != 0)
    $filledState +=1;
if ($rowcount != 0)
    $filledState +=2;
// encoding 0: empty, 1, filled, 2 do not care, 3 filled and assessed
$enableText = "Connect groups";
$disableText = "Disconnect groups";
$updateButton = "&nbsp;";
$deleteButton = "&nbsp;";
$open_button = "&nbsp;";
$close_button = "&nbsp;";
if ($isTutorOwner) {
    $deleteButton = "<input type='submit' name='delete' value='Delete members' " .
            "title='Throw members out of this project' />";
    $updateButton = "<button type='submit' name='bmove' value='Move' " .
            "title='Update the group composition' >Move student(s)</button>";
    $sql = "select prj_milestone_open from prj_milestone where prjm_id=$prjm_id";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        print( "<br>Cannot get open close data <pre>$sql</pre>, cause" . $dbConn->ErrorMsg() . "<br>");
    }
    $assessment_open = false;
    while (!$resultSet->EOF) {
        $assessment_open = $assessment_open || ($resultSet->fields['prj_milestone_open'] == 't');
        $resultSet->moveNext();
    }
}
$page = new PageContainer();
$page_opening = "Put participants into groups";
$page_opening = "Select participants into project groups&nbsp;" .
        "<span style='font-size:8pt;'>prj_id $prj_id milestone $milestone prjm_id $prjm_id</span>";
$page->setTitle('Put participants into groups');
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);

extract(getTutorOwnerData2($dbConn, $prjm_id), EXTR_PREFIX_ALL, 'ot');

$page->addBodyComponent($nav);
$templatefile = 'templates/defgroupmembers3.html';
$ie_warning = strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') ? "Internet Exploder is not supported" : "";
$template_text = file_get_contents($templatefile, true);
if ($template_text === false) {
    $page->addBodyComponent(new Component("<strong>cannot read template file $templatefile</strong>"));
} else {
    $page->addBodyComponent(new Component(templateWith($template_text, get_defined_vars())));
}
$page->addHeadText('
<script src="' . $root_url . '/js/scriptaculous/prototype.js" type="text/javascript"></script>
<script src="' . $root_url . '/js/scriptaculous/scriptaculous.js" type="text/javascript"></script>
<script src="' . $root_url . '/js/scriptaculous/OptionTransfer.js" type="text/javascript"></script>
<script type="text/javascript">
var ot = new OptionTransfer("leftselect","rightselect");

</script>

');
$page->setBodyTag("<body id='body' class='{$body_class}' onLoad='ot.init(document.forms.grpmembers)'>");
$page->show();
?>
