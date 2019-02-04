<?php
requireCap(CAP_SYSTEM);

require_once 'component.php';
require_once 'navigation2.php';
require_once 'selector.php';
require_once 'GradeHarvester.php';
/**
 * Upload grades to peerweb.
 */
$gradeSet = array();
$showInput = true;
$exam_event = 0;
$event_code = '';
if (isSet($_POST['exam_event'])) {
    $_SESSION['exam_event'] = $exam_event = validate($_POST['exam_event'],'integer','0');
    $sql="select  ee.exam_event_id as value, m.progress_code||'.'||mp.progress_code||'-'||ee.exam_date as name, m.progress_code as grpname from exam_event ee join module_part mp using (module_part_id) join module m using(module_id) where exam_event_id={$exam_event}";
    $resultSet = $dbConn->Execute($sql);
    if (!$resultSet->EOF) {
        $event_code=$resultSet->fields['name'];
        
    }
}

if (isSet($_POST['grades']) && isSet($_POST['exam_event'])) {
    $_SESSION['gradeSet'] = $gradeSet = harvest($_POST['grades']);
    $showInput = (count($gradeSet) === 0);
} else if (isSet($_POST['grade']) && isSet($_POST['cand']) && isSet($_POST['commit'])) {
    $gc = count($_POST['grade']);
    $gradeSet = array();
    $exam_event=$_SESSION['exam_event'];
    for ($c = 0; $c < $gc; $c++) {
        $gradeSet[$_POST['cand'][$c]] = $_POST['grade'][$c];
    }
    $cands = implode(',', array_keys($gradeSet));
    $trans_id = $dbConn->transactionStart("enter grades for  {$exam_event}");
    $sql = "delete from exam_grades where exam_event_id = '{$exam_event}' and snummer "
            . "in ($cands) and trans_id in (select trans_id from transaction where operator=$peer_id);\n";
    $resultSet = $dbConn->Execute($sql);
    foreach ($gradeSet as $can => $grad) {
        $sql = "insert into exam_grades (snummer,exam_event_id,grade,trans_id) values ({$can},{$exam_event},{$grad},{$trans_id});";
        $resultSet = $dbConn->Execute($sql);
    }
    $dbConn->transactionEnd();

    unSet($_SESSION['gradeSet']);
}
$rows = "";
if (count($gradeSet)) {

    $cands = implode(',', array_keys($gradeSet));
    $sql = "select snummer, achternaam,roepnaam,coalesce(', '||tussenvoegsel,'') as tussenvoegsel "
            . "from student_email where snummer in ({$cands}) order by achternaam,roepnaam";
    $resultSet = $dbConn->Execute($sql);

    if ($resultSet === FALSE) {
        die("error " . $dbConn->ErrorMsg() . "\n");
    }

    for (; !$resultSet->EOF; $resultSet->moveNext()) {
        extract($resultSet->fields);
        $rows .="<tr><td><input type='hidden' name='cand[]' value='$snummer'/>$snummer</td><td>{$achternaam}{$tussenvoegsel}</td>"
                . "<td>$roepnaam</td><td><input style='text-align:right' size='3' type='number' name='grade[]' min='0' max='10.0' step='0.1' value='{$gradeSet[$snummer]}'/></td></tr>\n";
    }
    if ($rows != "") {
        $rows = "<table border='1' style='border-collapse:collapse;'>\n"
                . "<tr><th>snummer</th><th>lastname</th><th>name</th><th>grade</th></tr>"
                . $rows . "\n</table>\n";
    }
}
$event_query="select  ee.exam_event_id as value, m.progress_code||'.'||mp.progress_code||'-'||ee.exam_date as name, m.progress_code as grpname from exam_event ee join module_part mp using (module_part_id) join module m using(module_id) order by exam_date desc, mp.progress_code";
$event_selector= new Selector($dbConn, 'exam_event',$event_query , $exam_event);
$pp = array();
$pp['rows'] = $rows;
$pp['showInput'] = $showInput;
$pp['exam_event'] = $exam_event;
$pp['event_code'] = $event_code;
$pp['event_selector'] = $event_selector->getSelector();
$page_opening = "Upload grades on DB $db_name ";
$nav = new Navigation(array(), basename($PHP_SELF), $page_opening);

$page = new PageContainer();
$page->addBodyComponent($nav);
$page->addHtmlFragment('templates/uploadGrades_template.php', $pp)->show();

