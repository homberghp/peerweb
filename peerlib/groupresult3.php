<?php

/* $Id: groupresult3.php 1818 2014-11-09 16:31:20Z hom $ */

/**
 * display criteria in header
 * @param colspan: columns to cover per criterium
 */
function criteriaHead3( $criteria, $lang, $rainbow, $colspan = 1 ) {
    $startColor = $rainbow->restart();
    $crit = 1;
    $result = '';
    foreach ( $criteria as $value ) {
        $result .= "\t<th  class='navleft' colspan='$colspan' style='background:" . $startColor . ";'>$crit " . $value[ $lang . '_short' ] . "</th>\n";
        $startColor = $rainbow->getNext();
        $crit++;
    }
    return $result;
}

function criteriaSubHead( $criteria, $lang, $rainbow, $colspan, $headers = array( 'grade', 'group', 'mult' ) ) {
    $startColor = $rainbow->restart();
    $result = '';
    foreach ( $criteria as $value ) {
        foreach ( $headers as $head ) {
            $result .= "\t<th colspan='$colspan' style='background:" . $startColor .
                    ";'>$head</th>\n";
        }
        $startColor = $rainbow->getNext();
    }
    return $result;
}

function avgFoot( $avg, $rainbow ) {
    $startColor = $rainbow->restart();
    $result = '';
    foreach ( $avg as $value ) {
        $result .= "\t<th style='text-align:right;background:" . $startColor . ";'>$value</th>" .
                "<th style='text-align:right;background:" . $startColor . ";'></th>\n";
        $startColor = $rainbow->getNext();
    }
    return $result;
}

/**
 * results with avg and multiplier
 */
function groupResultTable( $dbConn, $prjtg_id, $overall_criterium, $productgrade, $header, $criteria, $lang, $rainbow, $addlink = false, $commitForm = false, $showTutGrade = false ) {
    $result = getGroupResultTable( $dbConn, $prjtg_id, $overall_criterium, $productgrade, $header, $criteria, $lang, $rainbow, $addlink, $commitForm, $showTutGrade );
    echo $result;
    return $result;
}

function getGroupResultQuery( $prjtg_id, $productgrade ) {
    return "select ags.snummer as contestant, "
            . "roepnaam||' '||coalesce(tussenvoegsel,'')||' '||achternaam as naam,role,\n"
            . " to_char(commit_time,'YYYY-MM-DD HH24:MI')as commit_time,\n"
            . " apt.prj_id,apt.grp_num,pg.prj_grp_open as open,criterium,"
            . " apt.milestone,criterium,ags.grade,ags.multiplier,mg.grade as tutor_grade,mg.grade as committed_grade,"
            . " coalesce(mg.grade,ags.grade[array_upper(ags.grade,1)]) as grade_proposal,\n"
            . "ags.multiplier[array_upper(ags.multiplier,1)] as overall_multiplier, to_char(trx.ts,'YYYY-MM-DD HH24:MM') as transaction_ts, trx.operator,trx.from_ip\n"
            . " from  assessment_grade_set({$prjtg_id},{$productgrade}) ags \n"
            . " join prj_grp pg using(prjtg_id,snummer)\n"
            . " join all_prj_tutor apt using(prjtg_id)\n"
            . " left join last_assessment_commit lac using(prjtg_id,snummer)\n"
            . " join student_email s on(ags.snummer=s.snummer)"
            . " left join student_role sr on(sr.snummer=s.snummer and apt.prjm_id=sr.prjm_id)\n"
            . " left join project_roles pr on(pr.prj_id=apt.prj_id and pr.rolenum=sr.rolenum)\n "
            . " left join milestone_grade mg on (apt.prjm_id=mg.prjm_id and s.snummer=mg.snummer)\n"
            . " left join transaction trx on (mg.trans_id=trx.trans_id)\n"
            . " order by achternaam,roepnaam"
    ;
}

