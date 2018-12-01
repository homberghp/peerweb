<?php
requireCap(CAP_TUTOR);
require_once('validators.php');
include_once('navigation2.php');
require_once 'selector.php';
require_once 'prjMilestoneSelector2.php';
require_once 'TableBuilder.class.php';
requireScribeCap($peer_id);

class MyRowFactory implements RowFactory {

    private $rowCounter = 1;
    private $old_grp_num = 1;
    private $rainbow;
    private $rowColor;

    function __construct() {
        $this->rainbow = new RainBow(STARTCOLOR, COLORINCREMENT_RED, COLORINCREMENT_GREEN, COLORINCREMENT_BLUE);
        $this->rowColor = $this->rainbow->getCurrent();
    }

    public function startRow($valueArray) {
        extract($valueArray);
        if ($this->old_grp_num != $grp_num) {
            $this->old_grp_num = $grp_num;
            $this->rowColor = $this->rainbow->getNext();
        }
        $result = "<tr style='background-color:" . $this->rowColor . "'>\n\t<td>" . $this->rowCounter . "</td>\n"
                . "\t<td class='tabledata num'>$grp_name</td>\n"
                . "\t<td class='tabledata num'>$snummer<input type='hidden' name='participant[]' value='$snummer'/></td>\n"
                . "\t<td>$name</td>\n"
                . "\t<td>$face</td>\n";
        $this->rowCounter++;
        return $result;
    }

    public function buildHeader($valueArray) {
        return "<th class='tabledata head num'>#</th>\n"
                . "\t<th class='tabledata num' style='text-algin:left;'>grp#</th>\n"
                . "\t<th class='tabledata head num' style='text-algin:left;'>Number</th>\n"
                . "\t<th class='tabledata head' style='text-algin:left;'>Name</th>\n"
                . "\t<th class='tabledata head' style='text-algin:left;'>Face</th>\n"
                . "\t<th class='tabledata head num' style='text-algin:left;'>Mark</th>\n"
                . "\t<th class='tabledata head num' style='text-algin:left;'>grade</th>\n"
                . "\t<th class='tabledata head' style='text-algin:left;'>Comment</th>\n"
                . "\t<th> Trans</th>";
    }

    public function buildCell($valueArray) {
        extract($valueArray);
        if ($participant)
            $checked = 'checked';
        $trans_title = '';
        if (isSet($operator)) {
            $trans_title = "title='operator $operator at $ts from $from_ip'";
        }
        return "\t<td class='tabledata num'>"
                . "<input type='text' name='mark[]' value='$mark' size='2' maxlength='2' style='width:12pt;'/></td>\n"
                . "\t<td class='tabledata num'><input type='text' name='grade[]' value='$grade' size='2' maxlength='4' align='right'/></td>\n"
                . "\t<td class='tabledata'><textarea rows='2' cols='50' name='comment[]'>$comment</textarea></td>\n"
                //      ."\t<td class='tabledata'><input type='text' name='comment[]' value='$comment' size='40'/></td>\n"
                . "\t<td class='tabledata num' $trans_title >$trans_id  </td>\n";
    }

    public function buildHeaderCell($valueArray) {
        return '';
    }

}

$prjm_id = 0;
$task_id = 1;
extract($_SESSION);
//$dbConn->setSqlAutoLog(true);
$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
$prjSel->setJoin(' all_project_scribe using(prj_id) ');
$prjSel->setWhere(' prj_id in (select prj_id from project_task) and ' . $peer_id . '=scribe');
extract($prjSel->getSelectedData());
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;

if (isSet($_REQUEST['task_id'])) {
    $_SESSION['task_id'] = $task_id = validate($_REQUEST['task_id'], 'integer', $task_id);
} else if (!isSet($_SESSION['task_id'])) {
    // get last defined activity for project milestone
    $sql = "select max(task_id) as task_id from project_task where prj_id=$prj_id";
    $rs = $dbConn->Execute($sql);
    if (!$rs->EOF) {
        extract($rs->fields);
        $_SESSION['task_id'] = $task_id;
    }
}
if (isSet($task_id) && ($task_id >= 0)) {
    $sql = "(select 1 as seq, task_id,name, description\n" .
            "from project_task where task_id=$task_id\n" .
            "union\n" .
            "(select 2 as seq, task_id,name, description \n" .
            "from project_task where task_id in (select task_id from project_task where prj_id=$prj_id)) " .
            " order by seq,name) limit 1";
    $rs = $dbConn->Execute($sql);
    if (!$rs->EOF) {
        extract($rs->fields);
    }
} else
    $task_id = 1;

