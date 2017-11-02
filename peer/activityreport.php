<?php
include_once('peerutils.php');
require_once('validators.php');
require_once('component.php');
$snummer=$peer_id;
extract($_SESSION);
$judge=$snummer;
$sql="select roepnaam, tussenvoegsel,achternaam,snummer from student where snummer=$judge";
$resultSet=$dbConn->Execute($sql);
extract($resultSet->fields);
$texdir = $site_home.'/tex';
$basename='participationreport_'.$snummer;
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
       "\\title{Participation report $roepnaam $tussenvoegsel $achternaam}\n".
       "\\begin{document}\n".
       "\\maketitle".
       "\\begin{longtable}{rccp{30mm}p{80mm}}\n".
       "  \\caption{Colloquium participation for $roepnaam $tussenvoegsel $achternaam\\\\ student number $snummer}\\\\\n".
       "  \\textbf{num}& \\textbf{date} & \\textbf{time} & \\textbf{name} & \\textbf{description}\\\\\\hline\n".
       "  \\endfirsthead\n".
       "  \\textbf{num}& \\textbf{date} & \\textbf{time} & \\textbf{name} & \\textbf{description}\\\\\\hline\n".
       "  \\endhead\n".
       "  \\hline \\multicolumn{5}{r}{\\emph{Continued on next page}}\n".
       "  \\endfoot\n".
       "  \\hline \\multicolumn{5}{c}{\\textbf{End of report}}\n".
       "  \\endlastfoot\n");
$sql="select act_id,datum,start_time,short,description from activity join activity_participant using(act_id) \n".
    " where snummer=$snummer order by datum,start_time";
$resultSet=$dbConn->Execute($sql);
while(!$resultSet->EOF){
    extract($resultSet->fields);
    fwrite($fp,"$act_id& $datum& $start_time& $short& $description\\\\\hline");
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