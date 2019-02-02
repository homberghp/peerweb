<?php
requireCap(CAP_ALTER_STUDENT_CLASS);
require_once('peerutils.php');
require_once('validators.php');
include_once('navigation2.php');
require_once 'studentpicker.php';
require_once 'ClassSelectorClass.php';
$class_id = 363;
$sclass = "NOCLASS";
$newsnummer = 0;
unset($_SESSION['newsnummer']);
extract($_SESSION);
if (isSet($_REQUEST['newclass_id'])) {
    $_SESSION['class_id'] = $class_id = validate($_REQUEST['newclass_id'], 'integer', '0');
}
$sql = "select sclass from student_class where class_id={$class_id}";
$resultSet = $dbConn->Execute($sql);
if ($resultSet !== FALSE) {
    $sclass = $_SESSION['sclass'] = $resultSet->fields['sclass'];
}

if (isSet($_GET['newsnummer'])) {
    unset($_POST['newsnummer']);
    $_SESSION['newsnummer'] = $_REQUEST['newsnummer'] = $newsnummer = validate($_GET['newsnummer'], 'integer', '0');
    //    $dbConn->log('GET '.$newsnummer);
} else if (isSet($_POST['newsnummer'])) {
    unset($_GET['newsnummer']);
    $_SESSION['newsnummer'] = $_REQUEST['newsnummer'] = $newsnummer = validate($_POST['newsnummer'], 'integer', '0');
    //    $dbConn->log('POST '.$newsnummer);
} else {
    unset($_POST['newsnummer']);
    unset($_REQUEST['newsnummer']);
    unset($_GET['newsnummer']);
    unset($_SESSION['newsnummer']);
}
extract($_SESSION);

$searchname = '';
$studentPicker = "<fieldset><legend>Scanned Student Number</legend>"
        . "<form method='get' style='font-size:150%'>"
        . "Note, that the student selected is moved from source to target class immediately with no undo.<br/>"
        . "<label for='newsnummer'>New student number</label>"
        . "<input type='text' autofocus name='newsnummer' "
        . "  style='text-align:right;' id='newsnummer' onchange='this.submit()' value='' />"
        . "</form></fieldset>";

$_SESSION['searchname'] = $searchname;

if (hasCap(CAP_ALTER_STUDENT_CLASS) && $newsnummer != 0) {
    $sql = "begin work;\n"
            . "with move_prospect as (delete from prospects where snummer={$newsnummer} returning *)\n"
            . "insert into student_email select * from move_prospect;\n"
            . "update student_email set class_id={$class_id} where snummer={$newsnummer};";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === 0) {
        $dbConn->log($dbConn->ErrorMsg());
        $dbConn->Execute("abort");
    } else {
        $dbConn->Execute("commit");
    }
}
$scripts = '<script type="text/javascript" src="js/jquery.js"></script>
    <script src="js/jquery.tablesorter.js"></script>
    <script type="text/javascript">
      $(document).ready(function() {
           $("#myTable").tablesorter({widgets: [\'zebra\']});
      });

    </script>
    <link rel=\'stylesheet\' type=\'text/css\' href=\'' . SITEROOT . '/style/tablesorterstyle.css\'/>
';

$myClassSelector = new ClassSelectorClass($dbConn, $class_id);
$classSelectorWidget = $myClassSelector->setAutoSubmit(true)->setSelectorName('newclass_id')->getSelector();
$sql = "select snummer,achternaam,roepnaam,tussenvoegsel,hoofdgrp,lang \n"
        . " from student_email  \n"
        . "join student_class using(class_id)\n"
        . " where class_id={$class_id} "
        . "  order by hoofdgrp,achternaam,roepnaam";
$rainbow = new RainBow();
$memberTable = simpletableString($dbConn, $sql, "<table id='myTable' class='tablesorter' summary='your requested data'"
        . " style='empty-cells:show;border-collapse:collapse' border='1'>");

pagehead2('Add/move individual student to class.', $scripts);
$page_opening = "Add individual student to a class. <span style='font-size:6pt;'>class_id {$class_id}</span>";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$nav->show();
include_once 'templates/addtoclass.html';
?>
<!-- db_name=<?= $db_name ?> -->
<!-- $Id: addtoclass.php 1853 2015-07-25 14:17:12Z hom $ -->
</body>
</html>
