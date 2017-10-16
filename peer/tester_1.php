<?php

require_once 'peerutils.php';
$sql ="select query from menu_option_queries where menu_name='activity' and column_name='prjm_id'";
$resultSet = $dbConn->Execute($sql);
$con = " ";
$tutor_code='HOM';
$r = $resultSet->fields['query'];
echo "<pre>\n$r\n</pre>\n";
eval("\$result=\"$r\";");
echo "<pre>$result</pre>";
?>
