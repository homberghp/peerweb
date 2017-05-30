<?php
  //session_start();
echo $_SESSION['prj_id']."<br>";
if (isSet($_SESSION['judge'])) {
  $snummer=$_SESSION['judge'];	
}
if (isSet($_SESSION['prj_id'])) {
  $prj_id=$_SESSION['prj_id'];	
}
?>
<!DOCTYPE public "-//w3c//dtd html 4.01 transitional//en"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<META HTTP-EQUIV="refresh" content="3;URL=peerassessment.php?snummer=<?=$snummer?>&prj_id=<?=$prj_id?>">
<link rel="stylesheet" type="text/css" href="style.css">
<title>store</title>
</head>
<body>
<?php 
include_once('./peerlib/peerutils.php');
$c=count($_POST['criterium']);
echo "number of results=$c<br>";
$continuation='';
$prj_id=$_POST['prj_id'];
$grp= $_SESSION['grp'];
$milestone= $_POST['fase'];
$judge= $_POST['judge'];
$sql="begin work;\n";

$resultSet=$dbConn->Execute($sql);
for ( $i=0; $i < $c ; $i++){
#    $prj_id= $_POST['prj_id'][$i];
    $contestant= $_POST['contestant'][$i];
    $criterium= $_POST['criterium'][$i];
    $grade =  $_POST['grade'][$i];
    $sql .= $continuation."update assessment set grade =$grade where ".
	"prj_id=$prj_id and milestone=$milestone and ".
	"contestant=$contestant and judge=$judge and criterium=$criterium and grp='$grp'";
    $continuation=";\n";
}
$sql .=";\ncommit";
?>
<div id="content">
<?php
echo "<pre>\n$sql\n</pre>\n";
$resultSet=$dbConn->Execute($sql);
if ($resultSet === false) {
	print 'error inserting: '.$dbConn->ErrorMsg().'<BR>';
}
#phpinfo(INFO_VARIABLES);
?>
</div>
</body>
</html>
<?php
?>