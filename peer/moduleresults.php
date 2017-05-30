<?php
include_once('./peerlib/peerutils.php');
include_once('navigation2.inc');
require_once 'prjMilestoneSelector2.php';
requireCap(CAP_TUTOR);
$prj_id = 0;
$milestone = 1;
$grp_num = 1;
$prjtg_id = 1;
$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
extract($_SESSION);

$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
extract($prjSel->getSelectedData());
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;

$csvout = 'N';
$sql = "select max(pm.prj_id) as prj_id, min(pt.grp_num) as grp_num, min(pm.milestone) as milestone\n"
        . " from (select distinct prjtg_id from assessment ) a join prj_tutor pt using(prjtg_id) join prj_milestone pm using(prjm_id)";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    echo( "<br>Cannot get prj_id, grp_num with \"" . $sql . '", cause ' . $dbConn->ErrorMsg() . "<br>");
    stacktrace(1);
    die();
}
if (isset($resultSet->fields))
    extract($resultSet->fields);
extract($_SESSION);
$filename = 'peerassessment.csv';

if (isSet($_POST['prj_id_milestone'])) {
    list($prj_id, $milestone) = explode(':', $_POST['prj_id_milestone']);
    $_SESSION['prj_id'] = $prj_id;
    $_SESSION['milestone'] = $milestone;
}
if (isSet($_POST['grp_num'])) {
    $_SESSION['grp_num'] = $grp_num = $_POST['grp_num'];
}
if (isSet($_POST['prjtg_id'])) {
    $_SESSION['prjtg_id'] = $prjtg_id = $_POST['prjtg_id'];
}
$sql = "select * from project where prj_id=$prj_id";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    echo 'error fetching afko: ' . $dbConn->ErrorMsg() . '<br> with' . $sql . '<br>';
    stacktrace(1);
}
if (!$resultSet->EOF)
    extract($resultSet->fields);
$filename = trim($afko) . (($grp_num == '*') ? 1 : $grp_num) . 'm' . $milestone . '_' . date('Y-m-d') . '.csv';
if (isSet($_POST['csvout'])) {
    $csvout = $_POST['csvout'];
}
$checked = ($csvout == 'Y') ? 'checked' : '';
if (isSet($_POST['get'])) {

    $sql = "select "
            . "a.contestant,c.achternaam||', '||c.roepnaam||' '||coalesce(c.voorvoegsel,'') as contestant_name,\n "
            . "a.judge,j.achternaam||', '||j.roepnaam||' '||coalesce(j.voorvoegsel,'') as judge_name,\n"
            . "p.afko,pt.grp_num,pm.milestone,a.criterium,\n"
            . "a.grade,to_char(commit_time,'YYYY-MM-DD HH24:MI:SS') as entry_time \n"
            . "from assessment a join student j on (j.snummer=a.judge) \n"
            . " join student c on (c.snummer=a.contestant) \n"
            . " join prj_tutor pt using(prjtg_id) \n"
            . " join tutor t on(userid=tutor_id)\n "
            . " join prj_milestone pm on(pt.prjm_id=pm.prjm_id)\n "
            . " join project p on(pm.prj_id=p.prj_id)\n"
            . " left join (select prjtg_id,snummer,max(commit_time) as commit_time \n"
            . " from assessment_commit a group by a.prjtg_id,a.snummer) lac "
            . " on(a.judge=lac.snummer and a.prjtg_id=lac.prjtg_id )\n"
            . " where pm.prj_id=$prj_id and pm.milestone=$milestone and pt.prjtg_id=$prjtg_id\n"
            . " order by pt.grp_num,c.achternaam,a.criterium";
    $dbConn->log($sql);
    if ($csvout == 'Y') {
        $dbConn->queryToCSV($sql, $filename);
        exit(0);
    }
}
// $sqlgrp = "select 'g'||grp_num||' ('||tutor||')'||coalesce(': '||alias,'')  as name,\n" .
//        "prjtg_id as value,grp_num \n" .
//        "from prj_tutor pt join prj_milestone pm using(prjm_id) \n" .
//        "left join grp_alias using(prjtg_id) where prjm_id=$prjm_id order by grp_num";

$sqlgrp = "select distinct grp_num||' ('||tutor||')'||' ['||prjtg_id||']'||coalesce(': '||alias ,'')" .
        " as name,\n" .
        " prjtg_id as value,case when ago.open=true then 'background:#fee' else 'font-weight:bold;background:#efe' end as style\n" .
        " from prj_grp natural join prj_tutor \n" .
        "join tutor on(userid=tutor_id)\n".
        " natural join assessment_grp_open ago \n" .
        " natural left join grp_alias \n" .
        " where prjm_id=$prjm_id order by prjtg_id";
//echo " <pre>$sqlgrp</pre>\n";
$grpList = getOptionList($dbConn, $sqlgrp, $grp_num);
$scripts = '<script type="text/javascript" src="js/jquery.js"></script>          
    <script src="js/jquery.tablesorter.js"></script>
    <script type="text/javascript">                                         
      $(document).ready(function() {
      // do stuff when DOM is ready
           $("#myTable").tablesorter({ }); 
      });

    </script>
    <link rel=\'stylesheet\' type=\'text/css\' href=\'' . SITEROOT . '/style/tablesorterstyle.css\'/>
';
pagehead2('Get the raw data of an assessment', $scripts);

$page_opening = "Scores voor module $afko: $description ($year) milestone $milestone " .
        "<span style='font-size:6pt;'>($prj_id M $milestone)</span>";

$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$prjSel->setJoin('available_assessment using (prjm_id)');

$prj_id_selector = $prjSel->getSelector();
?>
<?= $nav->show() ?>
<div id='navmain' style='padding:1em;'>
    <p>Deze pagina maakt het mogelijk de ingevulde waarden voor een module (project) op te halen.
        De gegevens worden gepresenteerd als een tabel of een csv file die automatisch door excel wordt opengemaakt</p>
    <p>De naam van de csv-file wordt gemaakt van module afkorting, grp,milestone en datum van opvragen. 
        Daarbij wordt groep als een underscore weergegeven indien alle groepen wordt opgevraagd. Voorbeeld:<strong>PRJ11Am1_2004-10-09.csv</strong></p>
    <form method="post" name="moduleresult" action="<?= $PHP_SELF; ?>">
        <table>
            <tr><th>Project milestone</th><td><?= $prj_id_selector ?></td></tr>
            <tr><th>Group</th><td> <select name='prjtg_id'><?= $grpList ?></select>
                    &nbsp;Excel output?&nbsp;<input type="checkbox" name="csvout" value="Y" <?= $checked ?>/>
                    <input type="submit" name="get" value="Get"/></td></tr>
        </table>
    </form>
    <?php
    if (($csvout = 'N') && isSet($_POST['get'])) {
        //	echo "<pre>$sql</pre>";
        queryToTable($dbConn, $sql, true, 1, new RainBow(0x46B4B4, 64, 32, 0));
    }
    ?>
</div>
</body>
</html>