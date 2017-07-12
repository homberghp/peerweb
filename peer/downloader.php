<?php

/* $Id: downloader.php 1723 2014-01-03 08:34:59Z hom $ */
//session_start();
include_once('./peerlib/peerutils.php');
require_once('./peerlib/validators.php');
require_once('document_access.php');
require_once('tutorhelper.php');
$doc_id = 1;
$debug = 0;
extract($_SESSION);
$fname = '';
if (isset($_REQUEST['doc_id'])) {
    $doc_id = validate($_REQUEST['doc_id'], 'doc_id', $doc_id);
    if (authorized_document($snummer, $doc_id)) {
        $sql = "select rel_file_path,trim( both ' ' from mime_type_long) as mime_type from uploads where upload_id=$doc_id";
        $resultSet = $dbConn->Execute($sql);
        if ($resultSet === false) {
            die('cannot get project data:' . $dbConn->ErrorMsg() . ' with ' . $sql);
        }
        if (!$resultSet->EOF) {
            extract($resultSet->fields);
            $name = $upload_path_prefix . '/' . $rel_file_path;
            $filename = join('_', explode(' ', basename($name)));
            if ($debug == 1)
                echo "$name<br/>$mimetype </br>$filename<br/>\n";
            // open the file in a binary mode
            $fp = @fopen($name, 'r');
            $fname = $rel_file_path;
            if ($fp != false) {
                if ($debug == 1) {
                    echo "$name<br/>$mimetype </br>$filename<br/>\n";
                } else {
                    // send the right headers
                    header("Content-type: $mime_type");
                    header("Pragma: public");
                    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                    header("Content-Length: " . filesize($name));
                    if ($mime_type == 'application/pdf' || $mime_type == 'image/jpeg' || $mime_type == 'image/png' || $mime_type == 'image/svg+xml') {
                        header("Content-Disposition: inline; filename=\"$filename\"");
                    } else {
                        header("Content-Disposition: attachment; filename=\"$filename\"");
                    }

                    // dump the picture and stop the script
                    fpassthru($fp);
                }
                fclose($fp);
                // log download
                $dbConn->Execute("insert into downloaded (snummer,upload_id) values ($peer_id,$doc_id)");
                exit;
            }
        }
    }
}
$lang = 'en';
$thp = tutorhelperplaceholder($dbConn, $isTutor);
$q = "select roepnaam||coalesce(' '||tussenvoegsel||' ',' ')||achternaam as student_name from student where snummer=$peer_id";
$rs = $dbConn->Execute($q);
extract($rs->fields);
//$student_name='Piet Puk';
include 'templates/downloader_sorry.php';
?>
