<?php
   function documentsFor($dbConn,$snummer){
  global $isTutor;
  global $as_student;
  global $peer_id;
  global $tutor_code;
?>
<table border='3' style='empty-cells:show;border-collapse:3D;text-align:left' rules='groups' frame='box' width='100%' summary='Documents you can access'>
<thead>
<tr><th colspan='3'>New documents not read by <?=$peer_id?></th></tr>
<tr><th colspan='3' align='left'>Title</th></tr>
</thead>
<?php
if ( !$isTutor || $as_student ) {
    $sql = "select author,afko,title,".
        "doc_id,doctype,to_char(uploadts,'YYYY-MM-DD HH24:MM') as uploaded,\n".
	"rtrim(roepnaam) as roepnaam,\n".
	"rtrim(tussenvoegsel) as tussenvoegsel,rtrim(achternaam) as achternaam from\n".
	"viewabledocument join student st on (author=st.snummer) join project using(prj_id)\n".
	"where viewer=$snummer\n".
	"and uploadts < (now()-'3 months'::interval)\n".
	"and doc_id not in (select upload_id from downloaded where snummer=$snummer) order by doc_id desc";
    
} else {
    $sql = "select snummer as author,afko,title," .
                "upload_id as doc_id,doctype,\n" .
                "to_char(uploadts,'YYYY-MM-DD HH24:MM') as uploaded,\n" .
                "rtrim(roepnaam) as roepnaam,\n" .
                "rtrim(tussenvoegsel) as tussenvoegsel,rtrim(achternaam) as achternaam from\n" .
                "uploads join prj_grp using (prjtg_id,snummer) join student using(snummer)\n" .
                "join prj_tutor pt using(prjtg_id)\n" .
                "join prj_milestone pm on(pt.prjm_id=pm.prjm_id)\n " .
                " join project using(prj_id) where owner_id='$peer_id'\n" .
                "and uploadts >  (now()-'3 months'::interval)\n" .
                "and upload_id not in (select upload_id from downloaded where snummer=$peer_id)\n" .
                " order by doc_id desc";
    }
    $resultSet= $dbConn->Execute($sql);
if ($resultSet=== false) {
    echo('tt Error: '.$dbConn->ErrorMsg().' with '.$sql);
 } else while (!$resultSet->EOF) {
    extract($resultSet->fields);
    $title=trim($title);
    echo "<tr>".
	"\t<td>&nbsp;</td>\n\t<td><a href='upload_critique.php?doc_id=".$doc_id."' target='_blank'>$doc_id</a></td>\n".
	"\t<td title='By $roepnaam $tussenvoegsel $achternaam ($author) for $afko uploaded: $uploaded'>".
	"<a href='upload_critique.php?doc_id=".$doc_id."' target='_blank'>$title</a></td>".
	"</tr>\n";
    $resultSet->moveNext();
 }
    ?>
</table>
<?php
 }
?>

