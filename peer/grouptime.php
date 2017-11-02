<?php
include_once('peerutils.php');
include_once('tutorhelper.php');
include_once('documentfolders.php');
require_once('simplequerytable.php');
include_once 'navigation2.php';
require_once 'studentPrjMilestoneSelector.php';
$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
$grp_num = 1;
$prjtg_id = 1;
extract($_SESSION);
$judge = $snummer;
$prjSel = new StudentMilestoneSelector($dbConn, $judge, $prjm_id);
$prjSel->setExtraConstraint(" and prjm_id in (select distinct prjm_id from task_timer) ");
extract($prjSel->getSelectedData());
$_SESSION['prjtg_id'] = $prjtg_id;
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;
$_SESSION['grp_num'] = $grp_num;

$year = date('Y');
$month = date('m');
$sql = "select year,month,first_second,last_second from task_timer_year_month order by year desc,month desc limit 1";
$resultSet = $dbConn->Execute($sql);
extract($resultSet->fields);
$prj_id = 1;
$milestone = 1;
$grp_num = -1;
$afko = 'undef';
$csvout = 'N';
extract($_SESSION);
//echo "session $prj_id, $milestone</br>";
$prj_id_milestone = $prj_id . ':' . $milestone;
if (isSet($_REQUEST['year_month'])) {
    list($year, $month) = explode(':', validate($_REQUEST['year_month'], 'year_month', $year . ':' . $month));
}
// get first and last second for view
$sql = "select first_second,last_second from task_timer_year_month where year='$year' and month='$month'";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    echo 'Error: ' . $dbConn->ErrorMsg() . ' with <br/><pre>' . $sql . '</pre>';
}
if (!$resultSet->EOF) {
    extract($resultSet->fields);
} else {
    $first_second = date('Y-m-d h:i:s');
    $last_second = date('Y-m-30 h:i:s');
}
if (isSet($_REQUEST['csvout']))
    $csvout = ($_REQUEST['csvout'] == 'Y') ? 'Y' : 'N';
// echo "$prj_id, $milestone</br>";
// get group in this project/milestone
$sql = "select grp_num from prj_grp join all_prj_tutor using(prjtg_id) where snummer=$snummer and prjm_id=$prjm_id";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    echo 'Error: ' . $dbConn->ErrorMsg() . ' with <br/><pre>' . $sql . '</pre>';
}
if (!$resultSet->EOF) {
    extract($resultSet->fields);
} else {
    // this prj_id, milestone,student has no task_timers. grab any
    $sql = "select prj_id,milestone,grp_num from prj_grp join all_prj_tutor using(prjtg_id) where snummer=$snummer and prj_id>1 limit 1";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        echo 'Error: ' . $dbConn->ErrorMsg() . ' with <br/><pre>' . $sql . '</pre>';
    }
    if (!$resultSet->EOF) {
        extract($resultSet->fields);
        //	echo " after some real thinking: $prj_id, $milestone, $grp_num</br>";
    }
}

