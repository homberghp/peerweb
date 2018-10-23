<?php

include_once 'peerutils.php';
require_once 'validators.php';
require_once 'component.php';

if (isSet($_REQUEST['prjm_id'])) {
    $debug=false;
    $prjm_id = validate($_REQUEST['prjm_id'], 'integer', 1);
    $texdir = $site_home . '/tex/out';
    $scriptdir = $site_home . '/scripts';
    $pdfname = 'phototicketforproject.pdf';
    $result = `($scriptdir/mkticketsforproject.sh -p $prjm_id -w $texdir )`;
    $fp = @fopen($texdir . '/' . $pdfname, 'r');
    //   $fp = @fopen($texdir.'/'.$filename, 'r');
    if ($fp != false) {
        if ($debug) {
            echo "$name<br/>$mimetype </br>$filename<br/>$sql\n";
        } else {
            // send the right headers
            header("Content-type: application/pdf");
            header("Pragma: public");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Length: " . filesize($texdir . '/' . $pdfname));
            header("Content-Disposition: attachment; filename=\"$pdfname\"");

            // dump the pdf file
            fpassthru($fp);
        }
        fclose($fp);
        exit;
    } else {
        echo "no phototickets prjm_id $prjm_id\n";
    }
} else {
        echo "no phototickets prjm_id $prjm_id\n";
}
?>