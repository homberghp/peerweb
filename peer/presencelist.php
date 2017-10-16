<?php
include_once('peerutils.php');
require_once('validators.php');
include_once('navigation2.php');
include 'simplequerytable.php';
require_once 'selector.php';
require_once 'studentpicker.php';
require_once 'prjMilestoneSelector2.php';
require_once 'TableBuilder.class.php';
requireScribeCap($peer_id);

class MyRowFactory implements RowFactory {

    private $rowCounter = 1;
    private $old_grp_num = 0;
    private $rainbow;
    private $rowColor;

    function __construct() {
        $this->rainbow = new RainBow();
        $this->rowColor = $this->rainbow->getCurrent();
    }

    public function startRow($valueArray) {
        extract($valueArray);
        $grpnamecel = ''; // only set grpnum cell on new grp once
        if ($this->old_grp_num != $grp_num) {
            $this->old_grp_num = $grp_num;
            $this->rowColor = $this->rainbow->getNext();
            $grpnamecel = "\t<td class='tabledata num' style='font-size:200%;vertical-align:top' rowspan='$grp_size'>"
                    . "<button type='button' style='height:100%;width:100%' clas='grp_but' "
                    . ' onclick="checkAll(document.getElementById(\'activity\'), \''."pg{$grp_num}".'\',\'present\', true)" '."value='{$grp_name}'>g{$grp_num}</button>"
                    . "</td>\n"
                    . "";
        }
        $result = "<tr style='background-color:" . $this->rowColor . "'>\n\t<td>" . $this->rowCounter . "</td>\n"
                //. "\t<td class='tabledata num'>$grp_name</td>\n"
                . $grpnamecel
                . "\t<td class='tabledata num'>$snummer<input type='hidden' name='participant[]' value='$snummer'/></td>\n"
                . "\t<td>$name</td>\n"
                . "\t<td>$face</td>\n";
        $this->rowCounter++;
        return $result;
    }

    public function buildHeader($valueArray) {
        return "\t<th colspan='5' align='right'><span style='font-weight:bold;font-size:140%'>Apply to all</th>"
                . "\t<td class='rs'>"
                . "\t\t<div style='font-size:80%' id='rs'>\n"
                . "\n\t<input type='radio' name='rs' "
                . 'onclick="checkAll(document.getElementById(\'activity\'), \'absent\', \'absent\', this.checked)"/>Absent&nbsp;<br/>'
                . "\n\t<input type='radio' name='rs' "
                . 'onclick="checkAll(document.getElementById(\'activity\'), \'present\',  \'present\', this.checked)"/>Present&nbsp;<br/>'
                . "\n\t<input type='radio' name='rs' "
                . 'onclick="checkAll(document.getElementById(\'activity\'), \'reason\', \'reason\', this.checked)"/>Absent&nbsp;with&nbsp;reason&nbsp;<br/>'
                . "\n\t</div></td><th>Write comment <br/>for any absence reason</th></tr><tr>"
                . "<th class='tabledata head num'>#</th>\n"
                . "\t<th class='tabledata num' style='text-algin:left;'>grp#</th>\n"
                . "\t<th class='tabledata head num' style='text-algin:left;'>Number</th>\n"
                . "\t<th class='tabledata head' style='text-algin:left;'>Name</th>\n"
                . "\t<th class='tabledata head' style='text-algin:left;'>Face</th>\n"
                . "\t<th class='tabledata head num' style='text-algin:left;'>Mark</th>\n"
                . "\t<th class='tabledata head' style='text-algin:left;'>Comment</th>\n";
        //      ."\t<th> Trans</th>";
    }

