<?php
requireCap(CAP_TUTOR);
require_once('validators.php');
require_once('navigation2.php');
require_once 'simplequerytable.php';
require_once 'classSelector.php';
require_once 'SpreadSheetWriter.php';

$slb = $peer_id;
// get group tables for a project
$hoofdgrp = 'TUTORINF';

if (isSet($_REQUEST['slb'])) {
    $_SESSION['slb'] = $hoofdgrp = validate($_REQUEST['slb'], 'integer', '0');
}

extract($_SESSION);

$oldClassSelector = hoofdgrpSelector($dbConn, 'hoofdgrp', $hoofdgrp);

if (isSet($hoofdgrp)) {
    $sql = "select trim(f.faculty_short) as faculty_short,trim(hoofdgrp) as hoofdgrp\n" .
            " from hoofdgrp_s h join faculty f using(faculty_id) where hoofdgrp='$hoofdgrp'";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet !== false) {
        extract($resultSet->fields);
    }
    //    $dbConn->log($sql);
}

$fdate = date('Y-m-d');
$sql = "select tutor from tutor where userid=$slb";
$rs = $dbConn->Execute($sql);
$tutorCode = $rs->fields['tutor'];

$sqlhead = "select distinct snummer,"
        . "achternaam||rtrim(coalesce(', '||tussenvoegsel,'')::text) as achternaam ,roepnaam, "
        . "pcn,gebdat as birth_date,t.tutor as slb,rtrim(email1) as email1,"
        . "studieplan_short as studieplan,cohort,sclass,hoofdgrp \n"
        //.",straat,huisnr,plaats,phone_gsm,phone_home"
        . ",sort1\n"
        . "from \n";
$sqltail = " join student_class using(class_id) left join tutor t on (s.slb=t.userid)\n" 
        ." left join studieplan using(studieplan)\n" 
        ." left join faculty f on(f.faculty_id=s.faculty_id)\n" 
        ."where slb='$slb' and snummer not in (select userid from tutor) order by cohort desc,sclass,achternaam,roepnaam\n";

$spreadSheetWriter = new SpreadSheetWriter($dbConn, $sqlhead . ' student_email s ' . $sqltail);

$filename = 'slb_list_' . $faculty_short . '_' . $tutorCode . '-' . date('Y-m-d');

$spreadSheetWriter->setFilename($filename)
        ->setTitle("Slb groep list  $faculty_short $tutorCode $fdate")
        ->setLinkUrl($server_url . $PHP_SELF)
        ->setFilename($filename)
        ->setAutoZebra(true);

$spreadSheetWriter->processRequest();
$spreadSheetWidget = $spreadSheetWriter->getWidget();

$sqlhead = "select distinct '<a href=''student_admin.php?snummer='||snummer||'''target=''_blank''>'||snummer||'</a>' as snummer,\n"
        . "'<img src='''||photo||''' style=''height:24px;width:auto;''/>' as foto,\n"
        . "achternaam||rtrim(coalesce(', '||tussenvoegsel,'')::text) as achternaam ,roepnaam, \n"
        . "pcn,cohort,t.tutor as slb,gebdat as birth_date,rtrim(email1) as email1,\n"
        . "studieplan_short as studieplan,faculty_short as facul,sclass,hoofdgrp\n"
        //. ",straat,huisnr,plaats,phone_gsm,phone_home\n"
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

$sql_slb = "select mine,namegrp,name,userid as value from tutor_selector($peer_id) \n"
        . "order by mine,namegrp,name";
$slbList = "<select name='slb'>\n" . getOptionListGrouped($dbConn, $sql_slb, $slb) . "\n</select>";

pagehead2('list students by a slb', $scripts);
$page_opening = "Student list for slb ";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
?>
<?= $nav->show() ?>
<div id='navmain' style='padding:1em;'>
    <fieldset><legend>Select students by slb (study coach)</legend>
        <p>Choose the slb for to see then pupils.</p>

        <p>If you want to retrieve  it (named <?= $filename ?>) as a <strong>spread sheet</strong>, select the spreadsheet option below.</p>
        <form method="get" name="project" action="<?= $PHP_SELF; ?>">
            <?= $slbList ?>
            <input type='submit' name='get' value='Get slb' />&nbsp;<?= $spreadSheetWidget ?>
        </form>
    </fieldset>
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
