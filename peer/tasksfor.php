<?php
function tasksFor($dbConn,$snummer) {
?>
<table border='3' style='empty-cells:show;border-collapse:3d;text-align:left;' rules='groups' frame='box' width='100%' summary='My project tasks'>
<thead>
<tr><th colspan='3'>My project tasks</th></tr>
<tr><th colspan='1'>Project</th><th style='background:white'>#</th><th>Task</th></tr>
</thead>
<?php
    $sql = "select distinct afko,description,task_id,task_description \n"
            . "from project \n"
            . " join prj_milestone using(prj_id)\n"
            . " join prj_tutor using(prjm_id) \n"
            . "join prj_grp using(prjtg_id)\n"
            . "join project_tasks using(snummer, prj_id) \n"
            . "where snummer=$snummer order by afko,task_id";
    $resultSet= $dbConn->Execute($sql);
if ($resultSet=== false) {
    echo('tt Error: '.$dbConn->ErrorMsg().' with '.$sql);
 } else while (!$resultSet->EOF) {
    extract($resultSet->fields);
    $has_doc=isSet($has_doc)?'D':'';
    $has_assessment=isSet($has_assessment)?'A':'';
    echo "<tr>".
	"<td title='$description'>$afko</td>".
	"<td style='background:white' align='right'>$task_id</td>".
	"<td>$task_description</td>".
	"</tr>\n";
    $resultSet->moveNext();
 }
?>
</table>
<?php
  }
?>