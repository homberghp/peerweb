<?php
   function projectsFor($dbConn,$snummer){
?>
<table border='3' style='empty-cells:show;border-collapse:3d' rules='groups' frame='box' width='100%' summary='My projects'>
<thead>
<col align='left'/>
<col align='right'style='background:rgba(80,80,80,0.3)' />
<col align='right'/>
<col style='background:rgba(80,80,80,0.3)' align='center'/>
<col align='center'/>
<?php
   $sql = "select achternaam,voorvoegsel,roepnaam from student where snummer=$snummer";
   $resultSet= $dbConn->Execute($sql);
    extract($resultSet->fields);
?>
<tr><th colspan='5'>Projects for <?=$roepnaam?> <?=$voorvoegsel?> <?=$achternaam?> 
      (<?=$snummer?>)</th></tr>
<tr><th>Project</th><th >M</th><th>G</th><th>A</th><th>D</th></tr>
</thead>
<?php
$sql = "select afko,description,milestone,\n"
    ." case when milestone_name='Milestone' then 'M'||milestone else milestone_name end as milestone_name,\n"
    ." year,grp_num,has_assessment,has_doc "
    ."from student_project_attributes where snummer=$snummer".
    "order by year desc,afko,milestone";
$resultSet= $dbConn->Execute($sql);
if ($resultSet=== false) {
    echo('tt Error: '.$dbConn->ErrorMsg().' with '.$sql);
 } else while (!$resultSet->EOF) {
    extract($resultSet->fields);
    $has_doc=isSet($has_doc)?"<img src='".IMAGEROOT."/checkmark.png' alt='v'/>":'';
    $has_assessment=isSet($has_assessment)?"<img src='".IMAGEROOT."/checkmark.png' alt='v'/>":'';
    echo "<tr><td title='$description'>$afko-$year</td><td>{$milestone_name}</td>".
	"<td>$grp_num</td><td>$has_assessment</td><td>$has_doc</td></tr>\n";
    $resultSet->moveNext();
 }
?>
<tr><td colspan='5'><fieldset style='text-align:left;'><legend>Legend</legend>
<ul>
<li><b>M</b>ilestone</li>
<li>your <b>G</b>roup</li>
<li>has <b>A</b>ssessment</li>
<li>can upload <b>D</b>ocuments</li>
</ul>
</fieldset></td></tr>
</table>
<?php
 }
?>