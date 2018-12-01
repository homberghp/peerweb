<?php
requireCap(CAP_SYSTEM);

$debug=0;
include_once('peerutils.php');
require_once('validators.php');
require_once('component.php');
require_once 'TemplateWith.php';
ini_set('error_reporting',(E_ALL & ~E_NOTICE) );
extract($_SESSION);
$sql="select roepnaam||' '||coalesce(tussenvoegsel||' ','')||' '||achternaam as line1,snummer \n".
 "from student where snummer=$peer_id";
$resultSet=$dbConn->Execute($sql);
extract($resultSet->fields);
$line2='Your Function';
$line3='Your company';
$line1width = 200;
$line2width = 200;
$line2width = 200;
$line1height = 30;
$line2height = 30;
$line2height = 30;
if (isSet($_POST['line1'])) {
    $line1 = $_POST['line1'];
    $line2 = $_POST['line2'];
    $line3 = $_POST['line3'];
    $line1width = validate($_POST['line1width'],'integer',200);
    $line2width = validate($_POST['line2width'],'integer',200);
    $line2width = validate($_POST['line2width'],'integer',200);
    $line1height = validate($_POST['line1height'],'integer',30);;
    $line2height = validate($_POST['line2height'],'integer',30);;
    $line2height = validate($_POST['line2height'],'integer',30);;
    $latexline="\\tablecard{".$line1."}{".$line2."}{".$line3."}{".$snummer."}\n";
    $texdir = $site_home.'/tex/tablecard_out';
    $basename='tablecard';
    $filename = $basename.'.tex';
    $pdfname  =  $basename.'.pdf';
    $fp  =  fopen($texdir.'/'.$filename, "w");
    fwrite($fp,"\\input{../tablecarddef}%\n\\begin{document}\n");
    fwrite($fp,$latexline);
    fwrite($fp,"\n\\end{document}");
    fclose($fp);
    $result = @`(cd $texdir; /usr/bin/pdflatex -interaction=batchmode $filename)`;
    $fp = @fopen($texdir.'/'.$pdfname, 'r');
    if ($fp != false) {
	if ($debug == 1 ) {
	    echo "$name<br/>$mimetype </br>$filename<br/>\n";
	} else {
	    // send the right headers
	    header("Content-type: application/pdf");
	    header("Pragma: public");
	    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	    header("Content-Length: " . filesize($texdir.'/'.$pdfname));
	    header("Content-Disposition: attachment; filename=\"$pdfname\"");
	    
	    // dump the picture and stop the script
	    fpassthru($fp);
	}
	fclose($fp);
	exit;
    }
 }



$page_opening='Create your own table card';
$page = new PageContainer();
$page->setTitle('Create your own Table Card');
$form1 = new HtmlContainer("<div id='main'>");
$templatefile='tablecard.html';
$template_text= file_get_contents($templatefile, true);
if ($template_text === false ) {
  $form1->addText("<strong>cannot read template file $templatefile</strong>");
} else {  
  $form1->addText(templateWith($template_text, get_defined_vars()));
}
$page->addBodyComponent($form1);
$page->show();
?>