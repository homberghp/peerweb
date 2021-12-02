<?php

/**
 * Multi pane class selector
 * @param type $dbConn connection
 * @param type $sql select statement
 * @param type $submit_button button 
 * @param type $class_ids the submitted current set ogf class ids, selected
 * @return type rendered html string.
 */
 function classMultiSelector( PDO $dbConn, string $sql, string $submit_button, array $class_ids = array()): string {

    $pstm = $dbConn->query($sql);

    if ($pstm === false) {
    die("<br>Cannot get groups with {$sql} cause {$dbConn->errorInfo()[2]}<br>");
    }
//ob_start();
    $opl_afko = '';
    $colcount = 0;
    $curriculum = '';
    $complete = false;
    $result = '';
    $row = '';
    $cluster_name = '';
    if (($row=$pstm->fetch()) !== false) {
        $opl_afko = $row['opl_afko'];
        $sort1 = $row['sort1'];
        $sort2 = $row['sort2'];
        $cluster_name = $row['cluster_name'];
    }
    $divcount = 0;
    $cluster_name = '';
    $tablist = ""."<!-- classMultiSelector Start -->\n<div id='tabs'>\n<ul>\n";
    while (($row=$pstm->fetch()) !== false) {
        $opl_afko = $row['opl_afko'];
        if ($cluster_name != $row['cluster_name']) {
            // close cluster
            // append last row
            if ($curriculum != '') {
                $colsleft = (6 - $colcount);
                while ($colsleft > 0) {
                    $curriculum .= "\n\t\t\t<td >&nbsp;</td>";
                    $colsleft--;
                }
                $curriculum .= "\n\t\t<tr>\n"
                        . "\t\t</tr></table>\n" .
                        "<br/><b>Legend:class name [class size]</b>\n</div><!-- end tabs-${divcount} -->\n";
            }
            $divcount++;
            $faculty_short = $row['faculty_short'];
            $cluster_name = $row['cluster_name'];
            $curriculum .= "<div id='tabs-${divcount}'>\n<table border='1' style='border-collapse:collapse;'>\n"
                    . "\t<thead><tr>"
                    . "<th colspan='6' style='align:center'>"
                    . "<span style=''>Faculty/cluster/Curriculum</span> $faculty_short/$cluster_name </th>"
                    . "</tr></thead>\n\t\t<tr>\n";
            $colcount = 0;
            $tablist .= "\t\t<li><a href='#tabs-${divcount}'>$faculty_short/$cluster_name</a></li>\n";
        }
        extract($row);

        $checked = '';
        if (in_array($class_id, $class_ids)) {
            $checked = 'checked';
        }
        $curriculum .= "\n\t\t\t<td >"
                . "<input type='checkbox' name='class_ids[]' value='$class_id' $checked />&nbsp;"
                . "<a href='classphoto.php?class_id=$class_id' target='_blank'>$sclass&nbsp;</a>[$student_count]</td>";
        $colcount++;
        if ($colcount > 5) {
            $curriculum .= "\n\t\t</tr>\n";
            $colcount = 0;
        }
    }
    if ($colcount > 0) {
        $colsleft = (6 - $colcount);
        while ($colsleft > 0) {
            $curriculum .= "\t\t\t<td >&nbsp;</td>\n";
            $colsleft--;
        }
        $curriculum .= "\n\t\t</tr>\n";
    }
    $curriculum .= "</table>\n" .
            "<br/><b>Legend:class name [class size]</b>\n" .
            "</div><!-- end tabs-{$divcount} -->\n\n</div><!-- end tabs div -->\n";
    $tablist .= "\t</ul>\n";
//$result .="</table>\n</div>\n";
    return $tablist.$curriculum
            ."<table border='0' style='border-collapse:collapse;><thead>
    <tr>
<th class='theadleft'>&nbsp;</th>
    <th colspan='1' class=''><input type='reset' name='reset' value='Reset'/></th>
    <th colspan='1' class=''>$submit_button</th>
  </tr>
  </thead>\n<table>\n</div>\n"
            . "<!-- classMultiSelectorEnd -->";
}// eo function
