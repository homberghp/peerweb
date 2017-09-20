<?php
include_once('./peerlib/peerutils.php');
include_once('tutorhelper.php');
require_once('./peerlib/validators.php');
include_once('navigation2.php');
define('MAXROW', '3');
define('MAXCOL', '5');
$class_id = 1;
$tutor = $tutor_code;
extract($_SESSION);
$year = 2008;
# get actual course_year
$sql = "select value as year from peer_settings where key='course_year'";
$resultSet = $dbConn->Execute($sql);
if (!$resultSet->EOF)
    extract($resultSet->fields);

if (isSet($_REQUEST['oldclass_id'])) {
    $_SESSION['class_id'] = $class_id = validate($_REQUEST['oldclass_id'], 'integer', 1);
}
pagehead('my class');

$sql = "select class_id from current_student_class where snummer=$snummer\n";
$resultSet = $dbConn->Execute($sql);
if (!$resultSet->EOF)
    extract($resultSet->fields);

$sql = "select * from student_class where class_id=$class_id";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    die("<br>Cannot get class data with " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>");
}
if (!$resultSet->EOF)
    extract($resultSet->fields);
$tablehead = "<h2>Class photos for class $sclass: $year-" . ($year + 1) . "</h2>\n";
$page_opening = "Class photos for class  $sclass $year-" . ($year + 1);
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$sql = "SELECT snummer as number,roepnaam||' '||coalesce(tussenvoegsel||' ','')||achternaam as name,\n" .
        "achternaam,roepnaam,tussenvoegsel,cl.sclass,cohort,opl as opl_code,pcn,lang,sex,gebdat," .
        "straat,huisnr,pcode,plaats,nationaliteit," .
        "hoofdgrp, snummer as participant, course_description as opleiding,gebdat as birthday" .
        " from student st " .
        //		  "join current_student_class sc using (snummer) \n".
        "join student_class cl using(class_id)\n" .
        "left join fontys_course fc on(st.opl=fc.course)\n" .
        "where class_id='$class_id' " .
        "order by achternaam,roepnaam";

$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    die("<br>Cannot get student data with \"" . $sql . '", cause ' . $dbConn->ErrorMsg() . "<br>");
}
?>
<?= tutorHelper($dbConn, $isTutor); ?>
<div id='navmain' style='padding:0 0 0 0;'>
    <?= $nav->show() ?>

    <div class='nav'>
    </div>
    <?php
    $colcount = 0;
    $rowcount = 0;
    while (!$resultSet->EOF) {
        if ($rowcount == 0 && $colcount == 0)
            echo "$tablehead\n<table style='page-break-after:always'>\n";
        if ($colcount == 0)
            echo "<tr>\n";
        extract($resultSet->fields);

        if (file_exists('fotos/' . $number . '.jpg')) {
            $photo = 'fotos/' . $number . '.jpg';
        } else {
            $photo = 'fotos/anonymous.jpg';
        }
        $leftpix = 0; //100+$colcount*140;
        $toppix = 0; //$rowcount*160;
        echo "<th class='classmate' valign='top' halign='center'>"
        . "<img src='$photo' alt='fotos/$number.jpg' align='top' border='0'"
        . " style='width:128px;height:auto;box-shadow: 5px 5px 5px #004;border-radius:16px;'/>"
        . "\n<table width='100%'>\n"
        . "\t<tr><th>$name</th></tr>\n\t<tr><th>$number</th></tr>\n"
        . "\t</table></th>\n";
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
    if ($colcount != 0)
        echo "</tr>\n";
    if ($rowcount != 0)
        echo "</table>\n";
    ?>
</div>
<?php echo "<!-- db_name=" . $db_name . "-->\n" ?>
<!-- $Id: myclass.php 1723 2014-01-03 08:34:59Z hom $-->
</body>
</html>
<?php ?>
