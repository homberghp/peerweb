<?php
requireCap(CAP_EDIT_RIGHTS);
require_once 'peerutils.php';
include_once('navigation2.php');
require_once 'bitset.php';
require_once 'studentpicker.php';
$newuserid = $peer_id;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$dbMsg = '';
extract($_SESSION);

$tutorPicker = new StudentPicker($dbConn, $newuserid, 'Search tutor.');
$tutorPicker->setShowAcceptButton(false);
$tutorPicker->setPresentQuery("select userid as snummer from tutor");

$newuserid = $tutorPicker->processRequest();
$tutor_picker_text = $tutorPicker->getPicker();
$cluster_widget = "<table border='1' style='border-collapse:collapse; width:100%'>\n";
if (isSet($_REQUEST['class_cluster']) && isSet($_REQUEST['newuserid'])) {
    $newuserid = $_REQUEST['newuserid'];
    $sql1 = "begin work;\n"
            . "delete from tutor_class_cluster where userid=$newuserid;\n";
    for ($i = 0; $i < count($_REQUEST['class_cluster']); $i++) {
        if ($_REQUEST['cluster_order'][$i] > 0) {
            $sql1 .="insert into tutor_class_cluster select {$newuserid},{$_REQUEST['class_cluster'][$i]},{$_REQUEST['cluster_order'][$i]};\n";
        }
    }
    $sql1 .="commit;\n";
    $rs = $dbConn->Execute($sql1);
}

$sql = "select cluster_name, cluster_description, class_cluster,sort_order,coalesce(cluster_order,0) as cluster_order\n"
        . " from class_cluster left join (select class_cluster,cluster_order from tutor_class_cluster where userid=$newuserid) tcc using(class_cluster)\n"
        . "order by sort_order";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    die('cannot get cluster  data:' . $dbConn->ErrorMsg() . ' with <pre>' . $sql . "</pre>\n");
}
while (!$resultSet->EOF) {
    extract($resultSet->fields);
    $cluster_widget .="<tr>"
            . "<td>$class_cluster <input type='hidden' name='class_cluster[]' value ='$class_cluster'/></td>"
            . "<td>$cluster_name</td>"
            . "<td>$cluster_description</td>"
            . "<td><input type='number' name='cluster_order[]' style='text-align:right; width:3em;' value='$cluster_order' min='0'/></td>"
            . "</tr>\n";
    $resultSet->moveNext();
}

$cluster_widget .= "</table><br/>";

pagehead2('Set tutor cluster assignment');
$page_opening = "Set the class cluster(s) the tutor should see/works in.";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);


echo $nav->show();

include_once 'templates/tutorcluster.xhtml';
?>
</body>
</html>
