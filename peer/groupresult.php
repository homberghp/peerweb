<?php
include_once('./peerlib/peerutils.php');
requireCap(CAP_TUTOR);
require_once('./peerlib/validators.php');
include_once('navigation2.php');
include_once('groupresult3.php');
include_once 'openBarChart2.php';
require_once 'prjMilestoneSelector2.php';
require_once 'GroupPhoto.class.php';
require_once 'pgrowparser.php';
require_once 'SpreadSheetWriter.php';
require_once 'remarklist.php';
$prj_id = 1;
$prjm_id = 0;
$milestone = 1;
$prjtg_id = 1;
$tutor = $tutor_code;
$grp_num = 1;
extract($_SESSION);
$productgrade = 7.0;
$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
$prjSel->setWhere("has_assessment=true");
extract($prjSel->getSelectedData());

if (isSet($_REQUEST['prjtg_id'])) {
    $_SESSION['prjtg_id'] = $prjtg_id = validate($_REQUEST['prjtg_id'], 'integer', $prjtg_id);
    $sql = "select prj_id,milestone,prjm_id,grp_num,prjtg_id,tutor_grade as productgrade \n"
            . "from all_prj_tutor \n"
            . " where prjtg_id=$prjtg_id";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die("<br>Cannot get project name with <pre>\"" . $sql . '", cause ' . $dbConn->ErrorMsg() . "</pre><br/>");
    }
    if (!$resultSet->EOF)
        extract($resultSet->fields);
    $prjSel->setPrjmId($prjm_id);
    extract($prjSel->getSelectedData());
} else {
    $sql = "select 1 as t,prj_id,milestone,prjm_id,prjtg_id,grp_num,tutor_grade as productgrade from all_prj_tutor \n"
            . " where prjtg_id=$prjtg_id and prjm_id=$prjm_id\n"
            . " union \n"
            . " select 2 as t ,prj_id,milestone,prjm_id,prjtg_id,grp_num,tutor_grade as productgrade from all_prj_tutor\n"
            . " where prjm_id=$prjm_id and grp_num=1\n"
            . " order by t,grp_num limit 1";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die("<br>Cannot get project name with \"" . $sql . '", cause ' . $dbConn->ErrorMsg() . "<br>");
    }
    if (!$resultSet->EOF)
        extract($resultSet->fields);
    $prjSel->setPrjmId($prjm_id);
    extract($prjSel->getSelectedData());
}

$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['prjtg_id'] = $prjtg_id;
$_SESSION['milestone'] = $milestone;
$_SESSION['grp_num'] = $grp_num;

$isTutorOwner = checkTutorOwner($dbConn, $prj_id, $peer_id); // check if this is tutor_owner of this project
$isGroupTutor = checkGroupTutor($dbConn, $prjtg_id, $peer_id);
$fdate = date('Y-m-d');

// process the group openings.
if ($isTutorOwner) {
    groupOpener($dbConn, $prjm_id, $isTutorOwner, $_REQUEST);
}
$csvout = 'N';
$csvout_checked = '';
if (isSet($_REQUEST['csvout'])) {
    $csvout = $_REQUEST['csvout'];
    $csvout_checked = ($csvout == 'Y') ? 'checked' : '';
}

//$productgrade = 7;
if (isSet($_REQUEST['productgrade'])) {
    $_REQUEST['productgrade'] = preg_replace('/,/', '.', $_REQUEST['productgrade']);
    $tmpnum = $_REQUEST['productgrade'];
    if (preg_match("/^\d{1,2}(\.?\d+)?$/", $tmpnum)) {
        $_SESSION['productgrade'] = $productgrade = $tmpnum;
    }
}

if (isSet($_POST['clear'])) {

    $sql = "begin work;\n"
            . "update assessment set grade=0 where prjtg_id=$prjtg_id ;\n"
            . "update prj_grp set written=false,prj_grp_open=true where prjtg_id=$prjtg_id;\n"
            . "delete from assessment_commit where prjtg_id=$prjtg_id; "
            . "delete from milestone_grade where (prjm_id,snummer) in (select prjm_id,snummer from prj_grp join prj_tutor using(prjtg_id) where prjtg_id=$prjtg_id);\n"
            . "commit;\n";
    $resultSet = $dbConn->executeCompound($sql);
    if ($resultSet === false) {
        die("<br>Cannot update assessmenttable with \"" . $sql . '", cause ' . $dbConn->ErrorMsg() . "<br>");
    }
}

