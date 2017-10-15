<?php
include_once('./peerlib/peerutils.php');
require_once('./peerlib/validators.php');
requireCap(CAP_TUTOR);
include_once('navigation2.php');
include './peerlib/simplequerytable.php';
require_once 'SpreadSheetWriter.php';
require_once 'TemplateWith.php';

$sql = $query_text = '';
$expanded_query = $query_name = '';
if (isSet($_REQUEST['query_id'])) {
    $query_id = validate($_REQUEST['query_id'], 'integer', 1);
    $my_query = "select query_name,owner,query_comment,query as query_text from any_query where any_query_id=$query_id";
    $rs = $dbConn->Execute($my_query);
    if ($rs === FALSE) {
        echo "cannot execute" . $dbConn->ErrorMsg() . "\n";
    }
    extract($rs->fields);
}
if (isSet($_REQUEST['query_text'])) {
    $sql = $query_text = $_REQUEST['query_text'];
    $expanded_query=templateWith($query_text, get_defined_vars());
}
if (isSet($_REQUEST['query_name'])) {
    $query_name = $_REQUEST['query_name'];
}

if (isSet($_REQUEST['save'])) {
    $query_name = $_REQUEST['query_name'];
    $query_comment = $_REQUEST['query_comment'];
    $query_text = $_REQUEST['query_text'];
    $query_name_s = pg_escape_string($_REQUEST['query_name']);
    $query_comment_s = pg_escape_string($_REQUEST['query_comment']);
    $query_text_s = pg_escape_string($_REQUEST['query_text']);
    $save_query = "begin work;\n"
            . "update any_query set active = false where owner={$peer_id} and query_name='{$query_name_s}';\n"
            . "insert into any_query(owner,query_name,query_comment,query)\n"
            . "values($peer_id,'$query_name_s','$query_comment_s','$query_text_s');"
            . "\ncommit;";
    $dbConn->Execute($save_query);
}

if (isSet($_REQUEST['delete_query']) && preg_match('/^\d+$/', $_REQUEST['delete_query'])) {
    $dquery = $_REQUEST['delete_query'];

    $delete_query = "delete from any_query where owner={$peer_id} and any_query_id={$dquery}";
    $dbConn->Execute($delete_query);
}

$spreadSheetWriter = new SpreadSheetWriter($dbConn, $expanded_query);
$fdate=date('Y-m-d-H-i');
$filename = "anyquery-{$fdate}";

$spreadSheetWriter->setFilename($filename)
        ->setTitle("Query $query_name ($peer_id) $fdate")
        ->setLinkUrl($server_url . $PHP_SELF)
        ->setAutoZebra(true);

$spreadSheetWriter->processRequest();
$spreadSheetWidget = $spreadSheetWriter->getWidget();

$scripts = '<script type="text/javascript" src="js/jquery.js"></script>
    <script src="js/jquery.tablesorter.js"></script>
    <script type="text/javascript">                                         
      $(document).ready(function() {
           $("#myTable").tablesorter({widgets: [\'zebra\']}); 
      });

    </script>
    <link rel=\'stylesheet\' type=\'text/css\' href=\'' . SITEROOT . '/style/tablesorterstyle.css\'/>
';
pagehead2('Execute and sql query to the database', $scripts);
$page_opening = "Execute a query";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$my_queries = "select any_query_id,owner,query_name,query,query_comment from any_query where active";
$resultSet = $dbConn->Execute($my_queries);
$my_queries_table = '';
if ($resultSet !== FALSE) {
    if (!$resultSet->EOF) {
        $my_queries_table .="<table border='1' style='border-collapse:collapse; background:rgba(224,224,224,0.8)' width='100%'>\n"
                . "<tr><th>query id</th><th>owner id</th><th>query comment</th><th>query text</th><th>&nbsp;</th></tr>";
        while (!$resultSet->EOF) {
            extract($resultSet->fields);
            $my_queries_table .= "<tr>"
                    . "<td><a href='$PHP_SELF?query_id=$any_query_id'>{$any_query_id}: {$query_name}</a></td>"
                    . "<td>$owner</td>"
                    . "<td>$query_comment</td><td><pre>$query</pre></td><td><a href='{$PHP_SELF}?delete_query={$any_query_id}' title='delete query'><img src='images/delete-icon.png' border='0' alt='delete'/></td></tr>\n";
            $resultSet->moveNext();
        }
        $my_queries_table .="</table>\n";
    }
}

$nav->show()
?>
<div id='navmain' style='padding:1em;'>
    <fieldset><legend>Query text</legend>
        <form method="get" name="project" action="<?= $PHP_SELF; ?>">
            If you would like to save the query, give it a name<br/>
            <input type='text' name='query_name' value='<?= $query_name ?>' width='30'/><input type='submit' name='save' value='Save'/><br/>
            Comment:<br/>
            <textarea rows='5' cols='60' name='query_comment' ><?= $query_comment ?></textarea>
            <br/>
            Query text:<br/>
            <textarea rows='5' cols='120' name='query_text'><?= $query_text ?></textarea>
            <br/>

            <input type='submit' name='get' value='Execute' />
            <?= $spreadSheetWidget ?>
        </form>
    </fieldset>
    <div>For query <pre><?= $sql ?></pre>
        <?php
        if ($sql != '' && !preg_match("/(begin|drop|delete|insert|commit)/", $sql)) {
            $expanded_sql=templateWith($sql, get_defined_vars());
            simpletable($dbConn, $expanded_sql, "<table id='myTable' class='tablesorter' summary='your requested data'"
                    . " style='empty-cells:show;border-collapse:collapse' border='1'>");
        }
        ?>
    </div>
    <fieldset><legend>Saved Queries</legend>
        <?= $my_queries_table ?>
    </fieldset>
</body>
</html>
<?php
echo "<!-- dbname=$db_name -->";
?>
