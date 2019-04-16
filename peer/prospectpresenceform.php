<?php
requireCap(CAP_TUTOR);
require_once('validators.php');
require_once('component.php');
ini_set( 'error_reporting', E_ALL );
ini_set( 'display_errors', 'On' );

$debug = FALSE;
extract( $_SESSION );
$hoofdgrp = 'SEBINL2018';
if ( isSet( $_REQUEST[ 'hoofdgrp' ] ) ) {
    $hoofdgrp = trim($_REQUEST[ 'hoofdgrp' ]);
    $_SESSION['hoofdgrp']=$hoofdgrp;
}

$texdir = $site_home . '/tex';
$basename = sanitizeFilename( "prospectspresencelist_{$hoofdgrp}" );
$filename = $basename . '.tex';
$pdfname = $basename . '.pdf';
$fp = fopen( $texdir . '/' . $filename, "w" );
fwrite( $fp, "\\documentclass[11pt]{article}\n" .
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
        "\\begin{longtable}{|l|p{35mm}||p{25mm}|p{85mm}|}%\n" .
        "  \\caption{Presence list for class/group #1}\\\\\\hline%\n" .
        "  \\rowcolor[gray]{0.8}\\textbf{class}&\\textbf{name}&\\textbf{Student number}&\\textbf{signature}\\\\\\hline%\n" .
        " \\endhead%\n" .
        "  \\hline \\multicolumn{4}{r}{\\emph{List for class #1 continued on next page}} \n" .
        " \\endfoot%\n" .
        "  \\hline \\multicolumn{4}{c}{\\textbf{End of report}}%\n" .
        " \\endlastfoot%\n" .
        "}\n" .
        " \n" .
        "\\chead[Presence list for prospects {$hoofdgrp}]"
        . "{Presence list for prospects {$hoofdgrp}}\n"
        . "\\rfoot[Presence list for prospects {$hoofdgrp}]"
        . "{Presence list for prospects {$hoofdgrp}}\n"
        . ""
        . "\\begin{document}\n"
        . "\\pagestyle{fancy}\n" );

$sql = <<<'SQL'
        select hoofdgrp,snummer,
         achternaam||', '||roepnaam||coalesce(' '||tussenvoegsel,'') as name
        from prospects where hoofdgrp=$1
        order by achternaam,roepnaam
SQL;

if ( $debug ) {
    echo "{$hoofdgrp},<pre>$sql</pre>";
}
$resultSet = $dbConn->Prepare( $sql )->execute( [$hoofdgrp]  );
if ( !$resultSet->EOF ) {
    $oldSgroup = $resultSet->fields[ 'hoofdgrp' ];
    fwrite( $fp, "\\tablehead{" . $oldSgroup. "}\n" );
}
while ( !$resultSet->EOF ) {
    extract( $resultSet->fields );
    if ( $hoofdgrp != $oldSgroup ) {
        fwrite( $fp, "\\tabletail\n\\tablehead{" . $hoofdgrp. "}\n" );
    }
    fwrite( $fp, "{$hoofdgrp}& {$name}& {$snummer}& \\\\\\hline\n" );
    $oldSgroup = $hoofdgrp;
    $resultSet->movenext();
}
fwrite( $fp, "\\end{longtable}\n\\end{document}\n" );
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
    $result = @`(cd $texdir; rm $basename.*)`;
}
?>