//if ( isSet( $_POST[ 'recalc' ] ) && $isGroupTutor ) {
//
//    $sql = "begin work;\n"
//            . "delete from milestone_grade where (prjm_id,snummer) in (select prjm_id,snummer from prj_grp join prj_tutor using(prjtg_id) where prjtg_id=$prjtg_id);\n"
//            . "update prj_tutor set tutor_grade=$productgrade where prjtg_id=$prjtg_id;\n"
//            . "commit;\n";
//    $resultSet = $dbConn->executeCompound( $sql );
//    if ( $resultSet === false ) {
//        die( "<br>Cannot clear milestone_grade with \"" . $sql . '", cause ' . $dbConn->ErrorMsg() . "<br>" );
//    }
//}

if (isSet($_POST['random'])) {
    $sql = //"begin work;\n" .
            "update assessment set grade=2+round(8*random()) \n" .
            "  where prjtg_id=$prjtg_id;\n" .
            "update prj_grp set prj_grp_open=false,written=true where prjtg_id=$prjtg_id;\n" .
            "update prj_tutor set prj_tutor_open=false where prjtg_id=$prjtg_id ;\n" .
            "insert into assessment_commit (snummer,commit_time,prjtg_id) select snummer,now(),prjtg_id\n" .
            "  from prj_grp natural join prj_tutor where prjtg_id=$prjtg_id;\n" .
            "commit";

    $resultSet = $dbConn->executeCompound($sql);
    if ($resultSet === false) {
        $msg = $dbConn->ErrorMsg();
        $dbConn->Execute("rollback");
        die("<br>Cannot update assessmenttable with \"" . $sql . '", cause ' . $dbConn->ErrorMsg() . "<br>");
    }
}

if (isSet($_POST['commit']) && isSet($_POST['tutor_grade']) && $isGroupTutor) {
    //$sql = "begin work;\n";
    $sql = "";
    $trans_id = $dbConn->transactionStart('tutor grade commit');
    for ($i = 0; $i < count($_POST['gnummer']); $i++) {
        $gnummer = validate($_POST['gnummer'][$i], 'integer', 1);
        $grade = $_POST['tutor_grade'][$i];
        $grade = preg_replace('/,/', '.', $grade);
        $sql .="delete from milestone_grade where prjm_id=$prjm_id and snummer=$gnummer;\n"
                . "insert into milestone_grade (snummer,prjm_id,grade,multiplier,trans_id) \n"
                . " select $gnummer,$prjm_id,$grade,multiplier[array_upper(multiplier,1)] as multiplier,$trans_id \n"
                . " from assessment_grade_set($prjtg_id,$productgrade) where snummer=$gnummer;\n";
    }
    $sql .= "update prj_tutor set tutor_grade=$productgrade where prjtg_id=$prjtg_id;\n"
            . "commit;";
    //$dbConn->log( $sql );
    $resultSet = $dbConn->executeCompound($sql);
    if ($resultSet === false) {
        $msg = $dbConn->ErrorMsg();
        $dbConn->Execute("rollback");
        die("<br/>Cannot update grade table with \"<pre>" . $sql . '", cause ' . $msg . "</pre><br/>\n");
    }
    $dbConn->transactionEnd();
}


if (isSet($_REQUEST['reopen']) && isSet($_REQUEST['open_prjtg_id'])) {
    $open_prjtg_id = $_REQUEST['open_prjtg_id'];
    $sql = "begin work;\n" .
            "update prj_grp set prj_grp_open=true,written=false where prjtg_id=$open_prjtg_id;\n" .
            "update prj_tutor set prj_tutor_open=true,assessment_complete=false where prjtg_id=$open_prjtg_id;\n" .
            "update prj_milestone set prj_milestone_open=true\n" .
            " where prjm_id in (select prjm_id from prj_tutor where prjtg_id=$open_prjtg_id);\n" .
            "commit";
//    $dbConn->log($sql);
    $resultSet = $dbConn->executeCompound($sql);
    if ($resultSet === false) {
        die("<br>Cannot update prj_grp table with \"" . $sql . '", cause ' . $dbConn->ErrorMsg() . "<br>");
    }
}

