<?php
require_once 'component.php';
require_once 'navigation2.php';
require_once 'selector.php';
require_once './peerlib/SimpleTableFormatter.php';
if (!isSet($_SESSION['exam_event_id'])) {
    $sql = "select max(exam_event_id) as exam_event_id from exam_grades ";
    
    $resultSet = $dbConn->Execute($sql);
    if (!$resultSet->EOF) {
        $_SESSION['exam_event_id']= $exam_event_id=$resultSet->fields['exam_event_id'];
    }
}
if (isSet($_REQUEST['exam_event_id'])){
    $_SESSION['exam_event_id']= $exam_event_id=validate($_REQUEST['exam_event_id'],'integer','1');
    
}
$exam_event_id=$_SESSION['exam_event_id'];
/**
 * Show exam results from collected exam grade uploads
 */

$sql = "select progress_code||'-'||exam_date as name,exam_event_id as value "
        ." from exam_event join module_part using(module_part_id) "
        ."where exists (select 1 from exam_grades where exam_event_id =exam_event.exam_event_id)"
        ." order by exam_date desc,progress_code";

$event_query="select  ee.exam_event_id as value, "
        ."m.progress_code||'.'||mp.progress_code||'-'||ee.exam_date as name,"
        ." m.progress_code as grpname from exam_event ee "
        ."join module_part mp using (module_part_id) "
        ."join module m using(module_id) "
        ."order by exam_date desc, mp.progress_code";
$event_selector= new Selector($dbConn, 'exam_event_id',$event_query , $exam_event_id);
$result_query="select snummer,achternaam,roepnaam,grade from exam_result_view where exam_event_id={$exam_event_id}";
$pp = array();
$pp['event_selector']= $event_selector->getSelector();

$page_opening = "Exam results on DB $db_name ";
$nav = new Navigation(array(), basename($PHP_SELF), $page_opening);
$page = new PageContainer();
$page->addBodyComponent( $nav );
$css = '<link rel=\'stylesheet\' type=\'text/css\' href=\'' . SITEROOT . '/style/tablesorterstyle.css\'/>';
$page->addScriptResource( 'js/jquery.js' );
$page->addScriptResource( 'js/jquery.tablesorter.js' );
$page->addHeadText( $css );
$page->addJqueryFragment( '$("#myTable").tablesorter({widgets: [\'zebra\'],headers: {0:{sorter:false}}});
   var table = $("#myTable");
   table.bind("sortEnd",function() { 
    var i = 0;
    table.find("tr:gt(0)").each(function(){
        $(this).find("td:eq(0)").text(i);
        i++;
    });
});  ' );
$detail_query="select progress_code,exam_date,examiner from exam_event natural join module_part where exam_event_id={$exam_event_id}";
$resultSet= $dbConn->Execute($detail_query);
$pp=array_merge($pp,$resultSet->fields);
$tableFormatter = new SimpleTableFormatter( $dbConn, $result_query, $page );
$tableFormatter->setTabledef( "<table id='myTable' class='tablesorter' summary='your requested data'"
        . " style='empty-cells:show;border-collapse:collapse' border='1'>" );
$pp['resultTable'] = $tableFormatter->getTable();

$page->addHtmlFragment('templates/examResults.html', $pp)->show();
