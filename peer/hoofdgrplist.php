<?php
include_once('./peerlib/peerutils.php');
require_once('./peerlib/validators.php');
include_once('navigation2.php');
requireCap(CAP_TUTOR);

include './peerlib/simplequerytable.php';
require_once 'classSelector.php';
require_once 'SpreadSheetWriter.php';

// get group tables for a project
$hoofdgrp = 'TUTORINF';

if (isSet($_REQUEST['hoofdgrp']) && preg_match('/^\w+$/', $_REQUEST['hoofdgrp'])) {
    $_SESSION['hoofdgrp'] = $hoofdgrp = $_REQUEST['hoofdgrp'];
}

extract($_SESSION);
$faculty_short='';
$oldClassSelector = hoofdgrpSelector($dbConn, 'hoofdgrp', $hoofdgrp);

if (isSet($hoofdgrp)) {
    $sql = "select trim(f.faculty_short) as faculty_short,trim(hoofdgrp) as hoofdgrp\n" .
            " from hoofdgrp_s h join faculty f using(faculty_id) where hoofdgrp='{$hoofdgrp}'";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet !== false && ! $resultSet->EOF) {
        extract($resultSet->fields);
    }
    //    $dbConn->log($sql);
}

$fdate = date('Y-m-d');


$sqlhead = "select distinct snummer,"
        . "achternaam||rtrim(coalesce(', '||tussenvoegsel,'')::text) as achternaam ,roepnaam, "
        . "pcn,gebdat as birth_date,t.tutor as slb,rtrim(email1) as email1,"
        . "studieplan_short as studieplan,sclass,hoofdgrp ,\n"
        . "straat,huisnr,plaats,phone_gsm,phone_home\n"
        . "from \n";
$sqltail = " join student_class using(class_id) left join tutor t on (s.slb=t.userid)\n"
        . " left join studieplan using(studieplan)\n"
        . " left join faculty f on(f.faculty_id=s.faculty_id)\n"
        . "where hoofdgrp='$hoofdgrp' order by achternaam,roepnaam\n";

$spreadSheetWriter = new SpreadSheetWriter($dbConn, $sqlhead . ' student s ' . $sqltail);

$filename = 'hoofdgrp_list_' . $faculty_short . '_' . $hoofdgrp . '-' . date('Y-m-d');

$spreadSheetWriter->setFilename($filename)
        ->setTitle("Hoofd groep list  $faculty_short $hoofdgrp $fdate")
        ->setLinkUrl("{$server_url}{$PHP_SELF}?hoofdgrp={$hoofdgrp}")
        ->setFilename($filename)
        ->setAutoZebra(true);

$spreadSheetWriter->processRequest();
$spreadSheetWidget = $spreadSheetWriter->getWidget();

$sqlhead = "select distinct '<a href=''student_admin.php?snummer='||snummer||'''target=''_blank''>'||snummer||'</a>' as snummer,\n"
        . "'<img src='''||photo||''' style=''height:24px;width:auto;''/>' as foto,\n"
        . "achternaam||rtrim(coalesce(', '||tussenvoegsel,'')::text) as achternaam ,roepnaam, \n"
        . "pcn,cohort,t.tutor as slb,gebdat as birth_date,rtrim(email1) as email1,\n"
        . "studieplan_short as studieplan,faculty_short as facul,sclass,hoofdgrp,\n"
        . "straat,huisnr,plaats,phone_gsm,phone_home\n"
        . " from \n";
$sql2 = $sqlhead . ' student_email s natural join portrait ' . $sqltail;
//$dbConn->log($sql2);
$scripts = '<script type="text/javascript" src="js/jquery.js"></script>
    <script src="js/jquery.tablesorter.js"></script>
    <script type="text/javascript">                                         
      $(document).ready(function() {
           $("#myTable").tablesorter({widgets: [\'zebra\']}); 
      });

    </script>
    <link rel=\'stylesheet\' type=\'text/css\' href=\'' . SITEROOT . '/style/tablesorterstyle.css\'/>
';

$cardsLink=
      "<a href='classtablecards.php?rel=student&hoofdgrp={$hoofdgrp}'>table cards for students with  hoofdgrp</a>";

pagehead2('list students by a hoofgrp', $scripts);
$page_opening = "Hoofdgrp  list $faculty_short:$hoofdgrp ";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
?>
<?= $nav->show() ?>
<div id='navmain' style='padding:1em;'>
    <fieldset><legend>Select hoofdgrp</legend>
        <p>Choose the hoofdgrp of which you want to retrieve the data. hoofdgrp is a label that is given to a student as a selection criterion.
            Typically it is used to label students in course type and year or semester.</p>

        <p>If you want to retrieve  it (named <?= $filename ?>) as a <strong>spread sheet</strong>, select the spreadsheet option below.</p>
        <form method="get" name="project" action="<?= $PHP_SELF; ?>">
            <?= $oldClassSelector ?>
            <input type='submit' name='get' value='Get hoofdgrp' />&nbsp;<?= $spreadSheetWidget ?>
        </form>
    </fieldset>
    <?=$cardsLink?>
    <div align='center'>
        <?php
        simpletable($dbConn, $sql2, "<table id='myTable' class='tablesorter' summary='your requested data'"
                . " style='empty-cells:show;border-collapse:collapse' border='1'>");
//queryToTableChecked($dbConn,$sql2,true,-1,$rainbow,-1,'','');
        ?>
    </div>
</div>
</body>
</html>
<?php
echo "<!-- dbname=$db_name -->";
?>
