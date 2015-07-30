<html>
<body>
<?php
   require_once './peerlib/studentPrjMilestoneSelector.php';
$prjSel = new StudentMilestoneSelector($dbConn,2133872,1);
$psel = $prjSel->getWidget();
?>
<?=$psel?>
</body>
</html>