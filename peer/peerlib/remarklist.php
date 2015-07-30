<?php

function remarkList($dbConn, $prjtg_id) {
    $sql = "select * from assessment_remarks_view where prjtg_id=$prjtg_id order by cachternaam,croepnaam,jachternaam,jroepnaam \n";
    $resultSet2 = $dbConn->Execute($sql);
    $result = '';
    $oldContestant = 0;
    if ($resultSet2 === false) {
        $dbConn->logError("cannot get resultTable with $sql, reason: " . $dbConn->ErrorMsg());
        return 'no data' . $dbConn->ErrorMsg();
    } else if ($resultSet2->EOF) {
        $result .= "<h1>Sorry, no data yet</h1>\n";
        return $result;
    } else {
        $result .= "<h3>Student peer remarks</h3>\n"
                . "<div class='remarks'>\n\t<dl>\n";
        $myRow = '';
        while (!$resultSet2->EOF) {
            $contestant = $resultSet2->fields['contestant'];
            if ($oldContestant !== $contestant) {
                if ($myRow != '') {
                    $myRow .= "\t\t\t</dl>\n\t\t</dd>\n";
                }
                $result .= $myRow; // ship out
                $myRow = ""
                        . "\t\t<dt>{$resultSet2->fields['cname']} ({$resultSet2->fields['contestant']}) receives remarks:</dt>\n"
                        . "\t\t<dd>\n\t\t\t<dl>\n";
            }
            $oldContestant = $contestant;
            $myRow .= "\t\t\t\t<dt>{$resultSet2->fields['jname']} ({$resultSet2->fields['judge']}) notes:</dt><dd>\"{$resultSet2->fields['remark']}\"</dd>\n";
            $resultSet2->moveNext();
        }
        if ($myRow != '') {
            $myRow .= "\t\t\t</dl>\n\t\t</dd>\n\t</dl>\n</div>\n";
        }
        $result .= $myRow;
        return $result;
    }
    return $result;
}

?>
