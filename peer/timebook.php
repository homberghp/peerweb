<?php
/* $Id: timebook.php 1761 2014-05-24 13:17:31Z hom $ */
include_once('./peerlib/peerutils.php');
include_once('tutorhelper.php');
include_once 'navigation2.php';
$start_time = date( 'Y-m-d' ) . ' 08:45:00'; // defaults to schooltime start
$duration = '00:45:00'; // one hour
$task_prj_id_milestone = '1:1';
extract( $_SESSION );
if ( isSet( $_SESSION['task_timer'] ) )
  $new_task_timer = $task_timer;
$snummer = $peer_id; // this page is always personal
$sql = "select snummer,roepnaam,voorvoegsel,achternaam,email1,email2 \n" .
        "from student left join alt_email using(snummer) where snummer=$snummer";
$resultSet = $dbConn->Execute( $sql );
if ( $resultSet === false ) {
  die( 'Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql );
}
extract( $resultSet->fields );
if ( !isSet( $_SESSION['timeorder'] ) ) {
  $timeorder = 'N';
}
if ( isSet( $_REQUEST['timeorder'] ) ) {
  $_SESSION['timeorder'] = $timeorder = ($_REQUEST['timeorder'] == 'Y') ? 'Y' : 'N';
}

if ( isSet( $_REQUEST['newtask'] ) ) {
  $task_name = pg_escape_string( $_REQUEST['task_name'] );
  $task_prj_id_milestone = validate( $_REQUEST['task_prj_id_milestone'], 'prj_id_milestone', '1:1' );
  list($task_prj_id, $task_milestone) = explode( ':', $task_prj_id_milestone );
  $sql = "select max(task_id) as last_task_id from project_tasks where snummer=$snummer and prj_id=$task_prj_id";
  $resultSet = $dbConn->Execute( $sql );
  if ( $resultSet === false ) {
    die( 'Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql );
  }
  extract( $resultSet->fields );
  if ( !isSet( $last_task_id ) )
    $last_task_id = 0;
  $new_task_id = $last_task_id + 1;
  $sql = "insert into project_tasks (prj_id,task_id,task_description,snummer) values($task_prj_id,$new_task_id,'$task_name',$snummer)";
  $resultSet = $dbConn->Execute( $sql );
  if ( $resultSet === false ) {
    echo('cannot add task: ' . $dbConn->ErrorMsg() . ' with ' . "<pre>$sql</pre>");
  }
}
if ( isSet( $_REQUEST['update_task_description'] ) ) {
  $row = validate( $_REQUEST['update_task_description'], 'integer', 0 );
  list($task_prj, $task_id, $row) = explode( ':', validate( $_REQUEST['update_task'][$row], 'prj_task_id', '0:0:0' ) );
  $task_description = pg_escape_string( $_REQUEST['task_description'][$row] );
  $sql = "update project_tasks set task_description='$task_description' where snummer=$snummer and prj_id=$task_prj and task_id=$task_id";
  $resultSet = $dbConn->Execute( $sql );
  if ( $resultSet === false ) {
    echo('cannot update task_description: ' . $dbConn->ErrorMsg() . ' with ' . "<pre>$sql</pre>");
  }
}
if ( isSet( $_REQUEST['btasktime'] ) ) {
  $start_time = validate( $_REQUEST['start_time'], 'timestamp', $start_time );
  $duration = validate( $_REQUEST['duration'], 'duration', $duration );
  $new_task_timer = $_REQUEST['new_task_timer'];
  list($prj_id, $milestone, $task_id) = explode( ':', $new_task_timer );
// build query
  $remote_address = $_SERVER['REMOTE_ADDR'];

  $dbConn->transactionStart( "begin" );
  $newTaskCompleted = false;
  $resultSet = $dbConn->doSilent( "select * from task_timer where snummer=$snummer\n" .
          " and start_time='$start_time'\n" .
          " and stop_time='$start_time'::timestamp+'$duration'::interval" );
  if ( !$resultSet->EOF ) { // complete fit
    $id = $resultSet->fields['id'];
//	$dbConn->log("exact fit");
    $dbConn->doSilent( "update task_timer set prj_id=$prj_id,milestone=$milestone,task_id=$task_id where id=$id" );
    $dbConn->transactionEnd();
    $newTaskCompleted = true;
  }
  if ( !$newTaskCompleted ) {
// try fit to head
    $resultSet = $dbConn->doSilent( "select id from task_timer where snummer=$snummer\n" .
            " and start_time='$start_time' and \n" .
            "stop_time > '$start_time'::timestamp+'$duration'::interval" );
    if ( !$resultSet->EOF ) { // update 'old' tail insert new head and 
      extract( $resultSet->fields );
//	    $dbConn->log("fit head");
// original is in tail
      $resultSet = $dbConn->doSilent( "update task_timer set start_time='$start_time'::timestamp+'$duration'::interval where id=$id" );
      $resultSet = $dbConn->doSilent( "insert into task_timer (snummer,prj_id,milestone,task_id,start_time,stop_time,from_ip)\n" .
              " values ( $snummer,$prj_id,$milestone,$task_id,\n" .
              "          '$start_time','$start_time'::timestamp+'$duration'::interval,'$remote_address')" );
      $dbConn->transactionEnd();
      $newTaskCompleted = true;
    }
  }
  if ( !$newTaskCompleted ) {
// try fit to tail	
    $resultSet = $dbConn->doSilent( "select id from task_timer where snummer=$snummer\n" .
            " and start_time < '$start_time' and \n" .
            " stop_time = '$start_time'::timestamp+'$duration'::interval" );
    if ( !$resultSet->EOF ) { // update 'old' head an insert new tail
      extract( $resultSet->fields );
//	    $dbConn->log("fit tail");
// original is in head
      $resultSet = $dbConn->doSilent( "update task_timer set stop_time='$start_time' where id=$id" );
      $resultSet = $dbConn->doSilent( "insert into task_timer (snummer,prj_id,milestone,task_id,start_time,stop_time,from_ip)\n" .
              " values ( $snummer,$prj_id,$milestone,$task_id,\n" .
              "          '$start_time','$start_time'::timestamp+'$duration'::interval,'$remote_address')" );
      $dbConn->transactionEnd();
      $newTaskCompleted = true;
    }
  }

  if ( !$newTaskCompleted ) {
// try three some --x---x--- 
    $resultSet = $dbConn->doSilent( $sql = "select id,prj_id as org_prj_id, milestone as org_milestone, task_id as org_task_id,\n" .
            " stop_time as org_stop_time \n" .
            " from task_timer where snummer=$snummer\n" .
            " and start_time < '$start_time' and \n" .
            " stop_time > '$start_time'::timestamp+'$duration'::interval" );
//	$dbConn->log("threesome\n $sql");
    if ( !$resultSet->EOF ) { // update 'old' head, insert new mid and tail
      extract( $resultSet->fields );
//	    $dbConn->log("threesome");
// update head
      $resultSet = $dbConn->doSilent( "update task_timer set stop_time='$start_time' where id=$id\n" );
// insert mid
      $resultSet = $dbConn->doSilent( "insert into task_timer (snummer,prj_id,milestone,task_id,start_time,stop_time,from_ip)\n" .
              " values ( $snummer,$prj_id,$milestone,$task_id,\n" .
              "          '$start_time','$start_time'::timestamp+'$duration'::interval,'$remote_address')\n" );
// insert tail, org task_timer data
      $resultSet = $dbConn->doSilent( "insert into task_timer (snummer,prj_id,milestone,task_id,start_time,stop_time,from_ip)\n" .
              " values ( $snummer,$org_prj_id,$org_milestone,$org_task_id,\n" .
              "          '$start_time'::timestamp+'$duration'::interval,'$org_stop_time','$remote_address')" );

      $dbConn->transactionEnd();
      $newTaskCompleted = true;
    }
  }
  if ( !$newTaskCompleted ) {
// chop chop, start in one task_timer, stop in some other. 
    $resultSet = $dbConn->doSilent( "select id,prj_id as org_prj_id, milestone as org_milestone, task_id as org_task_id,\n" .
            " stop_time as org_stop_time \n" .
            " from task_timer where snummer=$snummer\n" .
            " and '$start_time' between start_time and stop_time \n" );
    if ( !$resultSet->EOF ) { // chop task_timer with head up, give it a new tail
      extract( $resultSet->fields );
//	    $dbConn->log("chop chop");
      $resultSet = $dbConn->doSilent( "update task_timer set stop_time='$start_time' where id=$id\n" );
      $resultSet = $dbConn->doSilent( "insert into task_timer (snummer,prj_id,milestone,task_id,start_time,stop_time,from_ip)\n" .
              " values ( $snummer,$prj_id,$milestone,$task_id,\n" .
              "          '$start_time','$org_stop_time','$remote_address')\n" );
    }
// chop tail
    $resultSet = $dbConn->doSilent( "select id,prj_id as org_prj_id, milestone as org_milestone, task_id as org_task_id,\n" .
            " start_time as org_start_time, \n" .
            " '$start_time'::timestamp+'$duration'::interval as stop_time" .
            " from task_timer where snummer=$snummer\n" .
            " and '$start_time'::timestamp+'$duration'::interval between start_time and stop_time \n" );
    if ( !$resultSet->EOF ) { // get task_timer with tail up, give it a new head
      extract( $resultSet->fields );
// possible specail case $org_stop_time=$stop_time
      if ( $stop_time == $org_stop_time ) {
        $resultSet = $dbConn->doSilent( "update task_timer set prj_id=$prj_id,milestone=$milestone,task_id=$task_id where id=$id\n" );
      } else { // chop and update
        $resultSet = $dbConn->doSilent( "update task_timer set start_time='$start_time'::timestamp+'$duration'::interval where id=$id\n" );
        $resultSet = $dbConn->doSilent( "insert into task_timer (snummer,prj_id,milestone,task_id,start_time,stop_time,from_ip)\n" .
                " values ( $snummer,$prj_id,$milestone,$task_id,\n" .
                "          '$org_start_time','$stop_time','$remote_address')\n" );
      }
    }
// update everything in between
    $resultSet = $dbConn->doSilent( "update task_timer set prj_id=$prj_id, milestone=$milestone, task_id=$task_id\n" .
            " where snummer=$snummer \n" .
            " and (start_time between '$start_time' and '$start_time'::timestamp+'$duration'::interval)\n" .
            " and (stop_time  between '$start_time' and '$start_time'::timestamp+'$duration'::interval)" );
    if ( !$resultSet !== false ) {
      $newTaskCompleted = true;
    }
    $dbConn->transactionEnd();
  }
}
$sql = "SELECT roepnaam, voorvoegsel,achternaam,lang,email1,email2 FROM student left join alt_email using(snummer) WHERE snummer=$snummer";
$resultSet = $dbConn->Execute( $sql );
if ( $resultSet === false ) {
  die( 'Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql );
}
$lang = strtolower( $resultSet->fields['lang'] );
$email1 = $resultSet->fields['email1'];
if ( isSet( $resultSet->fields['email2'] ) ) {
  $email2 = $resultSet->fields['email2'];
} else
  $email2 = '';
extract( $resultSet->fields, EXTR_PREFIX_ALL, 'stud' );
$page_opening = "Settings/time book-keeping for $roepnaam $voorvoegsel $achternaam ($snummer)";
$page = new PageContainer();
$page->setTitle( 'Personal settings and time book-keeping' );

$script = "function splitter(task_timer_id,new_task_timer_id) {\n" .
        "window.open('timesplitter.php?task_timer_id='+task_timer_id+'&new_task_timer_id='+new_task_timer_id,'_blank','width=800,height=670,scrollbars')" .
        "}";

$scriptContainer = new HtmlContainer( "<script id='tasktimerstarter' type='text/javascript'>" );
$scriptContainer->add( new Component( $script ) );
$page->addHeadComponent( $scriptContainer );

$nav = new Navigation( $tutor_navtable, basename( $PHP_SELF ), $page_opening );
$nav->setInterestMap( $tabInterestCount );
$nav->addLeftNavText( file_get_contents( 'news.html' ) );
ob_start();
tutorHelper( $dbConn, $isTutor );
$page->addBodyComponent( new Component( ob_get_clean() ) );
$page->addBodyComponent( $nav );
ob_start();
$sqltt = "select distinct rtrim(afko)||':M'||milestone||':'||rtrim(task_description) as name\n" .
        ", prj_id||':'||milestone||':'||task_id as value,prj_id,milestone,task_id\n" .
        "from project_tasks join all_prj_tutor using(prj_id) join prj_grp using(snummer,prjtg_id) where snummer=$peer_id\n" .
        "order by prj_id,milestone,task_id";
$taskSelector = "\n<select name='new_task_timer' title='select task to time'>\n" .
        getOptionList( $dbConn, $sqltt, $new_task_timer )
        . "\n</select>\n";
$timebookTable = "";
$sql = "select afko as project,description as project_title ,task_description,task_id,id as task_timer_id,prj_id,milestone,\n" .
        "to_char(start_time,'YYYY-MM-DD HH24:MI:SS')::text as start_time,\n" .
        "to_char(stop_time,'YYYY-MM-DD HH24:MI:SS')::text as stop_time,\n" .
        "from_ip,\n" .
        "date_trunc('seconds',stop_time-start_time) as time_diff,\n" .
        "tsum.task_time as total_time,\n" .
        "psum.project_time as project_time,\n" .
        "time_tag,\n" .
        "case when start_time=time_tag then 'P' else 'Q' end as validity,\n" .
        "extract(month from start_time) as month\n" .
        "from project_tasks join project using(prj_id)\n" .
        "join task_timer using (snummer,prj_id,task_id) \n" .
        "join task_timer_sum tsum using (snummer,prj_id,milestone,task_id)\n" .
        "join task_timer_project_sum psum using (snummer,prj_id,milestone)\n" .
        "where snummer=$snummer\n";
if ( $timeorder == 'Y' ) {
  $sql .=" order by start_time desc";
} else {
  $sql .=" order by prj_id desc, milestone desc,task_id,start_time desc";
}
//echo $sql;
//$dbConn->log($sql);
$resultSet = $dbConn->Execute( $sql );
if ( $resultSet === false ) {
  $timebookTable = "cannot get task timer data with <pre>$sql</pre>, error " . $dbConn->ErrorMsg();
  die( "help" );
}
$hasTimeBook = !$resultSet->EOF;
if ( $hasTimeBook ) {
  extract( $resultSet->fields );
  $styles = array( 'style=\'background:#EFE;\'', 'style=\'background:#FEE;\'', 'style=\'background:#EEF;\'' );
  $stylecounter = 0;
  // initialize data for loop
  $rowcontinue = '';
  $rowcounter = 0;
  while ( !$resultSet->EOF ) {
    $joinCell = '';
    if ( $timeorder == 'N' ) {
      if ( (( $resultSet->fields['prj_id'] != $prj_id )
              || ($resultSet->fields['milestone'] != $milestone ))
              || ($resultSet->fields['task_id'] != $task_id )
      ) {
        $timebookTable .= "\t<tr><th $styles[$stylecounter] colspan='6' " .
                "align='left'>Total for $project: $project_title, milestone $milestone/$task_description </th>" .
                "<th $styles[$stylecounter] " .
                "align='right'>$total_time</th><td $styles[$stylecounter]>&nbsp;</td>" .
                "<td $styles[$stylecounter]>&nbsp;</td></tr>\n";
      }
      if ( ( $resultSet->fields['prj_id'] != $prj_id )
              || ($resultSet->fields['milestone'] != $milestone )
      ) {
        $timebookTable .= "\t<tr><th $styles[$stylecounter] colspan='7' align='left'>" .
                "Total for project $project: $project_title, milestone $milestone</th>" .
                "<th $styles[$stylecounter] align='right' colspan='1'>$project_time</th>" .
                "<th $styles[$stylecounter]>&nbsp;</th></tr>\n";
        $stylecounter = (++$stylecounter) % count( $styles );
      }
      if (
              ( $resultSet->fields['prj_id'] == $prj_id ) &&
              ( $resultSet->fields['milestone'] == $milestone ) &&
              ( $resultSet->fields['task_id'] == $task_id ) &&
              ( $resultSet->fields['stop_time'] == $start_time ) &&
              $rowcounter > 0
      ) { // joinable
        $joinCell = '<td rowspan=\'2\' class=\'joinable\'>&nbsp</td>';
      }
    } else {
      if ( (( $resultSet->fields['prj_id'] != $prj_id )
              || ($resultSet->fields['milestone'] != $milestone ))
              || ($resultSet->fields['task_id'] != $task_id ) ) {
        $stylecounter = (++$stylecounter) % count( $styles );
      }
    }
    extract( $resultSet->fields );
    //$host = gethostbyaddr( $from_ip );
    $host = $from_ip;
    $validity_span = ( $validity == 'Q' ) ? '<span style=\'color:#800;text-decoration:underline;\' title=\'corrective entry made at ' .
            $time_tag . ' from ' . $host . '\'>' :
            '<span>';
    $timebookTable .= "\n\t" . $joinCell . $rowcontinue . "\n<tr>\n" .
            "\t<td $styles[$stylecounter] title='$project_title'>$project</td>\n" .
            "\t<td $styles[$stylecounter]>$milestone</td><td $styles[$stylecounter]>$task_description</td>\n" .
            "\t<td $styles[$stylecounter]>$host</td>\n" .
            "\t<td $styles[$stylecounter]>$validity_span $start_time </span></td>\n" .
            "\t<td $styles[$stylecounter]>$stop_time</td><td $styles[$stylecounter] align='right'>$time_diff</td>\n" .
            "\t<td $styles[$stylecounter]>&nbsp;</td>\n" .
            "\t<td $styles[$stylecounter]><a href='javascript:splitter($task_timer_id)'  title='split this record'>\n" .
            "\t\t<img src='images/scissors.png' border='0' alt='scissors'/></a></td>\n</tr>\n";
    $resultSet->moveNext();
    $rowcounter++;
  }
  $joinCell = '';
  // remaining totals
  $timebookTable .= "\t<tr><th $styles[$stylecounter] colspan='6' align='left'>\n" .
          "Total for $project: $project_title, milestone $milestone/$task_description</th>" .
          "<th $styles[$stylecounter] align='right'>$total_time</th><td $styles[$stylecounter]></td>" .
          "<td $styles[$stylecounter]>&nbsp;</td></tr>\n";
  $timebookTable .= "\t<tr><th $styles[$stylecounter] colspan='7' align='left'>" .
          "Total for project $project: $project_title, milestone $milestone</th>" .
          "<th $styles[$stylecounter] align='right' colspan='1'>$project_time</th><td $styles[$stylecounter]>" .
          "</td><td></td></tr>\n";
  $timebookTable .= "</table>";
  $rowcontinue = '</tr>';
}
?>
<div id="content">
  <div style='padding: 2em;'>
    <h3>Your timebook keeping</h3>
    <p>Here is your time bookkeeping.</p>
    <p>The principle of these task timers is that time always increase and never stops. What you can do is assign the
      passing of time to certain task. This means that all the assignments per day will alway add up to 24 hours. No cheating
      like doouble booking is possible.</p>
    <p>
      You can correct your time bookkeeping for those cases where you forgot to change the task at the appropriate moment.
      You can click on the scissors in the left most column. It will open a popup (you must have popup blocking off) 
      that allows you to cut a tasktimer into pieces and reallocate the time parts to differcent tasks.</p>
    <fieldset style='margin:0'><legend>Add task time entry</legend>
      <form id='newtasktimer' name='newtasktimer' method='get' action='<?= $PHP_SELF ?>'>
        <table><tr>
            <td width='50%'>
              <table>
                <tr>
                  <th>Task</th>
                  <td><?= $taskSelector ?></td>
                </tr>
                <tr><th>Start time (yyyy-mm-dd hh:mm:ss)</th>
                  <td><input type='text' name='start_time' value='<?= $start_time ?>' style='text-align:right' size='22'/></td></tr>
                <tr><th>Duration (hh:mm:ss)</th>
                  <td><input type='text' name='duration' value='<?= $duration ?>' style='text-align:right'size='10'/><input type='submit' name='btasktime' value='submit'/></td></tr>
              </table>
            </td>
            <td valign='top'><p>You can enter separate task times here. Just select the task and enter the starttime and duration.
                The data will be entered into your time book keeping, while maintaining the invariant that all times are head to tail and no time
                is accounted for twice.</p>
            </td>
          </tr>
        </table>
      </form >
    </fieldset>
    <?php if ( $hasTimeBook ) { ?>
      <fieldset style='margin:0'><legend>My personal time book-keeping</legend>
        <form id='timeorderform' name='timeorderform' method='get' action='<?= $PHP_SELF ?>'>
          <table><tr><td title='In reversed chronological order'>
                <input type='radio' name='timeorder' value='Y' onChange='timeorderform.submit()' <?= ($timeorder == 'Y') ? 'checked' : '' ?> />Time order</td>
              <td title='Summing per project,task'>
                <input type='radio' name='timeorder' value='N' onChange='timeorderform.submit()' <?= ($timeorder == 'N') ? 'checked' : '' ?> />Project order</td>
              <td><input type='submit'/></td>
            </tr></table>
        </form>
        <table border='1' frame='box' rules='group' summary='Time records'>
          <thead><tr><th>Project</th><th>mil</th><th>Task description</th><th>from ip</th><th>Start time</th><th>Stop time</th><th>Task time spent</th><th>Project time spent</th>
              <th>
                <form id='compactform' name='compactform' method='post' action='compacter.php'> 
                  <button name='compact' type='submit' value='compact' title='combine adjacent time_records of same task'>Compact</button>
                </form>
              </th>
              <th></th>
            </tr>
          </thead>
          <?= $timebookTable ?>
      </fieldset>
    <?php } ?>
  </div>
</div>
<!-- db_name=<?= $db_name ?> -->
<?php
$page->addBodyComponent( new Component( ob_get_clean() ) );
$page->show();
?>