if (isSet($_REQUEST['bsubmit']) && isSet($_REQUEST['participant']) && isProjectScribe($prj_id, $peer_id)) {
    $trans_id = $dbConn->transactionStart('task completed insert');
    //  $dbConn->log('transaction_id='.$trans_id);
    $rs = $dbConn->Execute("lock project_task_completed in access exclusive mode");
    // get old data in map
    $sql = "select 's'||snummer as sn,mark,grade,trim(comment) as comment from project_task_completed_latest where task_id=$task_id";
    //  $dbConn->log($sql);
    $rs = $dbConn->Execute($sql);
    if ($rs === false) {
        print "error fetching participant data with $sql : " . $dbConn->ErrorMsg() . "<br/>\n";
    }
    $map = array();
    while (!$rs->EOF) {
        extract($rs->fields);
        $map[$sn] = array();
        $map[$sn]['mark'] = $mark;
        $map[$sn]['grade'] = $grade;
        $map[$sn]['comment'] = stripslashes($comment);
        $rs->moveNext();
    }
//    $sql = ''// "begin work;" // delete from project_task_completed where task_id=$task_id;\n"
//            . "insert into project_task_completed (task_id,snummer,mark,grade,comment,trans_id) values \n";

    $sql = <<<'SQL'
insert into project_task_completed (task_id,snummer,mark,grade,comment,trans_id) 
    values($1,$2,$3,$4,$5,$6);
    
SQL;
    $stmt = $dbConn->Prepare($sql);
    for ($i = 0; $i < count($_REQUEST['participant']); $i++) {
        $participant = $_REQUEST['participant'][$i];
        $mark = trim($_REQUEST['mark'][$i]);
        $grade = trim($_REQUEST['grade'][$i]);
        if ($grade ==='') $grade=null;
        $comment = trim($_REQUEST['comment'][$i]);
        $stmt->execute(array($task_id, $participant, $mark, $grade, $comment, $trans_id));
    }

    $dbConn->transactionEnd();
}


$sql3 = "select task_id as value,name||'('||task_id||')'||': '||description as name from project_task where prj_id=$prj_id order by task_number";
$taskSel = new Selector($dbConn, 'task_id', $sql3, $task_id);
$task_id_selector = $taskSel->getSelector();
$participant = array();

$sql = "select snummer as participant from project_task_completed where task_id=$task_id";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    print "error fetching participant data with $sql : " . $dbConn->ErrorMsg() . "<br/>\n";
} else
    while (!$resultSet->EOF) {
        array_push($participant, $resultSet->fields['participant']);
        $resultSet->moveNext();
    }
$sql = "select st.snummer,"
        . "achternaam||', '||roepnaam||coalesce(' '||tussenvoegsel,'') as name,\n"
        . "'<img src=\"'||p.photo||'\" width=''32'' height=''48''/>' as face,ptc.\n"
        . "snummer as participant,ptc.mark,ptc.grade,ptc.comment,pg.grp_num\n"
        . ",alias as grp_name\n"
        . ",trans_id,operator,date_trunc('second',ts) as ts,from_ip \n"
        . " from (select * from prj_grp\n"
        . "  join all_prj_tutor using(prjtg_id) where prjm_id=$prjm_id) pg \n"
        . " join student st using(snummer)\n"
        . " join portrait p using(snummer)\n"
        . " left join (select * from project_task \n"
        . "where task_id=$task_id and prj_id=$prj_id) pt using(prj_id)\n"
        . " left join ( select * from project_task_completed_latest \n"
        . "where task_id=$task_id) ptc using(snummer) \n"
        . " natural left join transaction \n"
        . " order by grp_num,achternaam,roepnaam";

$myRowFactory = new MyRowFactory();
$tableBuilder = new TableBuilder($dbConn, $myRowFactory);
$taskTable = $tableBuilder->getTable($sql, 'snummer');
//$dbConn->log($sql);
//$page = new PageContainer();
//$page->setTitle('Record task completed');
pagehead('Record task completed');
$page_opening = "Record completion of tasks for project";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$nav->show();
?>
<div id='navmain' style='padding:1em;'>
    <?= $prjSel->getWidget() ?>
    <fieldset><legend>Select task</legend>
        <form method="post" name="activity" action="<?= $PHP_SELF; ?>">
            Select task: <input type='hidden' name='prjm_id' value='<?= $prjm_id ?>'/>
            <input type='hidden' name='task_id' value='<?= $task_id ?>'/>
            <?= $task_id_selector ?><input type='submit' name='sbm' value='Get'>
            <?= $taskTable ?>
            <input type='submit' name='bsubmit' value='submit'/>
        </form>
    </fieldset>
</div>
</body>
</html>
