<?php
include_once('./peerlib/peerutils.inc');
require_once('./peerlib/validators.inc');
include_once('navigation2.inc');
requireCap(CAP_TUTOR);
include './peerlib/simplequerytable.inc';
require_once 'ClassSelectorClass.php';
require_once 'SpreadSheetWriter.php';
// get group tables for a project
$class_id = 1;

$fileExtension = 'xls';
if (isSet($_REQUEST['class_id'])) {
    $_SESSION['class_id'] = $class_id = $_REQUEST['class_id'];
}
extract($_SESSION);

$classSelectorClass = new ClassSelectorClass($dbConn, $class_id);
$oldClassSelector = $classSelectorClass->addConstraint('sort1 < 10 and student_count <>0')->getSelector();


if (isSet($class_id)) {
    $sql = "select trim(faculty_short) as faculty_short,trim(sclass) as sclass\n" .
            " from student_class join faculty using(faculty_id) where class_id=$class_id";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet !== false) {
        extract($resultSet->fields);
    }
}



$sqlhead = "select distinct snummer,"
        . "achternaam||rtrim(coalesce(', '||voorvoegsel,'')::text) as achternaam ,roepnaam, "
        . "pcn,gebdat as birth_date,t.tutor as slb,rtrim(email1) as email1,rtrim(email2) as email2,\n"
        . "studieplan_short as studieplan,sclass,hoofdgrp ,\n"
        . "straat,huisnr,plaats,phone_gsm,phone_home\n"
        . " from \n";
$sqltail = " join student_class using(class_id) left join tutor t on (s.slb=t.userid)\n"
        . " left join studieplan using(studieplan)\n"
        . "where class_id='$class_id' order by achternaam,roepnaam";


$fdate = date('Y-m-d');
$filename = 'class_list_' . $faculty_short . '_' . $sclass . '-' . $fdate;

$spreadSheetWriter = new SpreadSheetWriter($dbConn, $sqlhead . ' student s left join alt_email aem using(snummer) ' . $sqltail);

$spreadSheetWriter->setTitle("Class list  $faculty_short $sclass $fdate")
        ->setLinkUrl($server_url . $PHP_SELF . '?class_id=' . $class_id)
        ->setFilename($filename)
        ->setAutoZebra(true);

$spreadSheetWriter->processRequest();
$spreadSheetWidget = $spreadSheetWriter->getWidget();

$sqlhead = "select distinct '<a href=''student_admin.php?snummer='||snummer||''' target=''_blank''>'||snummer||'</a>' as snummer," .
        "'<img src='''||photo||''' style=''height:24px;width:auto;''/>' as foto,\n"
        . "achternaam||rtrim(coalesce(', '||voorvoegsel,'')::text) as achternaam ,roepnaam, " .
        "pcn,cohort,t.tutor as slb,gebdat as birth_date,rtrim(email1) as email1,rtrim(email2) as email2,\n" .
        "studieplan_short as studieplan,sclass,hoofdgrp,\n" .
        "straat,huisnr,plaats,phone_gsm,phone_home\n" .
        " from \n";
$sql2 = $sqlhead . ' student_email s natural join portrait ' . $sqltail;
$rainbow = new RainBow(STARTCOLOR, COLORINCREMENT_RED, COLORINCREMENT_GREEN, COLORINCREMENT_BLUE);
$scripts = '<script type="text/javascript" src="js/jquery.js"></script>
    <script src="js/jquery.tablesorter.js"></script>
    <script type="text/javascript">                                         
      $(document).ready(function() {
           $("#myTable").tablesorter({widgets: [\'zebra\']}); 
      });

    </script>
    <link rel=\'stylesheet\' type=\'text/css\' href=\'' . SITEROOT . '/style/tablesorterstyle.css\'/>
';


pagehead2('Get class list', $scripts);
$page_opening = "Class list for class $faculty_short:$sclass ($class_id) ";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$maillisthead = strtolower($faculty_short) . '.' . strtolower($sclass);
$known_maillist = '/home/maillists/' . $maillisthead . '.maillist';
$class_mail_address = 'no email list exists';
$filename = $known_maillist;
$filetime = 'never';
if (file_exists($filename)) {
    $filetime = date("Y-m-d H:i:s", filemtime($filename));
    $class_mail_address = "Existing class email address:&nbsp;<a href='mailto:$maillisthead@fontysvenlo.org'><tt style='fontsize:120%;color:#008;font-weight:bold'>"
            . "$maillisthead@fontysvenlo.org</tt></a> last update {$filetime}";
}
?>
<?= $nav->show() ?>
<div id='navmain' style='padding:1em;'>
    <fieldset><legend>Select class</legend>
        <p>Choose the class of which you want to retrieve the data.</p>
        <p>If you want to retrieve  it (named <i>"<?= $filename ?>"</i>) as a <strong>spread sheet</strong>, select the spreadsheet option below.</p>
        <form method="get" name="project" action="<?= $PHP_SELF; ?>">
            <?= $oldClassSelector ?>
            <input type='submit' name='get' value='Get class' />&nbsp;<?= $spreadSheetWidget ?>
        </form>
    </fieldset>
    <a href='classtablecards.php?class_id=<?= $class_id ?>'>tablecards for class <?= $faculty_short . ':' . $sclass ?> class_id <?= $class_id ?></a>
    <?= $class_mail_address ?>
    <div align='center'>
        <?php
        simpletable($dbConn, $sql2, "<table id='myTable' class='tablesorter' summary='your requested data'"
                . " style='empty-cells:show;border-collapse:collapse' border='1'>");
        ?>
    </div>
    <?= $class_mail_address ?>
</div>
</body>
</html>
<?php
echo "<!-- dbname=$db_name -->";
?>