<?php
include_once('./peerlib/peerutils.php');
require_once('./peerlib/validators.php');
include_once('navigation2.php');
require_once './peerlib/simplequerytable.php';
require_once 'prjMilestoneSelector2.php';
requireCap(CAP_TUTOR);

$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
extract($_SESSION);

$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
extract($prjSel->getSelectedData());
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;

$filename = 'limetokens_' . $afko . '-' . date('Ymd') . '.csv';

$csvout = 'N';
$csvout_checked = '';
if (isSet($_REQUEST['csvout'])) {
    $csvout = $_REQUEST['csvout'];
    $csvout_checked = ($csvout == 'Y') ? 'checked' : '';
}

// <a href='../emailaddress.php?snummer=snummer'>snummer</a>

$sql = "select firstname,lastname,email,emailstatus,token,language_code,attribute_1,attribute_2 \n" .
        "from lime_token where attribute_2=$prjm_id  order by lastname,firstname";

$sql2 = $sql;

$rainbow = new RainBow(STARTCOLOR, COLORINCREMENT_RED, COLORINCREMENT_GREEN, COLORINCREMENT_BLUE);
if ($csvout == 'Y') {
    $content_header = 'Content-type: text/x-comma-separated-values; charset: UTF-8;';
    $dbConn->queryToCSV($sql, $filename, ';', true, $content_header, false);
    exit(0);
}
pagehead('Get lime survey token table');
$page_opening = "Lime survey token file project $afko $description <span style='font-size:6pt;'>prj_id $prj_id milestone $milestone </span>";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);

$prjSel->setJoin('milestone_grp using (prj_id,milestone)');
$prj_id_selector = $prjSel->getSelector();
?>
<?= $nav->show() ?>
<div id='navmain' style='padding:1em;'>
    <fieldset><legend>Select project</legend>
        <form method="get" name="project" action="<?= $PHP_SELF; ?>">
            <?= $prj_id_selector ?>
            csv:
            <input type='checkbox' name='csvout' <?= $csvout_checked ?> value='Y' />
            <input type='submit' name='get' value='Get' />
        </form><br/>
        <?= $prjSel->getSelectionDetails() ?>
</div>
</fieldset>
<div align='left' style='margin:0 10ex 0 10ex'>
    <?= simpletable($dbConn, $sql, "<table summary='candidates' style='border-collapse:collapse' border='1'>") ?>
</div>
</body>
</html>
<?php
echo "<!-- dbname=$db_name -->";
?>