<?php

requireCap(CAP_TUTOR);
require_once('validators.php');
require_once('component.php');
$debug = false;
$act_id = 11;
extract($_SESSION);
if (isSet($_REQUEST['act_id'])) {
    $act_id = validate($_REQUEST['act_id'], 'integer', $act_id);
}

$sql = "select act_id,rtrim(afko) as afko,short,datum,act_type_descr,to_char(start_time,'HH24:MI') as time,"
        . "part from activity natural join activity_type join all_prj_tutor using(prjm_id) where act_id=$act_id";
$resultSet = $dbConn->Execute($sql);
extract($resultSet->fields);
$texdir = $site_home . '/tex';
$basename = sanitizeFilename('attendancelist_' . trim(preg_replace('/\s+/', '_', $afko.'_'.$short)) . '_' . $datum . '_' . $time);
$filename = $basename . '.tex';
$pdfname = $basename . '.pdf';
$fp = fopen($texdir . '/' . $filename, "w");
fwrite($fp, "\\documentclass[11pt]{article}\n" .
        "\\usepackage[utf8]{inputenc}\n" .
        "\\usepackage[a4paper,scale={0.8,0.85}]{geometry}\n" .
        "\\usepackage{longtable}\n" .
        "\\usepackage{times}\n" .
        "\\usepackage{color}\n" .
        "\\usepackage{colortbl}\n" .
        "\\usepackage{fancyhdr}\n" .
        "\\setlength\\voffset{.25in}\n" .
        "\\newcommand\\tabletail{\\end{longtable}}\n" .
        "\\renewcommand\\arraystretch{3}\n" .
        "\\newcommand\\tablehead[1]{\n" .
        "\\begin{longtable}{|l|p{35mm}|p{85mm}|}%\n" .
        "  \\caption{Presence list for class/group #1}\\\\\\hline%\n" .
        "  \\rowcolor[gray]{0.8}\\textbf{class}&\\textbf{name}&\\textbf{signature/reason~for~absence}\\\\\\hline%\n" .
        " \\endhead%\n" .
        "  \\hline \\multicolumn{3}{r}{\\emph{List for class #1 continued on next page}} \n" .
        " \\endfoot%\n" .
        "  \\hline \\multicolumn{3}{c}{\\textbf{End of report}}%\n" .
        " \\endlastfoot%\n" .
        "}\n" .
        " \n" .
        "\\chead[Presence list for $afko $short on $datum, $time]"
        . "{Presence list for $afko $short on $datum, $time}\n"
        . "\\rfoot[Presence list for activity $act_id.]"
        . "{Presence list for activity $act_id}\n"
        . ""
        . "\\begin{document}\n"
        . "\\pagestyle{fancy}\n");

$sql = "select coalesce(grp_name,'g'||grp_num) as sgroup,tutor,apt.grp_num,\n" .
        " achternaam||', '||roepnaam||coalesce(' '||tussenvoegsel,'') as name, reason,".
        " case when reason notnull then 'excused' else null end as check" .
        " from prj_grp pg join all_prj_tutor apt using(prjtg_id)\n" .
        " join activity a  using(prjm_id) \n" .
        " join student st using(snummer)  \n" .
        " left join absence_reason using(act_id,snummer)\n".
        " where act_id=$act_id\n" .
        " order by grp_num,achternaam,roepnaam\n";
$resultSet = $dbConn->Execute($sql);
if ($debug) {
    echo $sql;
}
if (!$resultSet->EOF) {
    $oldSgroup = $resultSet->fields['sgroup'];
    $tutor = $resultSet->fields['tutor'];
    fwrite($fp, "\\tablehead{" . $oldSgroup . '/' . $tutor . "}\n");
}
while (!$resultSet->EOF) {
    extract($resultSet->fields);
    if ($sgroup != $oldSgroup) {
        fwrite($fp, "\\tabletail\n\\tablehead{" . $sgroup . '/' . $tutor . "}\n");
    }
    fwrite($fp, "$sgroup& $name&  $reason\\\\\\hline\n");
    $oldSgroup = $sgroup;
    $resultSet->movenext();
}
fwrite($fp, "\\end{longtable}\n\\end{document}\n");
fclose($fp);
$result = @`(cd $texdir; /usr/bin/pdflatex -interaction=batchmode $filename;/usr/bin/pdflatex -interaction=batchmode $filename)`;

if (!isSet($debug) || !$debug) {
    $filename = $pdfname;
}
$fp = @fopen($texdir . '/' . $filename, 'r');
if ($fp != false) {
    if ($debug) {
        header("Content-type: application/text");
        header("Pragma: public");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Length: " . filesize($texdir . '/' . $filename));
        header("Content-Disposition: attachment; filename=\"$filename\"");
    } else {
        // send the right headers
        header("Content-type: application/pdf");
        header("Pragma: public");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Length: " . filesize($texdir . '/' . $filename));
        header("Content-Disposition: attachment; filename=\"$filename\"");
    }
    // dump the picture and stop the script
    fpassthru($fp);

    fclose($fp);
    $result = @`(cd $texdir; rm $basename.*)`;
}
?>