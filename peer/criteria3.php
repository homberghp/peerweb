<?php

include_once('peerutils.php');
include_once('navigation2.php');
require_once 'prjMilestoneSelector2.php';
require_once 'TemplateWith.php';
//include_once 'project_selector.php';
requireCap(CAP_TUTOR);
extract($_SESSION);
$critcount = 4;
$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
extract($prjSel->getSelectedData());
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;

if (isSet($_POST['criterium_id']) && isSet($_POST['setcrit'])) {
    $critset = implode(",", $_POST['criterium_id']);
    $sql = "begin work;\n"
            . "delete from prjm_criterium where prjm_id=$prjm_id and criterium_id not in ($critset);\n"
            . "insert into prjm_criterium select $prjm_id,criterium_id from base_criteria \n"
            . " where criterium_id in ($critset) and ($prjm_id,criterium_id) not in (select prjm_id,criterium_id from prjm_criterium);\n"
            . "commit\n";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        $dbConn->log("cannot insert appplied criteria with <pre>$sql</pre>, reason: " . $dbConn->ErrorMsg() . "<br/>\n");
        $dbConn->Execute("rollback;");
    }
}
$prj_id = isSet($_SESSION['prj_id']) ? $_SESSION['prj_id'] : -1;
extract(getTutorOwnerData($dbConn, $prj_id));
$_SESSION['prj_id'] = $prj_id;
$isTutorOwner = ($tutor == $tutor_code);
$page = new PageContainer();
$page->setTitle('Peer assessment, define project');
$page_opening = "Define the number of criteria for the project.";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$page->addBodyComponent($nav);
$form1 = new HtmlContainer("<fieldset id='form1'><legend><b>Project milestone and number of criteria.</b></legend>");
$form1Form = new HtmlContainer("<form id='project' method='post' name='project' action='$PHP_SELF'>");
$input_prj_selector = "<select name='prj_id' onchange='submit()'>\n" .
        getOptionListGrouped($dbConn, "select afko||': '||description||' ('||year||')' as name" .
                ", year as namegrp,prj_id as value from project order by year desc,afko", $prj_id) . "\n</select>\n";
if ($isTutorOwner) {
    $input_num_criteria = "<input class='" . $validator->validationClass('critcount') . "' type='text' align='right' size='1' maxlength='2' name='critcount' value='$critcount'/>";
    $input_submit_button = "<input type='submit' name='setcrit' value='Set Criteria'/>";
} else {
    $input_num_criteria = "$critcount";
    $input_submit_button = '';
}
$project_selector = $prjSel->getSelector();

$sql = "select bc.criterium_id as bc,pc.criterium_id as ac,pc.criterium_id as uc,nl_short,nl,de_short,de,en_short,en\n"
        . " from base_criteria bc natural left join (select prjm_id,criterium_id from prjm_criterium \n"
        . "   where prjm_id=$prjm_id) pc order by bc";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    $dbConn->log("cannot get date with $sql, reason: " . $dbConn->ErrorMsg() . "<br/>\n");
}
$rainbow = new RainBow(STARTCOLOR, COLORINCREMENT_RED, COLORINCREMENT_GREEN, COLORINCREMENT_BLUE);
$table = "<table style='border-collapse:collapse' border='1'>
<tr valign='top' >
   <th>C</th>
   <th>S</th>
   <th>U</th>
   <th colspan='2'>NL</th>
   <th colspan='2'>DE</th>
   <th colspan='2'>EN</th>
   </tr>";
while (!$resultSet->EOF) {
    extract($resultSet->fields);
    $color = $rainbow->getNext();
    $checked = (isSet($ac) && '' != $ac) ? 'checked' : '';
    $table .= "<tr valign='top' style='background:$color'>
   <td>$bc</td>
   <td > <input type='checkbox' name='criterium_id[]', value='$bc' $checked/></td>
   <td>$uc</td>
   <td >$nl_short</td>
   <td >$nl</td>
   <td >$de_short</td>
   <td >$de</td>
   <td >$en_short</td>
   <td >$en</td>
   </tr>
";

    $resultSet->moveNext();
}
$table .= "</table>";
//$form1Form->addText($table);
$templatefile = 'templates/criteria3.html';
$template_text = file_get_contents($templatefile, true);
if ($template_text === false) {
    $form1Form->addText("<strong>cannot read template file $templatefile</strong>");
} else {
    $text = templateWith($template_text, get_defined_vars());
    $form1Form->addText($text);
}

$form1->add($form1Form);
$page->addBodyComponent($form1);
$page->addBodyComponent(new Component('<!-- db_name=' . $db_name . ' $Id: criteria3.php 1723 2014-01-03 08:34:59Z hom $ -->'));
$page->show();
?>