$_SESSION['prj_id'] = $prj_id;
$_SESSION['milestone'] = $milestone;
if ($csvout == 'Y') {
    $sql = " select afko from all_prj_tutor where prjm_id=$prjm_id \n" .
            "and grp_num ='$grp_num' ";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die("<br>Cannot get project name with \"" . $sql . '", cause ' . $dbConn->ErrorMsg() . "<br>");
    }
    if (!$resultSet->EOF)
        extract($resultSet->fields);
    $filename = trim($afko) . (($grp_num == '*') ? '_' : $grp_num) . 'm' . $milestone . '_timerecords_' . date('Y-m-d') . '.csv';

    $sqlt = "SELECT snummer,rtrim(roepnaam||coalesce(' '||tussenvoegsel,'')||' '||achternaam) as name, rtrim(afko) as afko,\n" .
            "milestone,rtrim(description) as description ,grp_num as group,task_id,rtrim(task_description) as task_description,\n" .
            "date_trunc('seconds',start_time) as start_time,date_trunc('seconds',stop_time) as stop_time,\n" .
            "from_ip,\n" .
            "date_trunc('seconds',time_tag) as time_tag,\n" .
            "case when start_time=time_tag then 'P' else 'Q' end as validity,\n" .
            "interval_to_hms(stop_time-start_time) as task_time,\n" .
            "extract(year from start_time) as year,\n" .
            "extract(month from start_time) as month\n" .
            "FROM task_timer \n".
            " join all_prj_tutor using(prjm_id,prj_id,milestone)\n".
            " join prj_grp using(snummer,prjtg_id)\n" .
            " join project_tasks using(snummer,prj_id,task_id)\n" .
            "join (select snummer,prjm_id,task_id,interval_to_hms(sum(stop_time-start_time)) \n" .
            "       as task_time from task_timer\n" .
            "  where start_time between '$first_second' and '$last_second' group by snummer,prjm_id,task_id ) " .
            "tts using(snummer,prjm_id,task_id)\n" .
            "join (select snummer,prjm_id,interval_to_hms(sum(stop_time-start_time)) \n" .
            "       as project_time from task_timer\n" .
            "  where start_time between '$first_second' and '$last_second' group by snummer,prjm_id) ttps using(snummer,prjm_id)\n" .
            "join (select prjm_id,grp_num,interval_to_hms(sum(stop_time-start_time)) \n" .
            "       as project_total from task_timer join prj_grp using(prjm_id,snummer)\n" .
            "  where start_time between '$first_second' and '$last_second' group by prjm_id,grp_num) ttgt using(prjm_id,grp_num)\n" .
            //    "join task_timer_grp_total ttgt using(prj_id,milestone,grp_num)\n".
            " join project using(prj_id) join student using(snummer)\n" .
            "order by achternaam,milestone,task_id";
    $dbConn->queryToCSV( $sqlt, $filename);
    exit(0);
}

$page = new PageContainer();
$page->setTitle('Group Time');
$page_opening = "Time spent on projects by $roepnaam $tussenvoegsel $achternaam ($snummer)";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
ob_start();
tutorHelper($dbConn, $isTutor);
$page->addBodyComponent(new Component(ob_get_clean()));
$page->addBodyComponent($nav);
ob_start();

$today = date('Y-m-d');
$page_opening = "Time records of the group(s) of $roepnaam $tussenvoegsel $achternaam ($snummer) on $today";
$sql = "SELECT distinct prj_id::text||':'||milestone::text as value,\n" .
        "afko::text||': '||description||', \n" .
        "milestone '||milestone::text||' ('||coalesce(project_time,'00:00:00'::interval)||')' as name,\n" .
        "prj_id,milestone,afko,description \n" .
        "from " .
        "all_prj_tutor p join prj_grp g using(prjtg_id) left join task_timer_group_sum using(prj_id,milestone,grp_num)\n" .
        "WHERE (prj_id,milestone,grp_num) in (select distinct prj_id,milestone,grp_num from prj_grp\n" .
        "where snummer=$snummer and prj_id > 1) order by afko";

$prjList = $prjSel->getSelector();
$sqltt = "SELECT snummer,roepnaam||coalesce(' '||tussenvoegsel,'')||' '||achternaam as name, afko,\n" .
        "milestone,description,grp_num as group,task_id,task_description,\n" .
        "date_trunc('seconds',start_time) as start_time,date_trunc('seconds',stop_time) as stop_time,\n" .
        "from_ip,\n" .
        "date_trunc('seconds',time_tag) as time_tag,\n" .
        "case when start_time=time_tag then 'P' else 'Q' end as validity,\n" .
        "stop_time-start_time as task_time,\n" .
        "tts.task_time as task_time_sum,\n" .
        "ttps.project_time as project_time,\n" .
        "ttgt.project_total as project_total,\n" .
        "extract(year from start_time) as year,\n" .
        "extract(month from start_time) as month\n" .
        "FROM task_timer join project_tasks using(snummer,prj_id,task_id)\n" .
        "join (select snummer,prj_id,milestone,task_id,sum(stop_time-start_time) \n" .
        "       as task_time from task_timer\n" .
        "  where start_time between '$first_second' and '$last_second' group by snummer,prj_id,milestone,task_id ) tts using(snummer,prj_id,milestone,task_id)\n" .
        "join (select snummer,prj_id,milestone,sum(stop_time-start_time) \n" .
        "       as project_time from task_timer\n" .
        "  where start_time between '$first_second' and '$last_second' group by snummer,prj_id,milestone) ttps using(snummer,prj_id,milestone)\n" .
        "join prj_grp using(snummer,prj_id,milestone)\n" .
        "join (select prj_id,milestone,grp_num,sum(stop_time-start_time) \n" .
        "       as project_total from task_timer join prj_grp using(prj_id,milestone,snummer)\n" .
        "  where start_time between '$first_second' and '$last_second' group by prj_id,milestone,grp_num) ttgt using(prj_id,milestone,grp_num)\n" .
        //    "join task_timer_grp_total ttgt using(prj_id,milestone,grp_num)\n".
        " join project using(prj_id) join student using(snummer)\n" .
        "where prj_id=$prj_id and milestone=$milestone and grp_num='$grp_num' and prj_id> 1\n" .
        " and task_timer.start_time between '$first_second' and '$last_second'\n" .
        "order by achternaam,milestone,task_id";
