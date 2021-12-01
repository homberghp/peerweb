<?php

requireCap(CAP_TUTOR_OWNER);
require_once 'querytotable.php';
require_once 'navigation2.php';
require_once 'project_selector.php';
require_once 'TemplateWith.php';

requireCap(CAP_TUTOR);
$milestones = 1;
extract($_SESSION);
$year = date('Y');
$milestone_span = 14 * 86400; // think of better default
if (date('m') < 07) {
    $year -= 1;
}
if (isSet($VPOST['prj_id'])) {
    $_SESSION['prj_id'] = $prj_id = $VPOST['prj_id'];
}
if (isSet($VPOST['milestone'])) {
    $_SESSION['milestone'] = $milestone = $VPOST['milestone'];
}
if (!isSet($_SESSION['prj_id'])) {
    // smart guess
    $sql = "select prj_id,afko,year,description from project\n" .
            " where prj_id=(select max(prj_id) as prj_id from project)";
    $resultSet = $dbConn->query($sql);
    if ($resultSet === false) {
        die("<br>Cannot get smart project data with $sql, cause" . $dbConn->errorInf()[2] . "<br>");
    }

    if (($row=$resultSet->fetch())===false) {
        $prj_id = 0;
    } else {
        extract($row);
    }
    $_SESSION['prj_id'] = $prj_id;
}
if (isSet($_SESSION['prj_id'])) {
    $sql = "select prj_id,afko,description,year,max(milestone) as milestones from " .
            "project p left join prj_milestone m using (prj_id) where prj_id={$prj_id} group by prj_id,afko,description,year";
    $resultSet = $dbConn->query($sql);
    if ($resultSet === false) {
        die("<br>Cannot get sequence next value with " . $dbConn->errorInf()[2] . "<br>");
    }
    $row=$resultSet->fetch();
    $afko = $row['afko'];
    $description = $row['description'];
    $year = $row['year'];
    if (isSet($row['milestones'])) {
        $milestones = $row['milestones'];
    } else {
        $milestones = 0;
    }
}
if (isSet($VPOST['baddmil'])) {
    if ($VPOST['baddmil'] == 'AddMil' && isSet($VPOST['prj_id'])) {
        // now get the max present and add to what's needed
        $sql = "select max(milestone) as milestone from prj_milestone where prj_id=$prj_id";
        $resultSet = $dbConn->Execute($sql);
        if ($resultSet === false) {
            die("<br>Cannot get max with " . $sql . " reason " . $dbConn->errorInf()[2] . "<br>");
        }
        if (isSet($resultSet->fields['milestone'])) {
            $milestone = $resultSet->fields['milestone'] + 1;
        } else {
            $milestone = 1;
        }
        $milestones = $milestone + 1;
        $milestone_date = time() + $milestone_span;
        while ($milestone < $milestones) {
            $assessment_due = date('Y-m-d', $milestone_date);
            $sql = "insert into prj_milestone (prj_id,milestone,prj_milestone_open,assessment_due) \n" .
                    " values( $prj_id,$milestone,false,'$assessment_due')";
            $resultSet = $dbConn->Execute($sql);
            if ($resultSet === false) {
                die("<br>Cannot update milestone values with " . $sql . " reason " . $dbConn->errorInf()[2] . "<br>");
            }
            $milestone++;
            $milestone_date += $milestone_span;
        }
    }
    $_SESSION['milestone'] = $milestones; // save last used value as default
} else if (isSet($VPOST['submitdue']) && isSet($VPOST['assessment_due'])) {
    $sql = "begin work;\n";
    $PPOST=$VPOST;
    for ($i = 0; $i < count($PPOST['assessment_due']); $i++) {
        $assessment_due = $PPOST['assessment_due'][$i];
        $weight = $PPOST['weight'][$i];
        $milestone_name = $PPOST['milestone_name'][$i];
        $_prjm_id = $PPOST['prjm_id'][$i];
        $has_assessment = isSet($PPOST['has_assessment'][$i]) ? 'true' : 'false';
        $isPublic = isSet($PPOST['public'][$i]) ? 'true' : 'false';
        $sql .= "update prj_milestone set assessment_due='{$assessment_due}',"
                . " weight={$weight}, milestone_name='{$milestone_name}',has_assessment={$has_assessment},public={$isPublic} "
                . " where prjm_id={$_prjm_id};\n";
    }
    $sql .= "commit";
//    echo "<pre>{$sql}</pre>";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        echo( "<br>Cannot update milestone values with " . $sql . " reason " . $dbConn->errorInf()[2] . "<br>");
    }
}

