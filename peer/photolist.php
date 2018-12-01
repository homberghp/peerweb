<?php

requireCap(CAP_TUTOR);
require_once('validators.php');
require_once('component.php');
$debug = false;
$class_id = 1;
extract($_SESSION);
if (isSet($_REQUEST['class_id'])) {
    $class_id = validate($_REQUEST['class_id'], 'integer', $class_id);
}

$sql = "select substr(now()::text,1,16) as ts,rtrim(sclass) as sclass,"
        . "rtrim(faculty_short) as faculty_short from student_class join faculty using(faculty_id)\n"
        . "where class_id=$class_id";
$resultSet = $dbConn->Execute($sql);
extract($resultSet->fields);
$classname = "$faculty_short.$sclass";
$texdir = $site_home . '/tex/out';
$basename = sanitizeFilename('photolist_' . trim(preg_replace('/\s+/', '_', $classname)));
$filename = $basename . '.tex';
$pdfname = $basename . '.pdf';
$fp = fopen($texdir . '/' . $filename, "w");
fwrite($fp, "\\documentclass[10pt]{article}\n"
        . "\\usepackage[utf8]{inputenc}\n"
        . "\\usepackage[a4paper,scale={0.9,0.88}]{geometry}\n"
        . "\\usepackage{array}\n"
        . "\\usepackage{longtable}\n"
        . "\\usepackage{times}\n"
        . "\\usepackage{color}\n"
        . "\\usepackage{graphicx}\n"
        . "\\usepackage{colortbl}\n"
        . "\\usepackage{fancyhdr}\n"
        . "\\usepackage[pdftex,colorlinks=true,
                      pdfstartview=FitV,
                      linkcolor=blue,
                      citecolor=blue,
                      urlcolor=blue,]{hyperref}\n"
        . "\\setlength\\voffset{.2in}\n"
        . "\\renewcommand\\footrulewidth{1pt}\n"
        . "\\newcommand\\tabletail{\\end{longtable}}\n"
        . "\\renewcommand\\arraystretch{1.2}\n"
        . "\\def\\mystrut(#1,#2){\\vrule height #1 depth #2 width 0pt}\n"
        . "\\newcolumntype{C}[1]{%"
        . "   >{\\mystrut(30mm,20mm)\\centering}%\n"
        . "   p{#1}%\n"
        . "   <{}}\n  "
        . "\\newcommand\\tablehead[1]{\n"
        . "\\begin{longtable}{C{35mm}C{35mm}C{35mm}C{35mm}C{35mm}}%\n"
        . " \\endhead%\n"
        . " \\endfoot%\n"
        . " \\endlastfoot%\n"
        . "}\n"
        . " \n"
        . "\\chead[" . $classname . "]{" . $classname . "}\n"
        . "\\rhead[" . $ts . "]{" . $ts . "}\n"
        . "\\lhead[Student Class]{student class}\n"
        . "\\lfoot[Fontys Venlo peerweb]{Fontys Venlo peerweb}\n"
        . "\\rfoot[\\url{https://peerweb.fontysvenlo.org}]{\\url{https://www.fontysvenlo.org/peerweb}}\n"
        . "\\begin{document}\n"
        . "\\pagestyle{fancy}\n"
        . "\\setlength{\\parindent}{0pt}\n"
        . "\\setlength{\\parskip}{0pt}\n ");

$sql = "select snummer,roepnaam||coalesce(' '||tussenvoegsel||' ',' ')||achternaam as name,\n"
        . " photo, coalesce(tutor,'---') as slb\n"
        . " from student "
        . " natural join portrait "
        . " left join tutor on(slb=userid) \n"
        . " where class_id=$class_id\n"
        . " order by achternaam,roepnaam\n";
$resultSet = $dbConn->Execute($sql);
if ($debug) {
    echo $sql;
}
if (!$resultSet->EOF) {
    fwrite($fp, "\\tablehead{" . $classname . "}\n");
}
$fotodir = '../../peer/';
$colcount = 0;
$cont = '';
$ps1 = '';
$ps2 = '';
while (!$resultSet->EOF) {
    extract($resultSet->fields);
    $ps1 .= $cont
            . "\n"
            . "\\begin{minipage}{35mm}"
            . "\\center\\includegraphics[height=40mm]{"
            . $fotodir . $photo . "}"
            . "\n\\vfill\\sf{}\\textbf{" . $name
            . "}\\\\$snummer ($slb)}"
            . "\\end{minipage}\n";
    //);
    $cont = ' & ';
    $resultSet->movenext();
    $colcount++;
    if ($colcount == 5 && !$resultSet->EOF) {
        fwrite($fp, $ps1 . "\\\\\n");
        $cont = '';
        $colcount = 0;
        $ps1 = '';
        $ps2 = '';
    }
}
if ($ps1 != '') {
    fwrite($fp, $ps1);
}
for (; $colcount < 5; $colcount++) {
    fwrite($fp, " & ");
}
fwrite($fp, "\\\\\n\\end{longtable}\n\\end{document}\n");
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
    // $result = @`(cd $texdir; rm $basename.*)`;
}
?>