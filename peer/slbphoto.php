<?php
requireCap(CAP_TUTOR);
require_once('validators.php');
require_once('navigation2.php');
require_once 'ClassSelectorClass.php';

define('MAXROW', '4');
define('MAXCOL', '5');
$class_id = '1';
$tutor = $tutor_code;
$slb = $peer_id;
if (isSet($_REQUEST['slb'])) {
    $_SESSION['slb'] = $hoofdgrp = validate($_REQUEST['slb'], 'integer', '0');
}

extract($_SESSION);
$slb_info = "";
$slb_sql = "select roepnaam||coalesce(' '||tussenvoegsel||' ',' ')||achternaam||' ('||tutor||':'||userid||')' as slb_info\n"
        . " from tutor join student_email  s on (s.snummer=tutor.userid) where userid=$slb";
$resultSet = $dbConn->Execute($slb_sql);
//echo "<pre>{$slb_sql}</pre>";
if (!$resultSet->EOF) {
    extract($resultSet->fields);
}
$year = date('Y');
# get actual course_year
$sql = "select value as year from peer_settings where key='course_year'";
$resultSet = $dbConn->Execute($sql);
if (!$resultSet->EOF)
    extract($resultSet->fields);

if (isSet($_REQUEST['class_id'])) {
    $_SESSION['class_id'] = $class_id = $_REQUEST['class_id'];
}

$style = file_get_contents('js/balloonscript.html');

pagehead2('pupil photos', $style);

$classSelectorClass = new ClassSelectorClass($dbConn, $class_id);
$oldClassSelector = $classSelectorClass->setAutoSubmit(true)->addConstraint('sort1 < 10 and student_count <>0')->getSelector();


$page_opening = "Pupil  photos for {$slb_info}";
$nav = new Navigation($tutor_navtable, basename(__FILE__), $page_opening);
$nav->setInterestMap($tabInterestCount);
$sql = "SELECT distinct st.snummer as number," .
        "st.roepnaam||' '||coalesce(regexp_replace(st.tussenvoegsel,'''','&rsquo;')||' ','')||st.achternaam as name,\n" .
        "st.achternaam,st.roepnaam,st.tussenvoegsel,cohort,cohort,st.opl as opl_code,pcn,lang,sex,gebdat,\n" .
        "straat,huisnr,pcode,plaats,nationaliteit,\n" .
        "td.roepnaam||coalesce(' '||td.tussenvoegsel,'')||' '||td.achternaam as slb,coalesce(td.tutor,'---') as slb_ab,\n" .
        "st.hoofdgrp as sclass, st.snummer as participant, course_description as opleiding,gebdat as birthday,\n" .
        "'fotos/'||image as image\n" .
        " from student_email st \n" .
        "left join fontys_course fc on(st.opl=fc.course)\n" .
        "left join tutor_join_student td on (st.slb=td.snummer)\n" .
        "where slb=$slb and active " .
        "order by cohort desc,achternaam,roepnaam";

//$dbConn->log($sql);
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    die("<br>Cannot get student_email data with \"" . $sql . '", cause ' . $dbConn->ErrorMsg() . "<br>");
}
$sql_slb = "select mine,namegrp,name,userid as value from tutor_selector($peer_id) \n"
        . "order by mine,namegrp,name";
$slbList = "<select name='slb'>\n" . getOptionListGrouped($dbConn, $sql_slb, $slb) . "\n</select>";
?>
<?= $nav->show() ?>
<div id='navmain' style='padding:1em;'>
    <div class='nav'>
        <form method="get" name="slb" action="<?= $PHP_SELF; ?>">
            <?= $slbList ?>
            <input type='submit' name='b' value ='Get fotos'/>
        </form>
    </div>
    <?php
    $colcount = 0;
    $rowcount = 0;
    $browserIE = strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') ? true : false;
    $oldCohort = 0;
    while (!$resultSet->EOF) {
        extract($resultSet->fields);
        if ($oldCohort == 0) {
            $oldCohort = $cohort;
        }
        if ($cohort !== $oldCohort) {
            if ($colcount != 0)
                echo "</tr>\n";
            if ($rowcount != 0)
                echo "</table>\n";
            $rowcount = 0;
            $colcount = 0;
        }
        if ($rowcount == 0 && $colcount == 0) {
            $tablehead = "<h2>SLB Pupil photos for {$slb_info} $cohort</h2>";
            echo "{$tablehead}\n"
            . "<table><colgroup>\n"
            . "<col width='140px'/>\n"
            . "<col width='140px'/>\n"
            . "<col width='140px'/>\n"
            . "<col width='140px'/>\n"
            . "<col width='140px'/>\n"
            . "</colgroup>\n";
        }
        if ($colcount == 0) {
            echo "<tr>\n";
        }

        if (file_exists('fotos/' . $number . '.jpg')) {
            $photo = 'fotos/' . $number . '.jpg';
        } else {
            $photo = 'fotos/0.jpg';
        }
        $leftpix = 0; //100+$colcount*140;
        $toppix = 0; //$rowcount*160;
        $tooltip = "onmouseover=" . '"balloon.showTooltip(event,\'<div><b>' . "<span style=\'font-size:120%\'>$name</span><br/>snummer:$number<br/>pcn:&nbsp;$pcn<br/>$birthday<br/>" .
                "$straat&nbsp;$huisnr<br/>$pcode&nbsp;$plaats<br/>$nationaliteit<br/>SLB: $slb<br/>class:$sclass<br/>Cohort:$cohort" . '</b></div>\')"';
        echo "<th class='classmate' valign='top' halign='center'>" .
        "<a href='student_admin.php?snummer=$number' target='mainframe' " .
        $tooltip .
        ">\n\t<img class='pasfoto' src='$image' alt='$image' border='0' style='width:128px;height:auto; height=auto;box-shadow: 5px 5px 5px #004;border-radius:16px;'/></a>\n" .
        "\t\t<table width='100%'>\n" .
        "\t\t\t<tr><th>$name</th></tr>\n\t<tr><th>$number($slb_ab) </th></tr>\n" .
        "\t\t</table>" .
        "\n</th>\n";

        $colcount++;
        if ($colcount >= MAXCOL) {
            echo "</tr>\n";
            $colcount = 0;
            $rowcount++;
            if ($rowcount >= MAXROW) {
                echo "</table>\n<p style='page-break-before: always;'><!--(continued)--></p>\n";
                $rowcount = 0;
            }
        }

        $oldCohort = $cohort;
        $resultSet->moveNext();
    }
    if ($colcount != 0)
        echo "</tr>\n";
    if ($rowcount != 0)
        echo "</table>\n";
    ?>
</div>
<?php echo "<!-- db_name=" . $db_name . "-->\n" ?>
<!-- $Id: slbphoto.php 1852 2015-07-23 13:25:29Z hom $-->
</body>
</html>
