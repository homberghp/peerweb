<?php

include_once('./peerlib/peerutils.php');
require_once('./peerlib/validators.php');
require_once('component.php');
$debug = false;
$prjtg_id = 1;
extract( $_SESSION );
if ( isSet( $_REQUEST['prjtg_id_id'] ) ) {
    $class_id = validate( $_REQUEST['prjtg_id'], 'integer', $prjtg_id );
}

$sql = "select substr(now()::text,1,16) as ts,rtrim(afko)||'-'||year as project,tutor, coalesce(grp_name,'g'||grp_num) as grp_name\n"
        . "from all_prj_tutor where prjtg_id=$prjtg_id";
$resultSet = $dbConn->Execute( $sql );
extract( $resultSet->fields );
$texdir = $site_home . '/tex/out';
$basename = sanitizeFilename( 'groupphotolist_' . trim( preg_replace( '/\s+/',
                        '_', $project . '_' . $grp_name ) ) );
$filename = $basename . '.tex';
$pdfname = $basename . '.pdf';
$fp = fopen( $texdir . '/' . $filename, "w" );
fwrite( $fp,
        "\\documentclass[10pt]{article}\n"
        . "\\usepackage[utf8]{inputenc}\n"
        . "%\\usepackage[a4paper,bindingoffset=-15mm,left=2cm,right=2cm,top=15mm,botton=5mm]{geometry}\n"
        . "\\usepackage[a4paper,scale={.9,.9}]{geometry}\n"
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
        . "%\\renewcommand\\arraystretch{1.2}\n"
        . "\\def\\mystrut(#1,#2){\\vrule height #1 depth #2 width 0pt}\n"
        . "\\newcolumntype{C}[1]{%"
        . "   >{\\mystrut(30mm,20mm)\\centering}%\n"
        . "   p{#1}%\n"
        . "   <{}}\n  "
        . "\\newcommand\\tablehead[1]{\n"
        . "%\\begin{longtable}{|p{60mm}|p{60mm}|p{60mm}|}%\n"
        . "\\begin{longtable}{ccc}%\n"
        . " \\endhead%\n"
        . " \\endfoot%\n"
        . " \\endlastfoot%\n"
        . "}\n"
        . " \n"
        . "\\chead[ \\Huge\\bfseries{} $grp_name ]{\\Huge  \\bfseries{} $grp_name }\n"
        . "\\rhead[produced on " . $ts . "]{produced on " . $ts . "}\n"
        . "\\lhead[Project $project]{Project $project}\n"
        . "\\lfoot[Fontys Venlo \\textbf{peerweb}]{Fontys Venlo \\textbf{peerweb}}\n"
        . "\\rfoot[\\tiny\\url{https://www.fontysvenlo.org/peerweb/groupphoto.php?prjtg_id={$prjtg_id}}]%\n"
        . "{\\tiny\\url{https://www.fontysvenlo.org/peerweb/groupphoto.php?prjtg_id={$prjtg_id}}}\n"
        . "\\begin{document}\\centering\n"
        . "\\pagestyle{fancy}\n"
        . "\\setlength{\\parindent}{0pt}\n"
        . "\\setlength{\\parskip}{0pt}\n " );

$sql = "select snummer,roepnaam||coalesce(' '||voorvoegsel||' ',' ')||achternaam as name,\n"
        . " photo, coalesce(tutor,'---') as slb\n"
        . " from student s join prj_grp pg using(snummer) natural join portrait p"
        . " left join tutor t on(s.slb=t.userid) \n"
        . " where pg.prjtg_id=$prjtg_id\n"
        . " order by achternaam,roepnaam\n";
$resultSet = $dbConn->Execute( $sql );
if ( $debug ) {
    echo $sql;
}
if ( !$resultSet->EOF ) {
    fwrite( $fp, "\\tablehead{" . $grp_name . "}\n" );
}
$fotodir = '../../peer/';
$colcount = 0;
$cont = '';
while ( !$resultSet->EOF ) {
    extract( $resultSet->fields );
    fwrite( $fp,
            $cont
            . "\n"
            . "\\begin{minipage}{60mm}"
            . "\\center\\includegraphics[width=45mm]{"
            . $fotodir . $photo . "}"
            . "\n\\vfill\\sf{}\large\\textbf{{$name}}\\\\"
    //. "$snummer ($slb)\\\\"
            . "\\vspace{13mm}"
            . "\\end{minipage}\n" );
    $cont = ' & ';
    $resultSet->movenext();
    $colcount++;
    if ( $colcount === 3 && !$resultSet->EOF ) {
        fwrite( $fp, "\\\\\n" );
        $cont = '';
        $colcount = 0;
    }
}
for (; $colcount < 5; $colcount++ ) {
    fwrite( $fp, " & " );
}
fwrite( $fp, "\\\\\n\\end{longtable}\n\\end{document}\n" );
fclose( $fp );
$result = @`(cd $texdir; /usr/bin/pdflatex -interaction=batchmode $filename;/usr/bin/pdflatex -interaction=batchmode $filename)`;

if ( !isSet( $debug ) || !$debug ) {
    $filename = $pdfname;
}
$fp = @fopen( $texdir . '/' . $filename, 'r' );
if ( $fp != false ) {
    if ( $debug ) {
        header( "Content-type: application/text" );
        header( "Pragma: public" );
        header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
        header( "Content-Length: " . filesize( $texdir . '/' . $filename ) );
        header( "Content-Disposition: attachment; filename=\"$filename\"" );
    } else {
        // send the right headers
        header( "Content-type: application/pdf" );
        header( "Pragma: public" );
        header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
        header( "Content-Length: " . filesize( $texdir . '/' . $filename ) );
        header( "Content-Disposition: attachment; filename=\"$filename\"" );
    }
    // dump the picture and stop the script
    fpassthru( $fp );

    fclose( $fp );
    // $result = @`(cd $texdir; rm $basename.*)`;
}
?>