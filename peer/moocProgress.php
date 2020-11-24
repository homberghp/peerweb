<?php
requireCap(CAP_TUTOR);
require_once('validators.php');
require_once('navigation2.php');
include 'simplequerytable.php';
require_once 'ClassSelectorClass.php';
require_once 'SpreadSheetWriter.php';
// get group tables for a project
$class_id = 1;

$fileExtension = 'xls';
if (isSet($_REQUEST['class_id'])) {
    $_SESSION['class_id'] = $class_id = validate($_REQUEST['class_id'], 'integer', 1);
}
extract($_SESSION);

$classSelectorClass = new ClassSelectorClass($dbConn, $class_id);
$oldClassSelector = $classSelectorClass->addConstraint(" sclass in ('INF1-A','INF1-B','INF1-C')")->setAutoSubmit(true)->getSelector();


if (isSet($class_id)) {
    $sql = "select trim(faculty_short) as faculty_short,trim(sclass) as sclass\n" .
            " from student_class join faculty using(faculty_id) where class_id=$class_id";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet !== false) {
        extract($resultSet->fields);
    }
}

$sql1 = "select * from mooc_progress where sclass='{$sclass}' order by achternaam,roepnaam";


$fdate = date('Y-m-d');
$filename = "mooc_progress_{$sclass}-{$fdate}";

$spreadSheetWriter = new SpreadSheetWriter($dbConn, $sql1);

$spreadSheetWriter->setTitle("Mooc Progress  $faculty_short $sclass $fdate")
        ->setLinkUrl($server_url . basename(__FILE__). '?class_id=' . $class_id)
        ->setFilename($filename)
        ->setAutoZebra(true);

$spreadSheetWriter->processRequest();
$spreadSheetWidget = $spreadSheetWriter->getWidget();

//echo "<pre>{$sqlhead2}</pre>";
$scripts = '<script type="text/javascript" src="js/jquery.min.js"></script>
    <script src="js/jquery.tablesorter.js"></script>
    <script type="text/javascript">                                         
      $(document).ready(function() {
           $("#myTable").tablesorter({widgets: [\'zebra\']}); 
      });

    </script>
    <link rel=\'stylesheet\' type=\'text/css\' href=\'' . SITEROOT . '/style/tablesorterstyle.css\'/>
';


pagehead2('Mooc Progress', $scripts);
$page_opening = "Mooc Progress class $faculty_short:$sclass ($class_id) ";
$nav = new Navigation($tutor_navtable, basename(__FILE__), $page_opening);
$nav->setInterestMap($tabInterestCount);
$maillisthead = strtolower($faculty_short) . '.' . strtolower($sclass);
 $nav->show() ;
         ?>
<div id='navmain' style='padding:1em;'>
    <fieldset><legend>Select class</legend>
        <p>Choose the class of which you want to retrieve the data.</p>
        <p>If you want to retrieve the date as a <strong>spread sheet</strong>, select the spreadsheet option below.</p>
        <form method="get" name="project" action="<?= basename(__FILE__); ?>">
            <?= $oldClassSelector ?>
            <input type='submit' name='get' value='Get class' />&nbsp;<?= $spreadSheetWidget ?>
        </form>
    </fieldset>
    <a href='classtablecards.php?class_id=<?= $class_id ?>'>tablecards for class <?= $faculty_short . ':' . $sclass ?> class_id <?= $class_id ?></a>
    <!-- <ul> -->
    <span>part 01: 36</span>
    <span>part 02: 44</span>
    <span>part 03: 32</span>
    <span>part 04: 43</span>
    <span>part 05: 30</span>
    <span>part 06: 33</span>
    <span>part 07: 24</span>
    <span>part 08: 22</span>
    <span>part 09: 41</span>
    <span>part 10: 36</span>
    <span>part 11: 23</span>
    <span>part 12: 16</span>
    <span>part 13: 15</span>
    <span>part 14: 17</span>
    <span>total: 412</span>
    <!--    </ul> -->
    <div align='center'>
        <?php
        simpletable($dbConn, $sql1, "<table id='myTable' class='tablesorter' summary='your requested data'"
                . " style='empty-cells:show;border-collapse:collapse' border='1'>");
        ?>
    </div>
</div>
</body>
</html>
<?php
echo "<!-- dbname=$db_name -->";
