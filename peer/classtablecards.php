<?php
requireCap(CAP_SYSTEM);
require_once 'validators.php';
require_once 'component.php';
include_once 'threelinetablecard.php';

if (isSet($_REQUEST['class_id'])) {
    $basename = 'classtablecards';
    $class_id = validate($_REQUEST['class_id'], 'integer', 1);
    $sql = "select trim(sclass) as sclass from student_class where class_id=$class_id";
    $resultSet = $dbConn->Execute($sql);
    if (!$resultSet->EOF) {
        $basename = $resultSet->fields['sclass'];
    }
    $basename = $basename . '-' . date('Ymd');
    $sql = "select roepnaam||coalesce(' '||tussenvoegsel||' ',' ')||achternaam as line1,\n" .
            "snummer as line2,\n" .
            "course_short||'.'||sclass as line3," .
            "snummer as barcode,\n" .
            "achternaam,roepnaam\n" .
            "from student s join student_class using(class_id) join fontys_course fc on(s.opl=fc.course)\n" .
            " where class_id=$class_id\n" .
            " order by achternaam,roepnaam";
    barcodedCard($dbConn, $basename, $sql);
} else if (isSet($_REQUEST['prjm_id'])) {
    $basename = 'classtablecards';
    $prjm_id = validate($_REQUEST['prjm_id'], 'integer', 1);
    $sql = "select rtrim(afko) as project from all_prj_tutor where prjm_id=$prjm_id";
    $resultSet = $dbConn->Execute($sql);
    if (!$resultSet->EOF) {
        $basename = $resultSet->fields['project'];
    }
    $basename = $basename . '-' . date('Ymd');
    $sql = "select roepnaam||coalesce(' '||tussenvoegsel||' ',' ')||achternaam as line1,\n" .
            "snummer as line2,\n" .
            "course_short||'.'||coalesce(apt.alias,'g'||apt.grp_num) as line3," .
            "snummer as barcode,\n" .
            "achternaam,roepnaam\n" .
            "from student s  join student_class using(class_id) join fontys_course fc on(s.opl=fc.course)\n" .
            " join prj_grp using (snummer) join all_prj_tutor apt using(prjtg_id)" .
            " where prjm_id=$prjm_id and snummer in (select snummer from prj_grp join all_prj_tutor using(prjtg_id) where prjm_id=$prjm_id)\n" .
            " order by grp_num,achternaam,roepnaam";
    //echo "$basename <br/><pre>\n$sql\n</pre>";
    barcodedCard($dbConn, $basename, $sql);
} else if (isSet($_REQUEST['rel']) && isSet($_REQUEST['hoofdgrp']) && preg_match('/^\w+$/', $_REQUEST['hoofdgrp']) && preg_match('/(prospects|student)/', $_REQUEST['rel'])
) {
    $rel = $_REQUEST['rel'];
    $hoofdgrp = $_REQUEST['hoofdgrp'];
    $basename = "hoofdgrpcard-{$hoofdgrp}-" . date('Ymd');
    $sql = "select roepnaam||coalesce(' '||tussenvoegsel||' ',' ')||achternaam as line1,\n"
            . "snummer as line2,"
            . "hoofdgrp as line3,"
            . "snummer as barcode \n"
            . "from {$rel} where hoofdgrp='{$hoofdgrp}'\n"
            . "order by achternaam,roepnaam";
    barcodedCard($dbConn, $basename, $sql);
} else {
    echo "no tablecards with class_id $classid\n";
}
?>