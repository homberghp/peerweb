<?php

function groupAssessmentTableHelper($dbConn, $sql, $inputs, $header, $criteria, $lang, $rainbow) {
    global $langmap;
    $result = '';
    $myRow = '';
    $resultSet2 = $dbConn->Execute($sql);
    $oldContestant = 0;
    if ($resultSet2 === false) {
        $dbConn->logError("cannot get resultTable with $sql, reason: " . $dbConn->ErrorMsg());
        return;
    } else if ($resultSet2->EOF) {
        $result .= "<h1>Sorry, no data yet</h1>\n";
        return $result;
    } else {
        if ($header) {
            $result .= "<tr>\n"
                    . "\t<th>" . $langmap['nummer'][$lang] . "</th>\n"
                    . "\t<th>" . $langmap['medestudent'][$lang] . "</t>\n"
                    . criteriaHead2String($criteria, $lang, $rainbow)
                    . "<th>Remark (only for tutor's eyes)</th></tr>\n";
        }
        $continuation = '';
        $color = $rainbow->restart();
        $remark = '';
        while (!$resultSet2->EOF) {
            $contestant = $resultSet2->fields['contestant'];
            if ($oldContestant !== $contestant) {
                if ($myRow != '') {
                    if ($inputs) {
                        $myRow .= "<td class='remark'><textarea rows='2' cols='70' name='remark[]'>$remark</textarea></td>\n</tr>\n";
                    } else {
                        $myRow .= "<td class='remark'>$remark</td>\n</tr>\n";
                    }
                    $myRow .="</tr>\n";
                }
                $result .= $myRow; // ship out
                $myRow = "<tr>\n"
                        . "\t<td>" . $resultSet2->fields['contestant'] . "</td>\n"
                        . "\t<td>" . $resultSet2->fields['naam']
                        . "<input type='hidden' name='contestant[]' value='$contestant' />"
                        . "</td>\n";
                $color = $rainbow->restart();
            }
            $oldContestant = $contestant;
            $remark = $resultSet2->fields['remark'];
            $grade = $resultSet2->fields['grade'];
            $criterium = $resultSet2->fields['criterium'];
            $grp_num = $resultSet2->fields['grp_num'];
            if ($inputs) {
                $myRow .="\t<td align='right' style='background:" . $color . ";'>"
                        . "<input type='hidden' name='criterium[]' value='" . $criterium . "' />"
                        . "<input type='number' class='num' min='1' max='10' size='2' name='grade[]' value='"
                        . $grade . "' onChange='validateGrade(this)' /></td>\n";
            } else {
                $myRow .= "\t<td align='right' style='background:" . $color . ";'>"
                        . $grade . "</td>\n";
            }
            $color = $rainbow->getNext();
            $resultSet2->moveNext();
        }
        if ($myRow != '') {
            if ($inputs) {
                $myRow .= "<td><textarea rows='2' cols='70' name='remark[]'>$remark</textarea></td>\n</tr>\n";
            } else {
                $myRow .= "<td>$remark</td>\n</tr>\n";
            }

            $myRow .="</tr>\n";
        }
        $result .= $myRow;
        return $result;
    }
    return $result;
}

?>
