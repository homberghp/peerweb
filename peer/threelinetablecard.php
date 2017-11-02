<?php
include_once 'peerutils.php';

//include_once 'threelinetablecard';
/**
 * sql should produced three columns named line1, line2, line3 and barcode
 * @param dbconn database connection
 * @param basename of produced file without extension
 * @param sql statement
 */
function barcodedCard($dbConn, $basename, $sql){
  global $site_home;
  $debug=false;
  $texdir = $site_home.'/tex/tablecard_out';
  $filename = $basename.'.tex';
  $pdfname  =  $basename.'.pdf';
  $fp  =  fopen($texdir.'/'.$filename, "w");
  fwrite($fp,"\n\\input{../tablecarddef}%\n\\begin{document}\n");
  $resultSet= $dbConn->Execute($sql);
  if ($resultSet === false) {
    fwrite($fp, "cannot create table cards with $sql, reason".$dbConn->ErrorMsg());
  } else
    while (!$resultSet->EOF) {
      extract($resultSet->fields);
      if(strlen($line1) <= 10) { 
	$padding = '|';
	$padlength= (10-strlen($line1))/2;
	for ($i = 1; $i< $padlength; $i++) {
	  $padding .='|';
	}
	$line1 = '{\\color{white}'.$padding.'}'.$line1.'{\\color{white}'.$padding.'}'; 
      }
      
      $latexline="\\tablecard{".$line1."}{".$line2."}{".$line3."}{".$barcode."}\n";
      fwrite($fp,$latexline);
      $resultSet->MoveNext();
    }
  fwrite($fp,"\n\\end{document}");
  fclose($fp);
  $result = @`(cd $texdir; /usr/bin/pdflatex -interaction=batchmode $filename)`;
  $fp = @fopen($texdir.'/'.$pdfname, 'r');
  //   $fp = @fopen($texdir.'/'.$filename, 'r');
  if ($fp != false) {
    if ($debug ) {
      echo "$name<br/>$mimetype </br>$filename<br/>$sql\n";
    } else {
      // send the right headers
      header("Content-type: application/pdf");
      header("Pragma: public");
      header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
      header("Content-Length: " . filesize($texdir.'/'.$pdfname));
      header("Content-Disposition: attachment; filename=\"$pdfname\"");
	    
      // dump the pdf file
      fpassthru($fp);
    }
    fclose($fp);
    exit;
  }
}
?>