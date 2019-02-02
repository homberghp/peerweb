<?php
requireCap(CAP_TUTOR);
require_once('validators.php');
include_once('navigation2.php');
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
$grpColumn = 14;
$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
extract($prjSel->getSelectedData());
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;

$filename = 'grouplist_' . $afko . '-' . date('Ymd');
$title = "Student and groups in project $afko milestone $milestone";
$sqlhead = "select s.snummer as snummer,";

// <a href='../student_admin.php?snummer=snummer'>snummer</a>
$sqltail = "achternaam||rtrim(coalesce(', '||tussenvoegsel,'')) as achternaam "
        . ",roepnaam"
        . ", pcn"
        //. ",gebdat as birth_date"
        . ",cohort\n"
        . ",role"
        . ",slb.tutor as slb"
        . ",rtrim(email1) as email1"
        //. ",rtrim(email2) as email2"
        . ",c.sclass as klas"
        . ",afko"
        . ",milestone"
        . ",sp.studieplan_short as studplan\n"
        . ",gi.github_id"
        . ",stick.stick\n"
        . ",apt.grp_num"
        . ",ga.alias"
        . ",apt.grp_name"
        . ",apt.tutor as grp_tutor"
        . ",'' as grade\n"
        . "from prj_grp pg join student_email s using (snummer)\n"
        . " join all_prj_tutor apt on(pg.prjtg_id=apt.prjtg_id)\n"
        . "left join studieplan sp using(studieplan)\n"
        . "left join student_class c using (class_id)\n"
        . "left join student_role sr on(apt.prjm_id=sr.prjm_id and pg.snummer=sr.snummer)\n"
        . "left join project_roles pr on(pr.prj_id=apt.prj_id and sr.rolenum=pr.rolenum)\n"
        . "left join grp_alias ga on(apt.prjtg_id=ga.prjtg_id)\n"
        . "left join alt_email aem on(aem.snummer=pg.snummer) \n"
        . "left join tutor slb on (s.slb=slb.userid) \n"
        . "left join github_id gi on(s.snummer=gi.snummer)\n"
        . "left join sebi_stick stick on(s.snummer=stick.snummer)\n"
        . "where apt.prjm_id=$prjm_id order by grp_num,achternaam,roepnaam";
$spreadSheetWriter = new SpreadSheetWriter($dbConn, $sqlhead . $sqltail);

$spreadSheetWriter->setFilename($filename)
        ->setLinkUrl($server_url . $PHP_SELF . '?prjm_id=' . $prjm_id)
        ->setTitle($title)
        ->setAutoZebra(false)
        ->setColorChangerColumn($grpColumn);

$spreadSheetWriter->processRequest();

$spreadSheetWidget = $spreadSheetWriter->getWidget();
$sqlhead = "select distinct '<a href=\"student_admin.php?snummer='||s.snummer||'\" target=\"_blank\">'||s.snummer||'</a>' as snummer,";


$rainbow = new RainBow(STARTCOLOR, COLORINCREMENT_RED, COLORINCREMENT_GREEN, COLORINCREMENT_BLUE);
$scripts='';
/* $scripts = '<script type="text/javascript" src="js/jquery.js"></script> */
/*     <script src="js/jquery.tablesorter.js"></script> */
/*     <script type="text/javascript"> */
/*       $(document).ready(function() { */
/*       // do stuff when DOM is ready */
/*            $("#myTable").tablesorter({ }); */
/*       }); */

/*     </script> */
/*     <link rel=\'stylesheet\' type=\'text/css\' href=\'' . SITEROOT . '/style/tablesorterstyle.css\'/> */
/* '; */
pagehead2('Get group tables', $scripts);
$page_opening = "Group lists for project $afko $description <span style='font-size:8pt;'>prjm_id $prjm_id prj_id $prj_id milestone $milestone </span>";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);

$prjSel->setJoin('milestone_grp using (prj_id,milestone)');
$prj_id_selector = $prjSel->getSelector();
$emailList = array();
$grpList = array();
$resultSet = $dbConn->Execute($sqlhead . $sqltail);
if ($resultSet === false) {
    $dbConn->log('failed wth' . $dbConn->ErrorMsg());
} else {
    while (!$resultSet->EOF) {
        $email = $resultSet->fields["email1"];
        $alias = $resultSet->fields["alias"];
        $grp_num = $resultSet->fields["grp_num"];
        $emailList[] = $email;
        $grp = "g$grp_num/$alias";
        $grpList[$grp][] = $email;
        $resultSet->moveNext();
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
    <a href='classtablecards.php?prjm_id=<?= $prjm_id ?>'>Project table cards</a>
    <div align='left'>
        <?= queryToTableChecked($dbConn, $sqlhead . $sqltail, true, $grpColumn, $rainbow, -1, '', ''); ?>
    </div>
    <div align='left'>
        <table>
            <?php
            echo "<tr><th>All</th><td>" . implode(';', $emailList) . "</td></tr>\n";
            foreach ($grpList as $key => $list) {
                echo "<tr><th>Group&nbsp;$key</th><td>" . implode(';', $list) . "</td></tr>\n";
            }
            ?>
        </table>
    </div>
</div>
</body>
</html>
<?php
echo "<!-- dbname=$db_name -->";
?>
