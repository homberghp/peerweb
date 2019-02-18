<?php
requireCap(CAP_TUTOR);
require_once('validators.php');
require_once('navigation2.php');
include 'simplequerytable.php';
require_once 'prjMilestoneSelector2.php';
require_once 'SpreadSheetWriter.php';


// get group tables for a project
$afko = 'PRJ00';
$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
extract($_SESSION);
$scripts = '';
$grpColumn = 3;
$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
extract($prjSel->getSelectedData());
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;

$filename = 'svnProgress_' . $afko . '-' . date('Ymd');
$title = "Student and groups in project $afko milestone $milestone";
$sql = "select * from svn_progress where prjm_id={$prjm_id}";
$spreadSheetWriter = new SpreadSheetWriter($dbConn, $sql);

$spreadSheetWriter->setFilename($filename)
        ->setLinkUrl($server_url . $PHP_SELF . '?prjm_id=' . $prjm_id)
        ->setTitle($title)
        ->setAutoZebra(false)
        ->setColorChangerColumn($grpColumn);

$spreadSheetWriter->processRequest();

$spreadSheetWidget = $spreadSheetWriter->getWidget();

$rainbow = new RainBow(STARTCOLOR, COLORINCREMENT_RED, COLORINCREMENT_GREEN, COLORINCREMENT_BLUE);
$scripts = '';

pagehead2('SVN Progress', $scripts);
$page_opening = "SVN Progess for project $afko $description <span style='font-size:8pt;'>prjm_id $prjm_id prj_id $prj_id milestone $milestone </span>";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);

$prjSel->setJoin('milestone_grp using (prj_id,milestone)');
$prj_id_selector = $prjSel->getSelector();
$emailList = array();
$grpList = array();
$resultSet = $dbConn->Execute($sql);

$scripts = '<script type="text/javascript" src="js/jquery.js"></script>
    <script src="js/jquery.tablesorter.js"></script>
    <script type="text/javascript">
      $(document).ready(function() {
           $("#myTable").tablesorter({widgets: [\'zebra\']});
      });

    </script>
    <link rel=\'stylesheet\' type=\'text/css\' href=\'' . SITEROOT . '/style/tablesorterstyle.css\'/>
';
$nav->show()
?>
<div id='navmain' style='padding:1em;'>
    <fieldset><legend>Select project</legend>
        <form method="get" name="project" action="<?= $PHP_SELF; ?>">
            <?= $prj_id_selector ?>
            <input type='submit' name='get' value='Get' />
            <?= $spreadSheetWidget ?>
            <?= $prjSel->getSelectionDetails() ?>
        </form>
    </fieldset>
    <div align='left'>
        <?= queryToTableChecked($dbConn, $sql, true, $grpColumn, $rainbow, -1, '', ''); ?>
    </div>
</div>
</body>
</html>
<?php
echo "<!-- dbname=$db_name -->";
?>