<?php
include_once('./peerlib/peerutils.inc');
requireCap(CAP_TUTOR);
require_once('./peerlib/validators.inc');
include_once('navigation2.inc');
require_once 'ClassSelectorClass.php';

define('MAXROW', '4');
define('MAXCOL', '5');
$class_id = '1';
$tutor = $tutor_code;
extract($_SESSION);
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

pagehead2('class photos', $style);

$classSelectorClass = new ClassSelectorClass($dbConn,$class_id);
$oldClassSelector = $classSelectorClass->setAutoSubmit(true)->addConstraint('sort1 < 10 and student_count <>0')->getSelector();


$sql = "select * from hoofdgrp where hoofdgrp='$class_id'";
$sql = "select * from student_class natural join faculty where class_id='$class_id'";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    die("<br>Cannot get class data with " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>");
}
if (!$resultSet->EOF)
    extract($resultSet->fields);
$tablehead = "<h2><a href='photolist.php?class_id=$class_id'>"
        . "Class photos for class $faculty_short.$sclass $class_id: $year-" . ($year + 1) . "<img src='images/pdf_icon.png' border='0'/></a></h2>\n";
$page_opening = "Class photos for class  $faculty_short.$sclass $class_id $year-" . ($year + 1);
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$sql = "SELECT distinct st.snummer as number," .
        "st.roepnaam||' '||coalesce(regexp_replace(st.voorvoegsel,'''','&rsquo;')||' ','')||st.achternaam as name,\n" .
        "st.achternaam,st.roepnaam,st.voorvoegsel,cohort,cohort,st.opl as opl_code,pcn,lang,sex,gebdat,\n" .
        "straat,huisnr,pcode,plaats,nationaliteit,\n" .
        "td.roepnaam||coalesce(' '||td.voorvoegsel,'')||' '||td.achternaam as slb,coalesce(td.tutor,'---') as slb_ab,\n" .
        "st.hoofdgrp as sclass, st.snummer as participant, course_description as opleiding,gebdat as birthday,\n" .
        "'fotos/'||image as image\n" .
        " from student_email st \n" .
        "left join fontys_course fc on(st.opl=fc.course)\n" .
        "left join tutor_join_student td on (st.slb=td.snummer)\n" .
        "where class_id='$class_id'" .
        "order by achternaam,roepnaam";

//$dbConn->log($sql);
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    die("<br>Cannot get student data with \"" . $sql . '", cause ' . $dbConn->ErrorMsg() . "<br>");
}
?>
<?= $nav->show() ?>
<div id='navmain' style='padding:1em;'>
    <div class='nav'>
        <form method="get" name="class" action="<?= $PHP_SELF; ?>">
<?= $oldClassSelector ?>
            <input type='submit' name='b' value ='Get fotos'/>
        </form>
    </div>
    <?php
    $colcount = 0;
    $rowcount = 0;
    $browserIE = strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') ? true : false;

    while (!$resultSet->EOF) {
        if ($rowcount == 0 && $colcount == 0)
            echo "$tablehead\n<table><colgroup>\n"
            . "<col width='140px'/>\n"
            . "<col width='140px'/>\n"
            . "<col width='140px'/>\n"
            . "<col width='140px'/>\n"
            . "<col width='140px'/>\n"
            . "</colgroup>\n";
        if ($colcount == 0)
            echo "<tr>\n";
        extract($resultSet->fields);

        if (file_exists('fotos/' . $number . '.jpg')) {
            $photo = 'fotos/' . $number . '.jpg';
        } else {
            $photo = 'fotos/anonymous.jpg';
        }
    $leftpix=0;//100+$colcount*140;
    $toppix=0;//$rowcount*160;
      $tooltip="onmouseover=".'"balloon.showTooltip(event,\'<div><b>'."<span style=\'font-size:120%\'>$name</span><br/>snummer:$number<br/>pcn:&nbsp;$pcn<br/>$birthday<br/>".
	"$straat&nbsp;$huisnr<br/>$pcode&nbsp;$plaats<br/>$nationaliteit<br/>SLB: $slb<br/>class:$sclass<br/>Cohort:$cohort".'</b></div>\')"';
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
        $resultSet->moveNext();
    }
    if ($colcount != 0)
        echo "</tr>\n";
    if ($rowcount != 0)
        echo "</table>\n";
    ?>
</div>
<?php echo "<!-- db_name=" . $db_name . "-->\n" ?>
<!-- $Id: classphoto.php 1852 2015-07-23 13:25:29Z hom $-->
</body>
</html>