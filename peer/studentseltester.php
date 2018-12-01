<html>
<body>
<?php
requireCap(CAP_SYSTEM);

   require_once 'studentPrjMilestoneSelector.php';
$prjSel = new StudentMilestoneSelector($dbConn,2133872,1);
$psel = $prjSel->getWidget();
?>
<?=$psel?>
</body>
</html>