    public function buildCell($valueArray) {
        extract($valueArray);
        $checkedAbsent = !isSet($participant) ? '' : 'checked';
        $checkedPresent = (isSet($participant) && $presence == 'P') ? 'checked' : '';
        $checkedReason = (isSet($participant) && $presence == 'A') ? 'checked' : '';

        if (isSet($participant)) {
            if ($presence == 'P') {
                $divClass = 'present';
            } else if ($presence == 'A') {
                $divClass = 'reason';
            } else {
                $divClass = 'absent';
            }
        } else {
            $divClass = 'absent';
        }

        $trans_title = '';
        if (isSet($operator)) {
            $trans_title = "title = 'operator $operator at $ts from $from_ip'";
        }
        return "\t<td>\n"
                . "\t\t<div style='font-size:80%' id='radio_${snummer}' class='$divClass'>\n"
                . "\t\t\t<input type='radio' class='absent a$grp_id' name='mark_${snummer}[]'"
                . " value='' $checkedAbsent style='vertical-align: middle' onChange='this.parentNode.className=\"absent\"'>Absent&nbsp;<br/>\n"
                . "\t\t\t<input type='radio' class='present p$grp_id' name='mark_${snummer}[]'"
                . " value='P' $checkedPresent style='vertical-align: middle' onChange='this.parentNode.className=\"present\"'/>Present&nbsp;<br/>\n"
                . "\t\t\t<input type='radio' class='reason r$grp_id' name='mark_${snummer}[]' "
                . "value='A' $checkedReason style='vertical-align: middle' onChange='this.parentNode.className=\"reason\"'/>Absent with reason&nbsp;\n"
                . "\t\t</div>\n\t</td>"
                . "\t<td class='tabledata'><textarea rows='2' cols='50' name='comment[]'>$comment</textarea></td>\n";
        //      ."\t<td class='tabledata num' $trans_title >$trans_id</td>\n";
    }

    public function buildHeaderCell($valueArray) {
        return '';
    }

}

$prjm_id = 0;
$act_id = 1;
extract($_SESSION);
//$dbConn->setSqlAutoLog(true);

$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
$prjSel->setJoin(' all_project_scribe aps using(prj_id) ');
$prjSel->setWhere(" {$peer_id} =aps.scribe ");
extract($prjSel->getSelectedData());
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;

if (isSet($_REQUEST['act_id'])) {
    $_SESSION['act_id'] = $act_id = validate($_REQUEST['act_id'], 'integer', $act_id);
} else if (!defined($_SESSION['act_id'])) {
    // get last defined activity for project milestone
    $sql = "select max(act_id) as act_id from activity where prjm_id=$prjm_id";
    $rs = $dbConn->Execute($sql);
    if (!$rs->EOF) {
        extract($rs->fields);
        $_SESSION['act_id'] = $act_id;
    }
}
if (isSet($act_id) && ($act_id >= 0)) {
    $sql = "(select 1 as seq, act_id,short,act_type_descr as act_type, description,datum,part,start_time \n" .
            "from activity natural join activity_type where act_id=$act_id\n" .
            "union\n" .
            "(select 2 as seq, act_id,short,act_type_descr as act_type, description,datum,part,start_time \n" .
            "from activity natural join activity_type where act_id in (select act_id from activity where prjm_id=$prjm_id)) " .
            " order by seq,datum desc, datum desc,part desc) limit 1";
    $rs = $dbConn->Execute($sql);
    if (!$rs->EOF) {
        extract($rs->fields);
    }
} else
    $act_id = 1;

if (isSet($_REQUEST['bsubmit']) && isSet($_REQUEST['participant']) && isProjectScribe($prj_id, $peer_id)) {
    $participant = $_REQUEST['participant'];
    $participantSet = '\'' . implode("',\n\t'", $participant) . '\'';
    $sql = "begin work;\ndelete from activity_participant where act_id=$act_id;\n";

    for ($i = 0; $i < count($participant); $i++) {
        if ($_REQUEST['mark_' . $participant[$i]][0] == 'P') {
            $sql .= "insert into activity_participant (act_id,snummer,presence) values ($act_id,$participant[$i],'P');\n";
        } else if ($_REQUEST['mark_' . $participant[$i]][0] == 'A') {
            $sql .= "insert into activity_participant (act_id,snummer,presence) values ($act_id,$participant[$i],'A');\n";
        }
    }
    $sql .= "delete from absence_reason where act_id=$act_id;\n";

    for ($i = 0; $i < count($participant); $i++) {
        if (isSet($_REQUEST['comment'][$i])) {
            $comment = pg_escape_string($_REQUEST['comment'][$i]);
            if ('' != $comment) {
                $sql .= "insert into absence_reason (act_id,snummer,reason) values ($act_id,$participant[$i],'$comment');\n";
            }
        }
    }

    $sql .= "commit;";

    $dbConn->log($sql);
    $rts = $dbConn->Execute($sql);
    if ($rts === false) {
        $dbConn->Execute("rollback;");
    }
}

