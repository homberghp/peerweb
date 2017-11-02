<?php

require_once 'peerutils.php';
require_once 'queryToXlsx.php';
require_once 'SpreadSheetWriter.php';
require_once 'pgrowparser.php';
$_REQUEST['filetype'] = 'Excel2007';
$prjm_id=266;
$criteria = criteriaShortAsArray(getCriteria($prjm_id),'en');
$criteria[] ='Overall';
$sql = "select prjtg_id from prj_tutor where prjm_id=$prjm_id";
$sql2 = "";
$resultSet = $dbConn->Execute($sql);
$con = " ";
while (!$resultSet->EOF) {
    extract($resultSet->fields);
    $sql2 .= $con . " select * from assessment_grade_set($prjtg_id,6.5)\n";
    $con = "union\n\t";

    $resultSet->moveNext();
}
//echo "<pre>$sql2</pre>\n";
$sqlt = "select s.snummer,achternaam,roepnaam,tussenvoegsel,gebdat,grp_num,grade\n"
        . " from ($sql2) ags \n"
        . " join prj_grp using(prjtg_id,snummer)"
        . " join all_prj_tutor using(prjtg_id) \n"
        . " join student s using(snummer) order by grp_num,achternaam";
//echo "<pre>$sqlt</pre>\n";
global $ADODB_FETCH_MODE;
$ADODB_FETCH_MODE = ADODB_FETCH_NUM;

$spreadSheetWriter = new SpreadSheetWriter($dbConn, $sqlt);

$headers=array('snummer','achternaam', 'roepnaam','tussenvoegsel', 'gebdat', 'grp_num',$criteria );
$filename = 'groupresult' . date('Y-m-d');
$c = array('FFC0C0FF','FFFFFFFF');
$spreadSheetWriter->setFilename($filename)
        ->setTitle("Group result")
        ->setLinkUrl("http://www.fontysvenlo.org")
       ->setColorChangerColumn(5)
        ->setFilename($filename)->setRowParser(new RowWithArraysPreHeadersParser($headers));
$spreadSheetWriter->processRequest();
?>