$prj_id = isSet($_SESSION['prj_id']) ? $_SESSION['prj_id'] : -1;
extract(getTutorOwnerData($dbConn, $prj_id));
$_SESSION['prj_id'] = $prj_id;
$isTutorOwner = ($tutor == $tutor_code);
$page = new PageContainer();
$self = basename(__FILE__);
$page->setTitle('Define the number of assessments (milestones) in the project.');
$page_opening = "Define the number of assessments (milestones) in the project. <font style='font-size:6pt;'>prj_id $prj_id</font>\n";
$nav = new Navigation($tutor_navtable, $self, $page_opening);
$page->addBodyComponent($nav);
$nav->setInterestMap($tabInterestCount);
$form1 = new HtmlContainer("<fieldset id='form1'><legend><b>Project milestones.</b></legend>");
$form1->addText("<p>A project starts life with one milstone. If you need to add another, this "
        . "is the page to be. Here you can also set the due dates for the assessment milstones.</p>");

$form1Form = new HtmlContainer("<form id='project' method='post' name='project' action='{$self}'>");

// ."<!--<input type='submit' name='baddmil' value='Get'>-->";
if ($isTutorOwner) {
    $submit_button = "<button type='submit' name='baddmil' value='AddMil'>Add a milestone</button>";
} else {
    $submit_button = '';
}
$project_selector = getProjectSelector($dbConn, $peer_id, $prj_id);

$templatefile = '../templates/addmilestone.html';
$template_text = file_get_contents($templatefile, true);
if ($template_text === false) {
    $form1Form->addText("<strong>cannot read template file $templatefile</strong>");
} else {
    $form1Form->addText(templateWith($template_text, get_defined_vars()));
}
$form1->add($form1Form);
$page->addBodyComponent($form1);
$form2 = new HtmlContainer("<fieldset><legend>Defined milestones and due dates.</legend>");
$form2->addText("<p>After you determined the number of milestones, select the due dates. " .
        "(Defaults are 14 days from now).</p>"
        . "<p>Weight is used in grade calculation with more milestones per project, <br/>" .
        "the name is optional and describes the purpose of the milestone/grade and weight.</p>");
$form2Form = new HtmlContainer("<form method='post' name='duedates' action='{$self}'>");

$sql = "select milestone  , prjm_id, assessment_due,weight, milestone_name,\n" .
        "  case when prj_milestone_open=true then  'open' else 'closed' end as open, has_assessment, public \n" .
        " from prj_milestone where prj_id=$prj_id order by milestone";
//$inputColumns = array('1' => array('type' => 'N', 'size' => '12'));
ob_start(); // collect table data
// column '0' = M<milestone>
$inputColumns = array(//'0' => array( 'type' => 'T', 'size' => '4'),
    '1' => array('type' => 'H', 'size' => '2'),
    '2' => array('type' => 'D', 'size' => '10'),
    '3' => array('type' => 'N', 'size' => '3'),
    '4' => array('type' => 'T', 'size' => '20'),
    '6' => array('type' => 'B', 'size' => '2'),
    '7' => array('type' => 'B', 'size' => '2'),
        //'2' => array( 'type' => 'B', 'size' => '1', 'colname' => 'open' ),
);
$datePickers = array();
queryToTableChecked2($dbConn, $sql, true, 0, new RainBow(0x46B4B4, 64, 32, 0), 'open[]', array(), $inputColumns);
$form2Form->addText(ob_get_clean());
$form2Form->addText("<input type='hidden' name='prj_id' value='$prj_id' />\n" .
        "<input type='submit' name='submitdue' value='Update' />\n" .
        "<input type='reset' name='reset' value='Reset' />");
$form2->add($form2Form);
$page->addBodyComponent($form2);
$page->addBodyComponent(new Component('<!-- db_name=$db_name $Id: addmilestone.php 1769 2014-08-01 10:04:30Z hom $ -->'));
$page->addHeadText(file_get_contents('../templates/simpledatepicker.html'));
$page->addScriptResource('js/jquery.min.js');
$page->addScriptResource('js/jquery-ui-custom/jquery-ui.min.js');

if (count($datePickers) > 0) {
    foreach ($datePickers as $dp) {
        $page->addJqueryFragment("$('#" . $dp . "').datepicker(dpoptions);");
    }
}
$page->show();