$script = '<script type="text/javascript" src="js/jquery-1.7.1.min.js"></script>'
        . '<script type="text/javascript" language="JavaScript">
    function checkAll(theForm, cName, cClass, status) {
    var n=theForm.elements.length;
   for (i=0;i<n;i++) {
       if (theForm.elements[i].classList.contains(cName)) {
            theForm.elements[i].checked = status;
            if (status) {
               theForm.elements[i].parentNode.className=cClass;
            }
       }
    }
}
</script>
<style type="text/css">
 th, *.rs{ background:rgba(255,255,255,0.4); }
</style>
';
// get group tables for a project
pagehead2('Get presence list', $script);
$page_opening = "Presence list for students attending activities xyz";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$nav->show();
$sql3 = "select datum||', '||' ('||act_id||', #'||coalesce(apc.count,0)||') '||act_type_descr||' '||rtrim(short)" .
        "||'*'||part||': '||rtrim(description) as name, act_id as value," .
        "to_char(datum,'IYYY')||':'||milestone as namegrp\n" .
        " from activity join activity_type using(act_type) join prj_milestone using(prjm_id) ".
        "left join act_part_count apc using(act_id) \n\t" .
        " where prjm_id=$prjm_id\n" .
        "order by namegrp desc,datum desc,part asc";
$actSel = new Selector($dbConn, 'act_id', $sql3, $act_id);
$act_id_selector = $actSel->getSelector();
$participant = array();
$sql = "select snummer as participant from activity_participant where act_id=$act_id";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    print "error fetching participant data with $sql : " . $dbConn->ErrorMsg() . "<br/>\n";
} else
    while (!$resultSet->EOF) {
        array_push($participant, $resultSet->fields['participant']);
        $resultSet->moveNext();
    }
$sql = "select count(*) as present from prj_grp join prj_tutor using(prjtg_id) join activity_participant using(snummer) where act_id=$act_id\n" .
        " and prjm_id=$prjm_id ";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    print "error fetching count data with $sql : " . $dbConn->ErrorMsg() . "<br/>\n";
}
{
    $present = $resultSet->fields['present'];
    $sql = "select coalesce(alias,'g'||apt.grp_num::text) as sgroup,st.snummer,size as grp_size,\n" .
            "achternaam||', '||roepnaam||coalesce(' '||tussenvoegsel,'') as name," .
            "'<img src=\"'||photo||'\" width=32 height=48/>' as face,\n" .
            "snummer as participant,ap.presence,ar.reason as comment,apt.grp_num\n" .
            ",coalesce(alias,'g'||apt.grp_num) as grp_name, 'g'||apt.grp_num as grp_id" .
            " from prj_grp pg join all_prj_tutor apt using(prjtg_id) join grp_size using(prjtg_id)\n" .
            " natural join student st " .
            " natural join portrait \n" .
            "left join (select snummer,presence from activity_participant \n" .
            "            where act_id=$act_id) ap using(snummer)\n" .
            " left join (select snummer,reason from absence_reason \n" .
            "            where act_id=$act_id) ar using(snummer) \n" .
            " where prjm_id=$prjm_id \n" .
            " order by apt.grp_num,achternaam,roepnaam\n";
    $myRowFactory = new MyRowFactory();
    $tableBuilder = new TableBuilder($dbConn, $myRowFactory);
    include 'templates/presencelist.html';
}
