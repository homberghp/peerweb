<?php

/**
 * returns barchars rendering for open and ready state of prj/milestone
 */
function groupOpenerBarChart2($dbConn, $prjm_id, $tutorOwner = false) {
    global $PHP_SELF;
    $maxFillPos = 73;
    $sql = "select size,ready_count,\n" .
            "tut_name,prjtg_id,grp_num,prjm_id,prj_id,milestone,\n" .
            "alias,prj_milestone_open,prj_tutor_open \n" .
            " from barchart_view where prjm_id=$prjm_id order by grp_num";
    $resultSet = $dbConn->Execute($sql);
    //    $dbConn->log($sql);
    if ($resultSet === false) {
        print( "<br>Cannot get project data with <pre>$sql</pre>, cause " . $dbConn->ErrorMsg() . "<br>");
    }
    $allOpen = true;
    $grpTutorString1 = "<tr class='barchart'><th>Group</th>\n";
    $grpTutorString2 = "<tr class='barchart'><th>Count</th>\n";
    $grpTutorString3 = "<tr class='barchart'><th>Open</th>\n";
    $grpTutorString4 = "<tr class='barchart' height='$maxFillPos'><th>Ready</th>\n";

    $contin = '';
    $grp_col_count = 3; // one for head, one for buttons, one for strut
    $leftpx = 90;
    $scount = 0;
    $fillimg = IMAGEROOT . '/redbar1.png';
    while (!$resultSet->EOF) {
        extract($resultSet->fields);
        $open_prjtg_id = $prjtg_id;
        if ($resultSet->fields['prj_tutor_open'] == 't') {
            $backgnd = '#008';
            $grpTutorString3 .= $contin . "\t\t<th><input type='checkbox' name='opengrp[]' " .
                    "value='$open_prjtg_id' checked title='open or close assessment for group'/>" .
                    "<input type='hidden' name='openclose_candidate[]' value='$open_prjtg_id'/>";
            if ($resultSet->fields['prj_milestone_open'] == 't') {
                $fillimg = IMAGEROOT . '/bluebar1.png';
            } else {
                $fillimg = IMAGEROOT . '/yellowbar1.png';
            }
            $allOpen &= true;
        } else {
            $backgnd = '#800';
            $grpTutorString3 .= $contin . "\t\t<th><input type='checkbox' name='opengrp[]' " .
                    "value='$open_prjtg_id' title='open or close assessment for group'/>" .
                    "<input type='hidden' name='openclose_candidate[]' value='$open_prjtg_id'/>";
            $fillimg = IMAGEROOT . '/redbar1.png';
            $allOpen &= false;
        }
        //$fillimg = IMAGEROOT . '/bluebar1.png';
        $scount +=$size;
        if ($size > 0) {
            $fillpos = $maxFillPos - round(($maxFillPos * $ready_count) / $size, 0) - 5;
        } else {
            $fillpos = $maxFillPos - 5;
        }
        $grpTutorString1 .= $contin . "\t\t<th class='prel' style='text-align:right;padding-right:4px;'>"
                . "<a href='$PHP_SELF?prjtg_id=$prjtg_id&amp;prjm_id=$prjm_id'"
                . " style='text-decoration:none;color:white;'>" . $grp_num
                . "</a>\n"
                . "<div class='grpinfo' style='left:${leftpx}px;color:#000;'>$prjtg_id&nbsp;$alias<br/>Tutor: $tut_name"
                . "</div>\n";

        $grpTutorString2 .= $contin . "\t\t<th style='text-align:right;padding-right:4px;'>" . $size;
        $grpTutorString4 .= $contin . "\t\t<th width='25px' style='text-align:right;padding-right:4px; background-position: 0% ${fillpos}px;" .
                "background-color:black;background-repeat:repeat-x;background-image: url($fillimg)'>" . $ready_count;
        $contin = "</th>\n";
        $grp_col_count++;
        $leftpx += 21;
        $resultSet->moveNext();
    }
    $grpTutorString = $grpTutorString1 . "\n<th>Group</th></tr>\n" .
            $grpTutorString3 . "\n</th>\n" .
            "<th>" . ($tutorOwner ? "<input type='checkbox' name='checkall' " . ($allOpen ? 'checked' : '') . " onclick='javascript:checkThem(\"opengrp[]\",this.checked)' title='(un)check all at once'/>Open" : "&nbsp;") . "</th></tr>\n" .
            $grpTutorString4 . "\n</th><th >Ready</th></tr>\n" .
            $grpTutorString2 . "\n</th><th >Count&nbsp;($scount)</th></tr>\n";
    $result = "<script type='text/javascript'>\n
function checkThem(ref,state){
  var checks = document.getElementsByName(ref);
  var boxLength = checks.length;
      for ( i=0; i < boxLength; i++ ) {
        checks[i].checked = state;
      }
}
</script>" .
            "<div class='nav'><table summary='group opener graph'><tr><td valign='top'>\n" .
            "<form name='grp_opener' method='post' action='$PHP_SELF'>\n" .
            "<table border='1' cellpadding='0' style='border-color:#888;border-collapse:collapse;background-color:#004;color:white;padding:0;margin:0;' summary='layout'>\n" .
            "\t<caption style='background-color:#004;font-weight:bold'>Number of students in groups:</caption>\n" .
            $grpTutorString .
            "<tr><td colspan='$grp_col_count'>" .
            "<input type='submit' name='submit' value='Submit'/>&nbsp;\n" .
            "<input type='reset' name='reset' value='reset form'/>\n" .
            "</td></tr>" .
            "</table>\n" .
            "<input type='hidden' name='open_prjm_id' value='${prjm_id}'/>\n" .
            "</form>\n" .
            "</td></tr>" . "<tr><td valign='top'>\n" .
            "<p>The assesment is closed for those groups that show " .
            "<span style='background:red;font-weight:bold;color:white'>red</span> and have their tickmark unset.<br/> The group is automatically closed when alle students in the group have filled in their assessment input.<br/>This is shown with the bar chart reaching the top in red and the ready value equal to the count.</p>" .
            "<p>The assesment is open for those groups that show " .
            "<span style='background:#008;font-weight:bold;color:white'>blue</span> and have their tickmark set.</p>" .
            "<p>The normal way of working is: select all then press submit.</p>" .
            "\n</td></tr></table>" .
            "</div>\n";
    return "<!-- begin openBarChart2.php -->\n" . $result . "\n<!-- end openBarChart2.php -->\n";
}

