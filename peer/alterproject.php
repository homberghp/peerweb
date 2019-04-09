<?php
requireCap(CAP_TUTOR);
require_once('peerutils.php');
require_once('navigation2.php');
require_once('validators.php');
include 'project_selector.php';

requireCap(CAP_TUTOR);
$page_opening = 'Alter a project definition';
$afko = 'WHATFR';
$description = 'Refine me or catch flies';
$comment = '';
$year = date('Y');
$prj_id = 0;
$course = 112; // default inf
extract($_SESSION);
$successReport = '';

if (date('m') < 07) {
    $year -= 1;
}
if (isSet($_REQUEST['prj_id'])) {
    $_SESSION['prj_id'] = $prj_id = validate($_REQUEST['prj_id'],'integer','0');
}

$tutor = $tutor_code;
$owner_id = $peer_id;
//$dbConn->log($tutor_code);
if (hasCap(CAP_SYSTEM) && isSet($_REQUEST['owner_id'])) {
    $owner_id = validate($_REQUEST['owner_id'], 'integer', 1);
    $sql = 'update project p set owner_id=$1 where prj_id=$2';
    $resultSet = $dbConn->Prepare($sql)->execute(array($owner_id,$prj_id));
}
// update
if ($validator_clearance) {
    if (isSet($_POST['bsubmit']) && isSet($_POST['prj_id']) && isSet($_POST['afko']) && isSet($_POST['project_description']) && isSet($_POST['year'])) {

        if (isSet($_POST['comment']))
            $comment = pg_escape_string($_POST['comment']);
        else
            $comment = '';
        $description = $_POST['project_description'];
        $afko = trim($_POST['afko']);
        $year = $_POST['year'];
        $course = $_POST['course'];
        $valid_until = validate_date($_POST['valid_until']);
        if ($_POST['bsubmit'] == 'Update' && isSet($_POST['prj_id'])) {
            $sql = "begin work;\n"
                    . "update project set prj_id=$prj_id, afko='$afko', description='$description',\n" .
                    "year=$year, comment='$comment', valid_until='$valid_until',course=$course\n" .
                    "where prj_id=$prj_id and owner_id='$peer_id';";
            if (isSet($_POST['activity_project'])) {
                $sql .= "insert into activity_project select $prj_id where $prj_id not in (select prj_id from activity_project);\n";
            } else {
                $sql .= "delete from activity_project where prj_id=$prj_id;\n";
            }
            $sql .= "commit;\n";
            $resultSet = $dbConn->Execute($sql);
            if ($resultSet === false) {
                die("<br>Cannot update project values with " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>");
            }
            $_SESSION['prj_id'] = $prj_id;
        }
    }
    //$prj_id= isSet($_SESSION['prj_id'])?$_SESSION['prj_id']:-1;
    $extra_constraint = "and owner_id='$peer_id'";
    if (hasCap(CAP_SYSTEM)) {
        $extra_constraint = '';
    }
    $sql = "select * from project where prj_id=$prj_id $extra_constraint";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die("<br>Cannot project data with <pre>$sql</pre> " . $dbConn->ErrorMsg() . "<br>");
    }
    if (!$resultSet->EOF) {
        extract($resultSet->fields);
    } else {
        $sql = "select prj_id,rtrim(afko) as afko,description,owner_id,year,comment,valid_until \n"
                . " from project where prj_id > 0 $extra_constraint order by prj_id desc limit 1";
        $resultSet = $dbConn->Execute($sql);
        if ($resultSet === false) {
            die("<br>Cannot project data with <pre>$sql</pre> " . $dbConn->ErrorMsg() . "<br>");
        }
        if (!$resultSet->EOF) {
            extract($resultSet->fields);
        }
    }
} else {
    extract($_POST);
}
//echo "<pre>$sql</pre>\n";
$_SESSION['prj_id'] = $prj_id;
extract(getTutorOwnerData($dbConn, $prj_id));
$isTutorOwner = ($owner_id == $peer_id);
$page = new PageContainer();
$page->setTitle('Alter a peerweb project definition');
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$form1 = new HtmlContainer("<div>");

