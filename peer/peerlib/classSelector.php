<?php

require_once 'ClassSelectorClass.php';

/**
 * Get a class by cluster, faculty, name and class_id
 * @param $dboon db connector
 * @param $selector_name, name and id of html select 
 * @param $current_selection the selected class
 * @deprecated since version 1568, use ClassSelectorClass instead.
 */
function classSelector($dbConn, $selector_name, $current_selection, $autoSubmit = false) {
    $csc = new ClassSelectorClass($dbConn, $current_selection);
    return $csc->setSelectorName($selector_name)->setAutoSubmit($autoSubmit)->getSelector();

}

/**
 * Get a class by cluster, faculty, name and class_id
 * @param $dboon db connector
 * @param $selector_name, name and id of html select 
 * @parem $current_selection the selected class
 */
function hoofdgrpSelector($dbConn, $selector_name, $current_selection) {
    global $peer_id;
    $query = "select  distinct trim(hoofdgrp) as value, trim(hoofdgrp) as name, substr(hoofdgrp,1,2) as namegrp "
//            . " trim(faculty_short)||'.'||trim(hoofdgrp)||' count '||hs.grp_size as name,\n"
//            . "  trim(faculty_short)||'-'||trim(course_short) as namegrp, \n"
//            . " case when (faculty_id,course)=(select faculty_id,opl from student where snummer={$peer_id}) then 0\n"
//            . "  when (faculty_id)=(select faculty_id from student where snummer={$peer_id}) then 1\n"
//            . " else 2 end as my_faculty \n"
            . " from hoofdgrp_s "
            //."h natural join hoofdgrp_size hs natural join faculty\n"
            . " order by name";
    $result = "<select name='{$selector_name}' id='{$selector_name}' >\n" . getOptionListGrouped($dbConn, $query, $current_selection)
            . "</select>\n";

    return $result;
}

?>