function groupOpener($dbConn, $prjm_id, $isTutorOwner, $form_array) {
    // incoming prjm_id not used
    if ($isTutorOwner && isSet($form_array['openclose_candidate'])) {
        $open_prjm_id = $form_array['open_prjm_id'];
        $candidates = implode(",", $form_array['openclose_candidate']);
        if (isSet($form_array['opengrp'])) {
            $openset = implode(",", $form_array['opengrp']);
        } else {
            $openset='(0)'; 
        }
        $sql = "begin work;\n";
        if (isSet($form_array['opengrp'])) {
            $openset = implode(",", $form_array['opengrp']);
            $sql .= "INSERT INTO assessment (contestant,judge,criterium,grade,prjtg_id)\n" .
                    " SELECT contestant,judge,criterium,grade,prjtg_id\n" .
                    " from assessment_builder3 ab where (contestant,judge,criterium,prjtg_id) not in\n" .
                    " (select distinct contestant,judge,criterium,prjtg_id \n" .
                    "   from assessment where prjtg_id in (select prjtg_id from prj_tutor pt where pt.prjm_id=$prjm_id ))\n" .
                    " and ab.prjm_id=$prjm_id;\n" .
                    "update prj_tutor set prj_tutor_open=true,assessment_complete=false where prjtg_id in ($openset);\n" .
                    "update prj_grp set prj_grp_open=true where prjtg_id in ($openset);\n" .
                    "update prj_grp set prj_grp_open=false where prjtg_id in ($candidates) and prjtg_id not in ($openset);\n" .
                    "update prj_tutor set prj_tutor_open=false where prjtg_id in ($candidates) and prjtg_id not in ($openset);\n"
                    . "update prj_milestone set has_assessment=true where prjm_id=$prjm_id;\n";
        } else {
            $sql .= "update prj_tutor set prj_tutor_open=false where prjtg_id in ($candidates);\n" .
                    "update prj_grp set prj_grp_open=false where prjtg_id in ($candidates);\n";
        }

        $sql .= "update prj_milestone pm set prj_milestone_open=(select not should_close as open from should_close_prj_milestone where prjm_id=pm.prjm_id)" .
                "  where prjm_id=$open_prjm_id;\n" .
                "commit;";
        //$dbConn->log($sql);
        $resultSet = $dbConn->Execute($sql);
        if ($resultSet === false) {
            echo( "<br>Cannot update grp open/close with <pre>$sql</pre>, cause" . $dbConn->ErrorMsg() . "<br>");
            stacktrace(1);
            die();
        }
    }
}

?>