//echo "<pre>$sqltt</pre>\n";
//echo "$prj_id, $milestone,$grp_num</br>";
$sqlym = "select year_month as name,year||':'||month as value,year,month from task_timer_year_month order by year,month";
$yearMonthList = "<select name='year_month' onchange='submit()'>\n" .
        getOptionList($dbConn, $sqlym, $year . ':' . $month) .
        "</select>\n";
;
?>
<div id='content'>
    <form method='get' name='project' action='<?= $PHP_SELF; ?>'>
        <table>
            <tr><th>Project, milestone</th><td><?= $prjList ?>
                    <input type='hidden' name='peerdata' value='prj_id_milestone'/>
                    <span style='font-size:8pt;'>Project_id <?= $prj_id ?> milestone <?= $milestone ?> group <?= $grp_num ?></span></td></tr>
            <tr><th>For year, month</th><td> <?= $yearMonthList ?></td></tr>
            <tr><th>csv output (excel):<input type='checkbox' name='csvout' <?= (($csvout == 'Y') ? 'checked' : '') ?> value='Y'/></th>
                <td><input type='submit' name='bsubmit'/></td></tr>
        </table>
    </form>
    <a href='<?= $PHP_SELF ?>?csvout=Y' target='_blank'>csv file</a>
    <div class='navleft selected' style='padding:2em;'>
        <?php
        $resultSet = $dbConn->Execute($sqltt);
        if ($resultSet === false) {
            echo 'Error: ' . $dbConn->ErrorMsg() . ' with <br/><pre>' . $sqltt . '</pre>';
        }
        if (!$resultSet->EOF) {
            ?>
            <p>The time spent is counted towards the moth in which the task timer was started. If you insist on having the time accounted according to the
                calendar, insert a small break or other subtask in the active (non idle) task at the end of the month, crossing the month boundary eg 'xxxx-mm-31 23:59:59', duration '1 seconds'.</p>
            <table border='1' style='border-collapse:collapse;'>
                <thead>
                    <tr>
                        <th>Snummer</th>
                        <th>Name</th>
                        <th>Project</th>
                        <th>Task id</th>
                        <th>Task description</th>
                        <th>From ip</th>
                        <th>Year</th>
                        <th>Month</th>
                        <th>Start time</th>
                        <th>Stop time</th>
                        <th>Task time</th>
                        <th>Task Total</th>
                        <th>Personal Total</th>
                    </tr>
                </thead>
                <?php
                $rb = new RainBow(STARTCOLOR, COLORINCREMENT_RED, COLORINCREMENT_GREEN, COLORINCREMENT_BLUE);
                if (!$resultSet->EOF) {
                    extract($resultSet->fields);
                }
                $color = $rb->restart();
                $rowstyle = "style='background:$color'";
                while (!$resultSet->EOF) {
                    if (( $resultSet->fields['task_id'] != $task_id ) || ( $resultSet->fields['snummer'] != $snummer )) {
                        ?>
                        <tr>
                            <th <?= $rowstyle ?> colspan='11' align='left'>Task total for <?= $name ?> on <?= $afko ?> milestone <?= $milestone ?> task <?= $task_id ?>,<?= $task_description ?></th>
                            <th <?= $rowstyle ?>><?= $task_time_sum ?></th>
                            <td <?= $rowstyle ?>>&nbsp;</td>
                        </tr>
                        <?php
                    }
                    if ($resultSet->fields['snummer'] != $snummer) {
                        // display totals
                        ?>
                        <tr>
                            <th <?= $rowstyle ?> colspan='12' align='left'>Project total for <?= $name ?> on <?= $afko ?> milestone <?= $milestone ?></th>
                            <th <?= $rowstyle ?>><?= $project_time ?></th>
                        </tr>
                        <?php
                        // change color
                        $color = $rb->getNext();
                        $rowstyle = "style='background:$color'";
                    }
                    extract($resultSet->fields);
                    $validity_span = ( $validity == 'Q' ) ? '<span style=\'color:#800;text-decoration:underline;\' title=\'corrective entry made at ' .
                            $time_tag . ' from ' . $from_ip . '\'>' :
                            '<span>';
                    ?>
                    <tr>
                        <td <?= $rowstyle ?>><?= $snummer ?></td>
                        <td <?= $rowstyle ?>><?= $name ?></td>
                        <td <?= $rowstyle ?>><?= $afko ?></td>
                        <td <?= $rowstyle ?>><?= $task_id ?></td>
                        <td <?= $rowstyle ?>><?= $task_description ?></td>
                        <td <?= $rowstyle ?>><?= $from_ip ?></td>
                        <td <?= $rowstyle ?> align='right'><?= $year ?></td>
                        <td <?= $rowstyle ?> align='right'><?= $month ?></td>
                        <td <?= $rowstyle ?>><?= $validity_span ?><?= $start_time ?></span></td>
                        <td <?= $rowstyle ?>><?= $stop_time ?></td>
                        <td <?= $rowstyle ?> align='right'><?= $task_time ?></td>
                        <td <?= $rowstyle ?>>&nbsp;</td>
                        <td <?= $rowstyle ?>>&nbsp;</td>
                    </tr>
                    <?php
                    $resultSet->moveNext();
                }
                ?>
                <tr>
                    <th <?= $rowstyle ?> colspan='11' align='left'>Task total for <?= $name ?> on <?= $afko ?> milestone <?= $milestone ?> task <?= $task_id ?>, <?= $task_description ?></th>
                    <th <?= $rowstyle ?>><?= $task_time_sum ?></th>
                    <td <?= $rowstyle ?>>&nbsp;</td>
                </tr>
                <tr>
                    <th <?= $rowstyle ?> colspan='12' align='left'>Project total for <?= $name ?> on <?= $afko ?> milestone <?= $milestone ?></th>
                    <th <?= $rowstyle ?>><?= $project_time ?></th>
                </tr>
                <tr>
                    <th colspan='12' align='left'>Group total for group <?= $grp_num ?> on <?= $afko ?> milestone <?= $milestone ?></th>
                    <th><?= $project_total ?></th>
                </tr>
            </table>
            <?php
        } else {
            ?><p>Your set is empty</p><?php
    }
        ?>
        <hr/>
        <?php
        echo "<h2 class='normal'>Workers in this project $prj_id, $afko, $description, milestone  $milestone, group $grp_num</h2>\n";

        $sql = "select snummer as student_number,roepnaam||coalesce(' '||tussenvoegsel,'')||' '||achternaam as name,\n" .
                "'<a href=\'mailto:'||email1||'\'>'||email1||'</a>' as fontys_email,\n" .
                "'<a href=\'mailto:'||email2||'\'>'||email2||'</a>' as alt_email from student join prj_grp using(snummer) left join alt_email using(snummer)\n" .
                " where prj_id=$prj_id and milestone=$milestone and grp_num='$grp_num' order by achternaam";
        simpletable($dbConn, $sql, "<table border='1' style='cell-padding:.5em;border-collapse:collapse;'>\n");
        ?>

    </div>
</div>
<!-- $Id: grouptime.php 1825 2014-12-27 14:57:05Z hom $ -->
<!-- db_name=<?= $db_name ?> -->
<?php
$page->addBodyComponent(new Component(ob_get_clean()));
$page->show();
?>
