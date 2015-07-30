<?php

require_once('peerutils.inc');

/**
 * guess one item, having other info
 */
function processQuery($dbConn, $sql) {
    $rs = $dbConn->Execute($sql);
    if ($rs === false) {
        echo ('query failed with <pre>' . $sql . ' cause ' . $dbConn->ErrorMsg());
        stacktrace(1);
    }
    return $rs;
}

/**
 * guess one item, having other info
 */
function guessStudent($dbConn) {
    $sql = "select snummer from student where snummer not in (select userid from tutor) limit 1";
    $resultSet = processQuery($dbConn, $sql);
    return $resultSet->fields['snummer'];
}

function guessStudentForTutor($dbConn, $peer_id) {
    $sql = "select snummer from student where snummer in\n" .
            " (select distinct snummer from prj_grp join prj_tutor using(prjtg_id)\n" .
            " where tutor_id=$peer_id) limit 1";
    $resultSet = processQuery($dbConn, $sql);
    if (!$resultSet->EOF)
        return $resultSet->fields['snummer'];
    else
        return 879417;
}

function guessClassFromStudent($dbConn, $snummer) {
    $sql = "select class_id from student \n" .
            "join student_class using (class_id)\n" .
            "where snummer=$snummer order by sclass limit 1 ";
    $resultSet = processQuery($dbConn, $sql);
    return $resultSet->fields['class_id'];
}

function guessStudentFromClass($dbConn, $class_id) {
    $sql = "select snummer,achternaam from student \n" .
            "where class_id='$class_id' order by achternaam limit 1 ";
    $resultSet = processQuery($dbConn, $sql);
    return $resultSet->fields['snummer'];
}

//function guessStudentFromProject($dbConn, $prjm_id ) {
//    $sql = "select snummer,achternaam from student join prj_grp using(snummer)\n".
//            "join prj_tutor using(prjtg_id) join prj_milestone using(prjm_id)\n".
//	"where prj_id=$prj_id and milestone=$milestone order by prj_id desc,milestone desc, achternaam limit 1 ";
//    $resultSet = processQuery($dbConn,$sql);
//    return $resultSet->fields['snummer'];
//}

/**
 * Get project data fpr student.
 * @param type $dbConn
 * @param type $snummer
 * @param type $prjm_id
 * @return string prj_id, milestone and prjtg_id
 */
function guessProjectFromStudent($dbConn, $snummer, $prjm_id) {
    if (!isSet($prjm_id) || $prjm_id === false || $prjm_id == '') {
        $prjm_id = $milestone = 1;
    }
    $sql = "select prj_id||':'||milestone  as prj_id_milestone from student join prj_grp using(snummer)\n" .
            "join prj_tutor using(prjtg_id) join prj_milestone using(prjm_id)\n" .
            "where snummer=$snummer and prjm_id=$prjm_id \n" .
            " order by prj_id desc, milestone desc limit 1 ";
    $resultSet = processQuery($dbConn, $sql);
    if (!$resultSet->EOF) {
        return $resultSet->fields['prj_id_milestone'];
    } else {
        $sql = "select prj_id||':'||milestone||':'||prjtg_id  as prj_id_milestone from student join prj_grp using(snummer)\n" .
                "join prj_tutor using(prjtg_id) join prj_milestone using(prjm_id)\n" .
                "where snummer=$snummer order by prj_id desc, milestone desc limit 1 ";
        $resultSet = processQuery($dbConn, $sql);
        if (!$resultSet->EOF) {
            return $resultSet->fields['prj_id_milestone'];
        }
    }
    return '1:1';
}

function guessProject($dbConn) {
    $sql = "select prj_id||':'||milestone as prj_id_milestone from \n" .
            "prj_tutor join prj_milestone using(prjtg_id)" .
            "order by prj_id desc, milestone desc limit 1 ";
    $resultSet = processQuery($dbConn, $sql);
    return $resultSet->fields['prj_id_milestone'];
}

function guessClass($dbConn) {
    $sql = "select class_id from student_class\n" .
            "order by sort1,sort2, sclass limit 1 ";
    $resultSet = processQuery($dbConn, $sql);
    return $resultSet->fields['class_id'];
}

function isInProjectMilestone($dbConn, $prj_id, $milestone, $snummer) {
    $sql = "select count(*) as counter from student join prj_grp using(snummer)\n" .
            "join prj_tutor using(prjtg_id) join prj_milestone using(prjm_id)" .
            " where prj_id=$prj_id and milestone=$milestone and snummer=$snummer";
    $resultSet = processQuery($dbConn, $sql);
    return ( $resultSet->fields['counter'] != 0 );
}

function isInClass($dbConn, $class_id, $snummer) {
    $sql = "select count(*) as counter from student where class_id='$class_id' and snummer=$snummer ";
    $resultSet = processQuery($dbConn, $sql);
    return ( $resultSet->fields['counter'] != 0 );
}

function guessPrjTgForStudent($dbConn, $snummer, $prjm_id) {
    $sql = "select prjtg_id from prj_grp join prj_tutor using(prjtg_id)\n"
            . " where snummer=$snummer and prjm_id=$prjm_id";
    $resultSet = processQuery($dbConn, $sql);
    if (isSet($resultSet->fields['prjtg_id'])) {
        return $resultSet->fields['prjtg_id'];
    } else
        return 1;
}

?>