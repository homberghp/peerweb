<?php
requireCap(CAP_TUTOR);
include_once('peerutils.php');
require_once('validators.php');
include_once('navigation2.php');
require_once 'prjMilestoneSelector2.php';
$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
$grp_num = 1;
$year = 2007;
$prjtg_id = 1;
define('MAXROW', '3');
define('MAXCOL', '5');

extract($_SESSION);

$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
$prjSel->setSubmitOnChange(true);
extract($prjSel->getSelectedData());

if ($prjSel->isSelectionChange()) {
    // guess new prjtg_id
    $sql = "select prjtg_id from prj_tutor where prjm_id=$prjm_id order by grp_num limit 1";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet !== false && !$resultSet->EOF) {
        extract($resultSet->fields);
    }
}
$tutor = $tutor_code;
//echo $_REQUEST['prjtg_id']."<br/>\n";
if (isSet($_REQUEST['prjtg_id'])) {
    $prjtg_id = validate($_REQUEST['prjtg_id'], 'integer', $_SESSION['prjtg_id']);
}

$_SESSION['prj_id'] = $prj_id;
$_SESSION['milestone'] = $milestone;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['prjtg_id'] = $prjtg_id;

$scripts = file_get_contents('js/balloonscript.html');

pagehead2('groupphotos', $scripts);
$prjSel->setSubmitOnChange(false);
$prj_id_selector = $prjSel->getSelector();

$grpList = '<select name="prjtg_id" onChange="submit()">' . "\n\t";
$grpList .= getOptionList($dbConn, "select distinct pt.grp_num||' (tutor: '||tutor||')'"
        . "||coalesce(', group name: '||grp_name ,'') as name,\n"
        . "pt.prjtg_id as value,\n"
        . "pt.grp_num "
        . " from prj_tutor pt \n"
        . " join tutor t on(userid=tutor_id)\n"
        . " left join grp_alias using( prjtg_id )\n"
        . " where pt.prjm_id=$prjm_id order by pt.grp_num", $prjtg_id, array());
$grpList .= "</select>\n";
$sql = "SELECT * from all_prj_tutor";
if (isSet($prjtg_id)) {
    $sql .= "\n where prjtg_id=$prjtg_id ";
}
$sql .= "\nlimit 1 \n";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    die("<br>Cannot get projectdata with \"" . $sql . '", cause ' . $dbConn->ErrorMsg() . "<br>");
}
if (!$resultSet->EOF)
    extract($resultSet->fields);
if (!isSet($alias))
    $alias = '';
$next_year = $year + 1;
$page_opening = "Group photos for project $afko: $description $year-$next_year" .
        "<span style='font-size:6pt;'> prj_id=$prj_id  milestone $milestone (prjm_id=$prjm_id) group $grp_num (prjtg_id=$prjtg_id)</span>";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$sql = "select distinct st.snummer as number,st.roepnaam||' '||coalesce(st.tussenvoegsel||' ','')||st.achternaam as name,\n" .
        " gebdat as birthday, st.roepnaam,st.achternaam,st.tussenvoegsel,cohort,pcn,role,straat,huisnr,plaats,pcode,nationaliteit,\n" .
        " sclass, 'fotos/'||image as image,\n" .
        "td.roepnaam||coalesce(' '||td.tussenvoegsel,'')||' '||td.achternaam as slb\n" .
        " from prj_grp pg join student_email st using(snummer) \n" .
        " join all_prj_tutor pt using(prjtg_id)\n" .
        " join student_class using(class_id)\n" .
        " left join student_role sr using(prjm_id,snummer) left join project_roles pr using(prj_id,rolenum)\n" .
        "left join tutor_join_student td on (st.slb=td.snummer)\n" .
        " where pt.prjtg_id=$prjtg_id order by achternaam,roepnaam";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    die("<br>Cannot get student data with <pre>\"" . $sql . '", cause <pre>' . $dbConn->ErrorMsg() . "\n</pre><br>");
}
?>
<?= $nav->show() ?>
<div id='navmain' style='padding:1em;'>
    <div class='nav'>
        <form method="get" name="project" action="<?= $PHP_SELF; ?>">
            <?= $prj_id_selector ?><input type="submit" value='Get Project'/>
            <input type='hidden' name='grp_num' value='1'/>
        </form>
        <form method="get" name="group" action="<?= $PHP_SELF; ?>">
            <?= $grpList ?><input type='submit' name='submit' value='Get Group'/>
            <input type='hidden' name='prj_id_milestone' value='<?= $prj_id ?>:<?= $milestone ?>'/>
        </form>
    </div>
    <?php
    $colcount = 0;
    $rowcount = 0;
    $tablehead = "<h2>Group photos for project $afko: $description $year-$next_year</h2>\n" .
            "<p>prj_id=$prj_id  milestone $milestone (prjm_id=$prjm_id)</p>\n" .
            "<h3><a href='groupphotolist.php?prjtg_id=$prjtg_id'> group $grp_num (prjtg_id=$prjtg_id) $alias <img src='images/pdf_icon.png' border='0'/></a></h3>";
    while (!$resultSet->EOF) {
        if ($rowcount == 0 && $colcount == 0)
            echo "$tablehead\n<table style='page-break-after:always'>\n";
        if ($colcount == 0)
            echo "<tr>\n";
        extract($resultSet->fields);
        echo "<td class='classmate' valign='top'>"
        . "<a href='student_admin.php?snummer=$number' target='mainframe' "
        . "onmouseover="
        . '"balloon.showTooltip(event,\'<div><b>'
        . "<span style=\'font-size:120% \'>$roepnaam $tussenvoegsel $achternaam</span><br/>"
        . "snummer:$number<br/>pcn:&nbsp;$pcn<br/>$birthday<br/>"
//        . "$straat&nbsp;$huisnr<br/>$pcode&nbsp;$plaats<br/>"
//        . "$nationaliteit<br/>
          .      "SLB: {$slb}<br/>"
        . "class:$sclass<br/>"
        . "Cohort:$cohort" . '</b></div>\')"'
        . ">\n"
        . "<img src='$image' alt='$image' align='top' border='0' style='width:128px;"
        . " height=auto;box-shadow: 5px 5px 5px #004;border-radius:16px'/></a>\n"
        . "<div style='margin-top:10px;text-align:center;font-size:120%;font-weight:bold'>\n"
        . "\t$name<br/>$number\n"
        . "\t</div>"
        . "</td>\n";

        $colcount++;
        if ($colcount >= MAXCOL) {
            echo "</tr>\n";
            $colcount = 0;
            $rowcount++;
            if ($rowcount >= MAXROW) {
                echo "</table>\n";
                $rowcount = 0;
            }
        }
        $resultSet->moveNext();
    }
    echo "</tr>\n";
    echo "</table>\n";
    echo "<h3><a href='groupphotolist2.php?prjtg_id=$prjtg_id'> BIG group $grp_num (prjtg_id=$prjtg_id) $alias <img src='images/pdf_icon.png' border='0'/></a></h3>"
    ?>
</div>
<?php echo "<!-- db_name=" . $db_name . "-->\n" ?>
<!-- $Id: groupphoto.php 1825 2014-12-27 14:57:05Z hom $-->
</body>
</html>
<?php ?>
