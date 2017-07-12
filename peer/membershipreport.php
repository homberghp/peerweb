<?php
include_once('./peerlib/peerutils.php');
require_once('./peerlib/validators.php');
require_once('component.php');
$snummer=$peer_id;
extract($_SESSION);
$judge=$snummer;
$sql="select roepnaam, tussenvoegsel,achternaam,snummer from student where snummer=$judge";
$resultSet=$dbConn->Execute($sql);
extract($resultSet->fields);
$texdir = $site_home.'/tex/out';
$basename='membershipreport_'.$snummer;
$filename = $basename.'.tex';
$pdfname  =  $basename.'.pdf';
$ffilename= $texdir.'/'.$filename;
$fp  =  fopen($ffilename, "w");
if ($fp !== false) {
fwrite($fp,"\\documentclass{article}\n".
       "\\usepackage[utf8]{inputenc}\n".
       "\\usepackage{times}\n".
       "\\usepackage[a4paper,scale={0.8,0.8}]{geometry}\n".
       "\\usepackage{longtable}\n".
       "\\title{Membership report for $roepnaam $tussenvoegsel $achternaam}\n".
       "\\begin{document}\n".
       "\\maketitle".
       "\\begin{longtable}{lllrrll}\n".
       "  \\caption{Group memberships of $roepnaam $tussenvoegsel $achternaam\\\\\n".
       "    student number $snummer}\\\\\n".
       "  \\textbf{year}& \\textbf{afko} & \\textbf{description} & \\textbf{milestone} & \\textbf{group number}".
       "  &$group alias$&\\textbf{Group description}\\\\\\hline\n".
       "  \\endfirsthead\n".
       "  \\textbf{year}& \\textbf{afko} & \\textbf{description} & \\textbf{milestone} & \\textbf{group number}".
       "  &\\textbf{group alias}&\\textbf{Group description}\\\\\\hline\n".
       "  \\endhead\n".
       "  \\hline \\multicolumn{7}{r}{\\emph{Continued on next page}}\n".
       "  \\endfoot\n".
       "  \\hline \\multicolumn{7}{c}{\\textbf{End of report}}\n".
       "  \\endlastfoot\n");
$sql="select year,afko,description,milestone,grp_num,regexp_replace(coalesce(alias,'G'||grp_num),'_','\\\\_','g') as group_alias,\n".
  " long_name as group_description".
  " from prj_grp join all_prj_tutor using(prjtg_id)\n".
  " where snummer=$snummer order by year,afko,milestone";
$resultSet=$dbConn->Execute($sql);
while(!$resultSet->EOF){
    extract($resultSet->fields);
    fwrite($fp,"$year& $afko& $descrition& $milestone& $grp_num & $group_alias& $group_description\\\\\hline\n");
    $resultSet->movenext();
 }
fwrite($fp,"\\end{longtable}\n\\end{document}\n");
fclose($fp);
$result = @`(cd $texdir; /usr/bin/pdflatex -interaction=batchmode $filename;/usr/bin/pdflatex -interaction=batchmode $filename)`;

$fp = @fopen($texdir.'/'.$pdfname, 'r');
if ($fp != false) {
    // send the right headers
    header("Content-type: application/pdf");
    header("Pragma: public");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Length: " . filesize($texdir.'/'.$pdfname));
    header("Content-Disposition: attachment; filename=\"$pdfname\"");
    
    // dump the picture and stop the script
    fpassthru($fp);
    
    fclose($fp);
    $result = @`(cd $texdir; rm $basename.*)`;
 }
} else {
  echo "cannot write to file $ffilename<br/>";
}
?>