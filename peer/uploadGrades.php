<?php

require_once 'component.php';
require_once 'navigation2.inc';
require_once 'GradeHarvester.php';
/**
 * Upload grades to peerweb.
 */
$gradeSet = array();
$showInput = true;
$event = '';
if (isSet($_POST['event'])) {
    $_SESSION['event'] = $event = pg_escape_string($_POST['event']);
}

if (isSet($_POST['grades']) && isSet($_POST['event'])) {
    $_SESSION['gradeSet'] = $gradeSet = harvest($_POST['grades']);
    $showInput = (count($gradeSet) === 0);
} else if (isSet($_POST['grade']) && isSet($_POST['cand']) && isSet($_POST['commit'])) {
    $gc = count($_POST['grade']);
    $gradeSet = array();
    for ($c = 0; $c < $gc; $c++) {
        $gradeSet[$_POST['cand'][$c]] = $_POST['grade'][$c];
    }
    $cands = implode(',', array_keys($gradeSet));
    $trans_id = $dbConn->transactionStart("enter grades for  {$event}");
    $sql = "delete from exam_grades where event = '{$event}' and snummer "
            . "in ($cands) and trans_id in (select trans_id from transaction where operator=$peer_id);\n";
    $resultSet = $dbConn->Execute($sql);
    foreach ($gradeSet as $can => $grad) {
        $sql = "insert into exam_grades (snummer,event,grade,trans_id) values ({$can},'{$event}',{$grad},{$trans_id});\n";
        $resultSet = $dbConn->Execute($sql);
    }
    //echo "<pre>{$sql}</pre>\n";
    $resultSet = $dbConn->Execute($sql);
    $dbConn->transactionEnd();

    unSet($_SESSION['gradeSet']);
}
$rows = "";
if (count($gradeSet)) {

    $cands = implode(',', array_keys($gradeSet));
    $sql = "select snummer, achternaam,roepnaam,coalesce(', '||voorvoegsel,'') as voorvoegsel "
            . "from student where snummer in ({$cands}) order by achternaam,roepnaam";
    $resultSet = $dbConn->Execute($sql);

    if ($resultSet === FALSE) {
        die("error " . $dbConn->ErrorMsg() . "\n");
    }

    for (; !$resultSet->EOF; $resultSet->moveNext()) {
        extract($resultSet->fields);
        $rows .="<tr><td><input type='hidden' name='cand[]' value='$snummer'/>$snummer</td><td>{$achternaam}{$voorvoegsel}</td>"
                . "<td>$roepnaam</td><td><input style='text-align:right' size='3' type='number' name='grade[]' min='0' max='10.0' step='0.1' value='{$gradeSet[$snummer]}'/></td></tr>\n";
    }
    if ($rows != "") {
        $rows = "<table border='1' style='border-collapse:collapse;'>\n"
                . "<tr><th>snummer</th><th>lastname</th><th>name</th><th>grade</th></tr>"
                . $rows . "\n</table>\n";
    }
}
$pp = array();
$pp['rows'] = $rows;
$pp['showInput'] = $showInput;
$pp['event'] = $event;
$page_opening = "Upload grades on DB $db_name ";
$nav = new Navigation(array(), basename($PHP_SELF), $page_opening);

$page = new PageContainer();
$page->addBodyComponent($nav);
$page->addHtmlFragment('templates/uploadGrades.php', $pp)->show();