function getGroupResultTable( $dbConn, $prjtg_id, $overall_criterium, $productgrade, $header, $criteria, $lang, $rainbow, $addlink = false, $commitForm = false, $showTutGrade = false ) {
    global $root_url;
    global $PHP_SELF;
    $sql = "select afko,year,description,prj_id,milestone,prjm_id,"
            . "grp_num,prjtg_id, coalesce(alias,'g'||grp_num) as alias"
            . " from project natural join prj_milestone natural join prj_tutor natural left join grp_alias"
            . " where prjtg_id=$prjtg_id";

    $resultSet = $dbConn->Execute( $sql );
    if ( !$resultSet->EOF ) {
        extract( $resultSet->fields );
    }
    $sql = getGroupResultQuery( $prjtg_id, $productgrade );
    $result = '<!-- start groupResultTable-->' . "\n";
    if ( $commitForm ) {
        $result .='<script type="text/javascript">
      function recalcGrades(){
        var grades = document.getElementsByName("tutor_grade[]");
        var pgrades = document.getElementsByName("pgrade[]");
        var mults  = document.getElementsByName("mults[]");
        var grpGrade = document.getElementById("productgrade").value;
        var g=0;
        var sum=0;
        for (i =0; i<grades.length; i++){
            g = Math.min(10,grpGrade*mults[i].value);
            grades[i].value = g.toFixed(1);
            pgrades[i].innerHTML = mults[i].value+ " * " + grpGrade+" = ";
            sum += g;
        }
        sum /= (grades.length);
        document.getElementById("trueAvg").innerHTML=sum.toFixed(2);
      }
      </script>
      ';
    } else {
        $result .='<script type="text/javascript">
      function recalcGrades(){
        var grades = document.getElementsByName("tutor_grade[]");
        var pgrades = document.getElementsByName("pgrade[]");
        var mults  = document.getElementsByName("mults[]");
        var grpGrade = document.getElementById("productgrade").value;
        var g=0;
        var sum=0;
        for (i =0; i<grades.length; i++){
            g = Math.min(10,grpGrade*mults[i].value);
            grades[i].innerHTML = g.toFixed(1);
            pgrades[i].innerHTML = mults[i].value+ " * " + grpGrade+" = ";
            sum += g;
        }
        sum /= (grades.length);
        document.getElementById("trueAvg").innerHTML=sum.toFixed(2);
      }
      </script>
      ';
    }
    //    $dbConn->log($sql);
    $resultSet2 = $dbConn->Execute( $sql );
    $oldContestant = 0;
    $avg = array();
    if ( $resultSet2 === false ) {
        $dbConn->logError( "cannot get result data with $sql, cause " . $dbConn->ErrorMsg() );
        $result .= "<h1>Sorry, no data available (db)</h1>\n";
        return $result;
    } else if ( $resultSet2->EOF ) {
        $result .= "<h1>Sorry, no data available (empty)</h1>\n";
        return $result;
    }
    //if ( $commitForm ) {
    $result .= "<form method='post' action='$PHP_SELF' name='commitform' onsubmit=\"return confirm('Are you sure you want to submit these data?')\">";
    //}
    $result .= "\n<table  id='groupresult' border='1' class='tabledata' width='100%' summary='group result'>\n";
    $result .= "\t<caption><span style='font-size:120%;font-weight:bold;'>\n"
            . "peer assessment for project $afko $year: \"$description\"<br/>"
            . " group <i>$alias</i></span></br>Prj_id/milestone=$prj_id/$milestone ($prjm_id) grp_num $grp_num ($prjtg_id) with average gorup grade [$productgrade]<caption>\n";
    $result .= "<colgroup>\n";
    $result .= "\t<col width='8%'/>\n" .
            "\t<col width='17%'/>\n" .
            "\t<col width='13%'/>\n" .
            "\t<col/>\n";
    $crits = count( $criteria );
    //$rainbow->restart();
    $color = $rainbow->restart();
    // this is not reliable under firefox 1.07
    for ( $i = 0; $i < $crits; $i++ ) {
        $result .= "\t<col style='background:$color'/>\n" .
                "\t<col style='background:$color'/>\n";
        $color = $rainbow->getNext();
    }
    $lastUsedColor = $color;
    if ( $showTutGrade ) {
        $result .= "\t<col  style='background:rgba(255,255,255,0.5);' width='130px'/>\n";
    }
    if ( \is_numeric( $resultSet2->fields[ 'committed_grade' ] ) ) {
        $result .= "<col style='background:rgba(255,255,255,0.5);'/>";
    }
    $result .= "</colgroup>\n";
    //$result .= "\n<thead style='background:rgba(255,255,255,0.5);'>\n";
    if ( $header ) {
        $result .= "<tr style='background:rgba(255,255,255,0.5);'><th colspan='4'>Student data</th>\n";
        $result .= criteriaHead3( $criteria, $lang, $rainbow, 2 );
        if ( $showTutGrade ) {
            $result .="\n\t<th align='right'>Tutor appraisal</th>\n";
            if ( $commitForm ) {
                $result .= "\t\t\t<th><input type='reset' value='reset'/></th>\n";
            }
        }
        $result .= "</tr>\n";
        $result .= "<tr style='background:rgba(255,255,255,0.5);'>\n";
        $result .= "\t<th align='left'>Number</th>\n";
        $result .= "\t<th align='left'>Student</th>\n";
        $result .= "\t<th align='left'>Role</th>\n";
        $result .= "\t<th align='left'>Open</th>\n";
        $result .= criteriaSubHead( $criteria, $lang, $rainbow, 1, array( 'grade', 'mult' ) );
        if ( $showTutGrade ) {
            if ( $commitForm ) {
                $result .="\n\t<th align='right'>Group Grade"
                        . "<input type='button'  name='recalc' onClick='javascript:recalcGrades()'\n"
                        . "value='=' title='(re) compute the grades from group grade' style='font-weight:bold'/><br/>\n"
                        . " <input type='text' id='productgrade' name='productgrade' style='text-align:right;' "
                        . " maxlength='3' size='2' value='$productgrade' /></th>\n";
            } else {
                $result .="\n\t<th align='right'>Group Grade= {$productgrade}</th>\n";
            }
            if ( \is_numeric( $resultSet2->fields[ 'committed_grade' ] ) ) {
                $result .= "<th>Committed grade</th>\n";
            }
        }
        $result .= "</tr>\n";
    }
    //$result .= "\n</thead>\n";
    $continuation = '';
    $rainbow->restart();
    $color = $rainbow->getCurrent();
    $colCounter = 0;
    $multSums = array();
    $gradeSums = array();
    // init arrays to 0.
    for ( $i = 0; $i < $crits; $i++ ) {
        $multSums[] = 0;
        $gradeSums[] = 0;
    }
    $tutorGradeSum = 0;
    $committedGradeSum = 0;
    $rowCount = 0;
    while ( !$resultSet2->EOF ) {
        extract( $resultSet2->fields );
        $rowCount++;
        $result .= "<tr>\n";
        $result .= "\t<td title='student details'><a href='student_admin.php?snummer=$contestant' target='_blank'>$contestant</td>\n";
        if ( isSet( $commit_time ) ) {
            $result .= "\t<td title='$naam: committed at $commit_time'>";
        } else {
            $result .= "\t<td title='$naam: never committed' style='background:#F00;color:#FFF'>";
        }
        if ( $addlink ) {
            $astyle = isSet( $commit_time ) ? "style='text-decoration:none'" : "style='text-decoration:line-through'";
            $result .="<a href='$root_url/ipeer.php?prjm_id=$prjm_id&amp;" .
                    "snummer=$contestant' $astyle target='_blank' >";
        }
        $result .= $naam;
        if ( $addlink )
            $result .="</a>";
        $result .= "</td>\n";
        $result .= "\t<td>$role</td>\n";
        $open = ($resultSet2->fields[ 'open' ] == 't') ? 'open' : 'closed';
        $result .= "\t<td>" . $open . "</td>\n";
        $color = $rainbow->restart();
        $colCounter = 0;

        $len = strlen( $multiplier );
        $amult = preg_split( '/,/', substr( substr( $multiplier, 1 ), 0, $len - 2 ) );

        $len = strlen( $grade );
        $agrade = preg_split( '/,/', substr( substr( $grade, 1 ), 0, $len - 2 ) );
        $crits = count( $agrade );
        $rainbow->restart();
        $overallMult = 0;
        for ( $i = 0; $i < $crits; $i++ ) {
            $result .="\t\t<td align='right'>{$agrade[ $i ]}</td><td align='right'>{$amult[ $i ]}</td>\n";
            $multSums[ $i ] += $amult[ $i ];
            $overallMult = $amult[ $i ];
            $gradeSums[ $i ] += $agrade[ $i ];
            $color = $rainbow->getNext();
        }

        if ( $commitForm && !isSet( $tutor_grade ) ) {
            // suggest overall grade
            $tutor_grade = max( 1, min( 10, $agrade[ $crits - 1 ] ) );
        }
        $tutor_grade = round( $tutor_grade, 2, PHP_ROUND_HALF_EVEN );
        if ( $showTutGrade ) {
            if ( $commitForm ) {
                $result .="\t\t<td align='right'> "
                        . "\t\t\t<span name='pgrade[]'>{$overallMult} * {$productgrade} = </span><input type='hidden' name='mults[]' value='{$overallMult}'/>\n"
                        . "<input type='text' style='text-align:right' "
                        . "maxlength='3' size='2' name='tutor_grade[]' value='{$grade_proposal}'/>\n"
                        . "\t\t\t<input type='hidden' name='gnummer[]' value='{$contestant}'/>\n"
                        . "</td>\n";
                $tutorGradeSum += $tutor_grade;
            } else {
                $grade_proposal = \max( 1, \min( 10, $grade_proposal ) );
                $result .="\t\t<td align='right'>\n"
                        . "\t\t\t<input type='hidden' name='mults[]' value='" . $overallMult . "'/>\n"
                        . "<span name='tutor_grade[]'>{$grade_proposal}</span></td>\n";
                $tutorGradeSum += $tutor_grade;
            }
            if ( \is_numeric( $committed_grade ) ) {
                $committedGradeSum += $committed_grade;
                $result .= "<td style='color:background:rgba(255,255,255,0.5); font-weight:bold;text-align:right' title='by {$operator} on {$transaction_ts} from {$from_ip}'>{$committed_grade}</td>";
            }
        }
        $resultSet2->moveNext();
    }
    if ( $header ) {
        $result .= "\t<tr>\n";
        $result .= "\t<th colspan='4' class='tabledata head'>Group average</th>\n";
        $rainbow->restart();
        for ( $i = 0; $i < $crits; $i++ ) {
            $result .="\t\t<td align='right'>" . number_format( $gradeSums[ $i ] / $rowCount, 2 ) . "</td><td align='right'>1.00</td>\n";
            $color = $rainbow->getNext();
        }
        if ( $showTutGrade ) {
            $result .="\t\t<th align='right'>True group average&nbsp;&nbsp;&nbsp;<span id='trueAvg'>" . number_format( $tutorGradeSum / $rowCount, 2 ) . "</span></th>\n";
        }
        if ( $committedGradeSum > 0 ) {
            $avg = round( $committedGradeSum / $rowCount, 2, PHP_ROUND_HALF_EVEN );
            $result .= "<td style='background:rgba(255,255,255,0.5); font-weight:bold;text-align:right'>{$avg}</td>\n";
        }
        $result .= "\t</tr>\n";
        if ( $showTutGrade ) {
            $cspan = 4 + 2 * $crits;
            $result .= "\t<tr style='background:rgba(255,255,255,0.5);'><td colspan='$cspan' style='padding-left: 10px'>"
                    . "<p>This page computes a grade proposal based on the group grade and the individual multiplier. "
                    . "When the tutor commits by pressing the button, the grades are saved in the database as final (progress) grades.<br/>Those grades "
                    . "will then be visible in the column <b>committed grade</b> on this page. That column is only visible when there was at least one commit.</p>"
                    . "</td><td align='right'>";

            if ( $commitForm ) {
                $result .= "<b>As&nbsp;Tutor</b><input type='submit' name='commit' value='Commit' "
                        . " title='save in database as individual notes.'/>";
            }
            $result .= "</td></tr>\n";
        }
    }

    $result .= "</table>\n" . '<!-- end groupResultTable-->';
//  if ( $commitForm ) {
    $result .= "\n</form><!-- end commit form-->\n";
    //}

    return $result;
}

// groupresulttable
?>
