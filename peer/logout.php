<?php
requireCap(CAP_DEFAULT);
$peer_id= $_SESSION['peer_id'];
$sql ='insert into logoff (userid) values (?)';

$dbConn->prepare($sql)->execute([$peer_id]);
//$dbConn->setSqlLogModifyingQuery(false);
savesessiondata( $dbConn, $peer_id );
session_destroy();
header("location: $root_url/login.php");