$criteria = getCriteria($prjm_id);
array_push($criteria, array('nl_short' => 'Overall',
    'de_short' => 'Overall',
    'en_short' => 'Overall',
    'nl' => 'Eindcijfer',
    'de' => 'Endnote',
    'en' => 'Final Grade'));
$overall_criterium = 99; //count($criteria);
/* display peerassessment form rows.
 * first get the criteria per row
 */
$sql = " select afko,rtrim(alias) as alias from all_prj_tutor where prj_id=$prj_id \n" .
        "and milestone=$milestone and grp_num ='$grp_num' ";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    die("<br>Cannot get project name with \"" . $sql . '", cause ' . $dbConn->ErrorMsg() . "<br>");
}
if (!$resultSet->EOF) {
    extract($resultSet->fields);
}
$filename = 'groupresult' . trim($afko) . (($grp_num == '*') ? '_' : $grp_num) . 'm' . $milestone . '_consolidated_' . $fdate;

$title = "Group result project $afko $year, milestone $milestone group $grp_num ";
if (isSet($alias)) {
    $title .= " ($alias)";
}
$title .= "; spreadsheet file created on $fdate";
$sqlt = "select s.snummer as contestant, "
        . "roepnaam||' '||coalesce(voorvoegsel,'')||' '||achternaam as naam, "
        . "ags.grade as peerg,\n"
        . "ags.multiplier[array_upper(ags.multiplier,1)] as grp_multiplier, "
        . "coalesce(round(mg.grade,2),round({$productgrade}*ags.multiplier[array_upper(ags.multiplier,1)],2)) as tutorg \n"
        . " from student s join assessment_grade_set($prjtg_id,$productgrade) ags using (snummer)"
        . " join all_prj_tutor using(prjtg_id) "
        . " left join milestone_grade mg using(prjm_id,snummer) order by achternaam,roepnaam,snummer";

$spreadSheetWriter = new SpreadSheetWriter($dbConn, $sqlt);

$spreadSheetWriter->setFilename($filename)
        ->setLinkUrl($server_url . $PHP_SELF . '?class_id=' . $class_id)
        ->setTitle($title)
        ->setAutoZebra(true)
        ->setWeights(array(1, 2, 3, 4))
        ->setFirstWeightsColumn(2)
        ->setWeightSumsColumn(9)
        ->setRowParser(
                new RowWithArraysPreHeadersParser(array('contestant', 'name',
            criteriaShortAsArray($criteria, 'en'), 'multiplier', 'tutorg')));

$spreadSheetWriter->processRequest();


$scripts = '<script type="text/javascript" src="js/jquery.js"></script>          
    <script src="js/jquery.tablesorter.js"></script>            
    <script type="text/javascript">                                         
      $(document).ready(function() {
      // do stuff when DOM is ready 
           $("#groupresult").tablesorter({ }); 
      });

    </script>
    <link rel=\'stylesheet\' type=\'text/css\' href=\'' . SITEROOT . '/style/tablesorterstyle.css\'/>
';
pagehead2('groupresult', $scripts);
$prj_id_selector = $prjSel->getSelector();
$prj_data = $prjSel->getSelectionDetails();
extract($prjSel->getSelectedData());
$grpList = '<select name="prjtg_id" onchange="submit()">' . "\n";
$grpList .= getOptionList($dbConn, "select distinct grp_num||' ('||tutor||')'||coalesce(': '||alias ,'')" .
        "||' (#'||prjtg_id||')' as name,\n" .
        " prjtg_id as value,case when prj_tutor_open=true then 'background:#fee' else 'font-weight:bold;background:#efe' end as style,grp_num\n" .
        " from all_prj_tutor \n" .
        " where prjm_id=$prjm_id order by grp_num ", $prjtg_id);
$grpList .= "\n</select>\n";

$page_opening = "Group results for $afko \"$description\" $year prj_id $prj_id mil $milestone ($prjm_id)";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$prj_widget = $prjSel->getWidget();
$spreadSheetWidget = $spreadSheetWriter->getWidget();

