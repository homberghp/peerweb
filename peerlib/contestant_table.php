<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * create tablecontents for form or result.
 */
function groupContestantTable($dbConn, $sql, $inputs, $criteria, $lang, $rainbow) {
    return groupContestantTableH($dbConn, $sql, $inputs, true, $criteria, $lang, $rainbow);
}

function groupContestantTableH($dbConn, $sql, $inputs, $header, $criteria, $lang, $rainbow) {
    global $langmap;
    $resultSet2 = $dbConn->Execute($sql);
    $oldJudge = 0;
    $result = '';
    if ($resultSet2 === false) {
        $result .= "cannot get resultTable with $sql, reason: " . $dbConn->ErrorMsg();
        return;
    } else if ($resultSet2->EOF) {
        $result .= "<h1>Sorry, no data yet</h1>\n";
        return;
    } else {
        if ($header) {
            $result .= "<tr>\n";
            $result .= "\t<th>" . $langmap['nummer'][$lang] . "</th>\n";
            $result .= "\t<th>" . $langmap['medestudent'][$lang] . "</th>\n";
            $startColor = $rainbow->restart();
            foreach ($criteria as $value) {
                $result .= "\t<th class='navleft' style=\"background:" . $startColor . ";\">" . $value[$lang . '_short'] . "</th>\n";
                $startColor = $rainbow->getNext();
            }
            $result .= "</tr>\n";
        }
        $continuation = '';
        $color = $rainbow->restart();
        while (!$resultSet2->EOF) {
            $judge = $resultSet2->fields['judge'];
            if ($oldJudge !== $judge) {
                $result .= $continuation;
                $result .= "<tr>\n";
                $result .= "\t<td>" . $resultSet2->fields['judge'] . "</td>\n";
                $result .= "\t<td>" . $resultSet2->fields['naam'] . "</td>\n";
                $continuation = "</tr>\n";
                $color = $rainbow->restart();
            }
            $oldJudge = $judge;
            $grade = $resultSet2->fields['grade'];
            $criterium = $resultSet2->fields['criterium'];
            $grp_num = $resultSet2->fields['grp_num'];
            $result .= "\t<td align='right' style='background:" . $color . ";'>" .
            $grade . "</td>\n";
            $color = $rainbow->getNext();
            $resultSet2->moveNext();
        }
        $result .= $continuation;
    }
    return $result;
}

?>