//$form1Form = new HtmlContainer("<form method='post' name='project' action='$PHP_SELF'>"); // 
$project_selector = getProjectSelector($dbConn, $peer_id, $_SESSION['prj_id']);

$input_module_code = "<input type='text' size='10' maxlength='10' name='afko' class='" . $validator->validationClass('afko') . "' value='$afko' title='Progress module code'/>";
$input_year = "<input type=text size='4' maxlength='4' align='right' class='" . $validator->validationClass('year') . "' name='year' value='$year' title='starting year of scollastic year' />";
$input_description = "<input type='text' size='30' maxlength='30' class='" . $validator->validationClass('project_description') . "'name='project_description' value='$description' title='module description in 30 characters'/>\n";

$input_valid_until = "<input type='text' maxlength='10' size='8' class='" . $validator->validationClass('valid_until') . "' " .
        "name='valid_until' id='embeddedPicker' value='$valid_until' title='Project entry to be used until. Date in yyyy-mm-dd format' style='text-align:right'/>\n";

$input_comment = "<textarea class='" . $validator->validationClass('comment') . "'name='comment' cols='72' rows='5'>$comment</textarea>\n";
$input_update_button = ($isTutorOwner) ? "<input type='submit' name='bsubmit'\n" .
        "value='Update' title='Use this to update project data for project_id=$prj_id' />" : '';

$tutor_owner_form = "";

if (hasCap(CAP_SYSTEM)) {
    $tutor_sql = "select achternaam||', '||roepnaam||' '||coalesce(tussenvoegsel,'')" .
            "||' ('||tutor||')' as name,\n" .
            " userid as value,\n" .
            " faculty_short||team as namegrp," .
            "case when opl=mopl then 0 else 1 end as mine\n" .
            " from tutor t  join student_email s on (userid=snummer)\n" .
            " join faculty f on(t.faculty_id=f.faculty_id)\n" .
            " cross join (select opl  as mopl from student_email where snummer={$peer_id}) m\n" .
            "where teaches " .
            " order by mine,namegrp desc,achternaam,roepnaam";
//    echo "<pre>{$tutor_sql}</pre>";
    $tutor_owner_form = "<form name='tuto' action='$PHP_SELF' method='get'>\n" .
            "<select name='owner_id' title='set tutor_owner'>" .
            getOptionListGrouped($dbConn, $tutor_sql, $owner_id) .
            "</select>\n" .
            "<input type='hidden' name='prj_id' value='$prj_id'/>" .
            "<button type='submit' name='tutor_o'>New Owner</button>" .
            "</form>\n";
}
$input_course = "<select name='course' title='set base course'>\n" .
        getOptionListGrouped($dbConn, "select trim(course_short)||':'||trim(course_description)||'('||course||')' as name,\n"
                . " course as value,\n"
                . " faculty_short as namegrp\n"
                . " from fontys_course fc natural join faculty f\n"
                . " order by namegrp,name"
                , $course);
$sql = "select count(prj_id) as active_project_set from activity_project where prj_id=$prj_id";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    die("<br>Cannot activity_project data with <pre>$sql</pre> " . $dbConn->ErrorMsg() . "<br>");
}
$activity_project_checked = $resultSet->fields['active_project_set'] ? 'checked' : '';

$input_activity_project = "<input type='checkbox' name='activity_project' value='set' $activity_project_checked/>";

$templatefile = 'templates/alterproject.html';
$template_text = file_get_contents($templatefile, true);
if ($template_text === false) {
    $form1Form->addText("<strong>cannot read template file $templatefile</strong>");
} else {
    $form1->addText(templateWith($template_text, get_defined_vars()));
}
//$form1->add($form1Form);
$page->addBodyComponent($nav);
$page->addBodyComponent($form1);
$page->addBodyComponent(new Component('<!-- db_name=$db_name $Id: alterproject.php 1726 2014-02-03 13:54:48Z hom $ -->'));
$page->addHeadText(file_get_contents('templates/simpledatepicker.html'));
$page->addScriptResource('js/jquery.min.js');
$page->addScriptResource('js/jquery-ui.custom.min.js');
$page->addJqueryFragment('$(\'#embeddedPicker\').datepicker(dpoptions);');

$page->show();
?>