$remarkList = remarkList($dbConn, $prjtg_id);
?>
<?= $nav->show() ?>
<div id='navmain' style='padding:1em;'>
    <?= $prj_widget ?>
    <?php
    if ($isTutorOwner) {
        ?>    <fieldset class='noprint'><legend>Completeness and open setting of student groups</legend>
        <?=
        groupOpenerBarChart2($dbConn, $prjm_id, $isTutorOwner);
        ?>
        </fieldset>
        <?php
    }
    ?>
    <fieldset class='noprint' ><legend>Project and group selection</legend>

        <form method="post" name="project" action="<?= $PHP_SELF; ?>">
            <p>In this form below you can enter a <i>group grade</i>, (default a 7 at the moment <b><?= $productgrade ?></b>)
                that will be used to compute a proposal for the individual grade.</p>
            <table class='layout' width='100%' summary='layout' style='border-collapse: collapse;' border='1'>
                <tr><th class='layout'>Group</th>
                    <th class='layout' colspan='3'>Submit operation</th><th class='layout'>csv</th></tr>
                <tr><th class='layout'><?= $grpList ?></th>

                    <th class='layout'><input type='submit' name='bsubmit' value='Get Data'/></th>

                    <?php if (true || $db_name != 'peer') {
                        ?><th class='layout'>
                            <input type='submit' name='clear' value='clear all' style='color:#c00;' 
                                   onclick='return confirm("Are you sure you want to clear all grade data?")'/>
                        </th>
                        <th>
                            <input type='submit' name='random' value='Random values' style='color:#c00;' 
                                   onclick='return confirm("Are you sure you randomize all grade data?")'/>
                        </th>
                        <?php
                    }
                    ?>
                    <th class='layout'> <?= $spreadSheetWidget ?></th></tr>
            </table>
        </form>
        <form name='reopenform' method='get' action=<?= $PHP_SELF ?>>
            <input type='hidden' name='open_prjtg_id' value='<?= $prjtg_id ?>'/>
            <p>You can also reopen the assessment for the group: To let a group 
                correct their values, re-open the assessment for the group by clicking this button.
                <input type='submit' name='reopen' value='Re open'/></p>
        </form>
    </fieldset><!-- end noprint fieldset-->
    <?php
    $lang = 'nl';
    $rainbow = new RainBow(STARTCOLOR, COLORINCREMENT_RED, COLORINCREMENT_GREEN, COLORINCREMENT_BLUE);
    $pg = new GroupPhoto($dbConn, $prjtg_id);
    $pg->setPictSize(64, 96)->setMaxCol(15);

    echo $pg->getGroupPhotos();

    groupResultTable($dbConn, $prjtg_id, $overall_criterium, $productgrade, true, $criteria, $lang, $rainbow, true, $isGroupTutor, true);
    //groupResultTable( $dbConn, $prjtg_id, $overall_criterium, $productgrade, true, $criteria, $lang, $rainbow, true, true, true );
    ?>
    <p>Note that the students in 
        <span style='background:#F00;color:#00F;text-decoration:line-through;'>&nbsp;red&nbsp;</span> 
        have not yet committed any peer grades.</p>
    <?= $remarkList ?>
    <table class='navleft selected' width='100%' summary='layout'>
        <tr><td width="50%" valign="top" style="border-width:0pt;">
                <h3>Criteria legends</h3>
                <table class='tabledata' summary='criteria' style='font-size:8pt;'><?php
                    $rainbow->restart();
                    criteriaList($criteria, $lang, $rainbow);
                    ?>
                </table>
            </td>
            <td width="50%" valign="top" style="border-width:0pt;">
                <h3>Explanation</h3>
                <p>In the table above you find the average score per criterion/column. Note that the last (rightmost) 
                    overall column is influenced by the group grade you enter in the top right field. 
                    The average is by definition equal to the group grade (budget) you entered and not the average of 
                    the grades in the other columns</p>
                <p>The default for the group grade is a 7.</p>
                <p>Max grade is 10, automatically applied on the computed grades, even if the group grade * multiplier would compute differently.</p>
                <p>Any tutor can calculate the tutor appraisal, but only the group's tutor can commit this appraisal to peerweb.
                    The individual appraisal values can be modified by the group tutor. Once the values are committed, they are 
                    available for collection on the <a href='http://localhost/peertest/allgroupresults.php'>All group results page</a> 
                    for instance in spreadsheet form for further processing.
                </p>
            </td>
        </tr>
    </table>
</div>
<!-- $Id: groupresult.php 1829 2014-12-28 19:40:37Z hom $-->
</body>
</html>
<?php ?>
