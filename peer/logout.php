<?php
requireCap(CAP_DEFAULT);
$peer_id= $_SESSION['peer_id'];
$sql ="insert into logoff (userid) values ($peer_id)";
$resultSet= $dbConn->Execute($sql);
$dbConn->setSqlLogModifyingQuery(false);
savesessiondata( $dbConn, $peer_id );
session_destroy();
header("location: $root_url/login.php");
