<?php
/**
 * presents a page that allows a time record to be spilt and attaached to other projects
 */
include_once('./peerlib/peerutils.inc');
include_once('tutorhelper.inc');
require_once('tasktimer.inc');
if ( !isSet( $startend ) ) {
  $startend = 'start';
}
if ( isSet( $_REQUEST['startend'] ) ) {
  if ( $_REQUEST['startend'] == 'end' )
    $startend = 'end';
}
extract( $_SESSION );
$script = "
/*
* refresh parent page on close
*/
function bye(){
  opener.focus();
  opener.location.reload();
  self.close();
}
/* split */
function splitter(task_timer_id,new_task_timer_id) {
  window.open('timesplitter.php?task_timer_id='+task_timer_id+'&new_task_timer_id='+new_task_timer_id,'_blank','width=800,height=600,scrollbars')
}
";
pagehead( 'Time splitter.', $script );

//echo "<pre>";print_r($_REQUEST);echo "</pre>\n";
$new_task_timer_id = -1;
if ( isSet( $_REQUEST['task_timer_id'] ) ) {
  $task_timer_id = validate( $_REQUEST['task_timer_id'], 'integer', 0 );
}
// now get the task (which should fit split_time)
$sql = "select prj_id, milestone,prjm_id,task_id,start_time, stop_time from task_timer\n" .
        "  where id=$task_timer_id";
$resultSet = $dbConn->Execute( $sql );
if ( $resultSet === false ) {
  echo "cannot get task timer data with <pre>$sql</pre>, error " . $dbConn->ErrorMsg();
}
if ( !$resultSet->EOF ) {
  extract( $resultSet->fields );
}
//echo "<pre>";print_r($resultSet->fields);echo "</pre>\n";


if ( isSet( $_REQUEST['head_task'] ) ) {
  $head_task = validate( $_REQUEST['head_task'], 'prj_task_id', '1:1:0' );
} else {
  $head_task = $prj_id . ':' . $milestone . ':' . $task_id;
}
list($head_project, $head_milestone, $head_task_id) = explode( ':', $head_task );

if ( isSet( $_REQUEST['tail_task'] ) ) {
  $tail_task = validate( $_REQUEST['tail_task'], 'prj_task_id', '1:1:0' );
} else {
  $tail_task = $prj_id . ':' . $milestone . ':' . $task_id;
}
list($tail_project, $tail_milestone, $tail_task_id) = explode( ':', $tail_task );

if ( isSet( $_REQUEST['split_time'] ) ) {
  $split_time = validate( $_REQUEST['split_time'], 'timestamp', 0 );
} else {
  $split_time = $stop_time;
}


// simple lexicographic compare works?
if ( ($split_time < $start_time) || ( $split_time > $stop_time ) ) {
  $split_time = $stop_time; // out of range 
  //    echo " $split_time out of range<br/>";
}


$sql = "select snummer,prj_id, afko,description,milestone, task_id,task_description,\n" .
        "start_time,stop_time,stop_time-start_time as record_length,\n" .
        "(stop_time-start_time)::interval as pre_split,\n" .
        "'0.0'::interval as post_split\n" .
        "from task_timer join project_tasks using(snummer,prj_id,task_id) \n" .
        "join project using(prj_id) where id=$task_timer_id";
$resultSet = $dbConn->Execute( $sql );
if ( $resultSet === false ) {
  echo "cannot get task timer data with <pre>$sql</pre>, error " . $dbConn->ErrorMsg();
}
if ( !$resultSet->EOF ) {
  extract( $resultSet->fields );
}
$remote_address = $_SERVER['REMOTE_ADDR'];
if ( isSet( $_REQUEST['commit'] ) ) {
  //  apply split
  // get new task_timer_id handle
  if ( isSet( $task_timer_id ) && ( $task_timer_id > 0) &&
          isSet( $start_time ) && isSet( $stop_time ) && isSet( $split_time ) &&
          ( $start_time != 0 ) && ( $stop_time != 0 ) && ( $split_time != 0 ) ) {
    if ( $start_time == $split_time ) {
      $sql = "begin work;\n" .
              "update task_timer set prj_id=$tail_project,milestone=$tail_milestone,task_id=$tail_task_id where id=$task_timer_id;";
    } else if ( $stop_time == $split_time ) {
      $sql = "begin work;\n" .
              "update task_timer set prj_id=$head_project,milestone=$head_milestone,task_id=$head_task_id where id=$task_timer_id;";
    } else {
      $sql = "begin work;\n" .
              "update task_timer set prj_id=$head_project, milestone=$head_milestone, \n" .
              "\ttask_id=$head_task_id, stop_time='$split_time' where id=$task_timer_id;" .
              "insert into task_timer ( snummer, prj_id, milestone, task_id, start_time, stop_time,from_ip,prjm_id )\n" .
              "values ( $peer_id, $tail_project, $tail_milestone, $tail_task_id, '$split_time', '$stop_time','$remote_address',$prjm_id );";
      //	    echo "<pre>$sql</pre>\n";
    }
    $resultSet = $dbConn->Execute( $sql );
    if ( $resultSet === false ) {
      echo "cannot update task timer with <pre>$sql</pre>, error " . $dbConn->ErrorMsg();
      $dbConn->Execute( "rollback" );
    } else {
      $dbConn->Execute( "commit" );
    }
  }
}

if ( isSet( $split_time ) && ($split_time != 0 ) ) {
  // compute the new head and tail lengths from split_time

  $sql = "select start_time,('$split_time'::timestamp - start_time) as pre_split,\n" .
          " (stop_time-'$split_time'::timestamp) as post_split,stop_time from task_timer where id=$task_timer_id";
  $resultSet = $dbConn->Execute( $sql );
  if ( $resultSet === false ) {
    echo "cannot get task timer data with <pre>$sql</pre>, error " . $dbConn->ErrorMsg();
    $split_time = $stop_time;
  }
  if ( !$resultSet->EOF )
    extract( $resultSet->fields );
  else
    die( 'barst' );
}

// get task_id selectors
$sqltt = "select distinct rtrim(afko)||':M'||milestone||':'||rtrim(task_description) as name\n" .
        ", prj_id||':'||milestone||':'||task_id as value,prj_id,milestone,task_id\n" .
        "from project_tasks join all_prj_tutor using(prj_id) join prj_grp using(snummer,prjtg_id) where snummer=$snummer\n" .
        "order by prj_id,milestone,task_id";
$head_task_sel = "<select name='head_task' title='select first head task'>" . getOptionList( $dbConn, $sqltt, $head_task ) . "</select>";
$tail_task_sel = "<select name='tail_task' title='select second (tail) task'>" . getOptionList( $dbConn, $sqltt,
                $tail_task ) . "</select>";
?>
<div class='navopening'>
  <h3 class='normal' align='center'>Split your time
    <button onClick='javascript:bye()'>Close</button>
  </h3>
</div>
<div style='padding:0px 5px'>
  <p>It is easier than splitting atoms.<img src='<?= IMAGEROOT ?>/tongue_n.gif' alt=':-)' border='0'></p>
  <p>You can correct your time bookkeeping in that you split up records in two records and than set a new task to one or both parts. Note that the task(s) 
    you want to use for the new parts has to be defined beforehand.
    You can enter the split point with minutes precision. The format of the split time is <i>yyy-mm-dd hh:mm:ss</i>.</p>

  <p>You are splitting record <?= $task_timer_id ?>, for <i>project <?= $afko ?>: <?= $description ?></i>. Its task_id is <?= $task_id ?>, <i><?= $task_description ?></i>.
    The start and stop times are <?= $start_time ?> and <?= $stop_time ?></p>

  <p>The length of the task record you are splitting up is <span style='color:#080;'><?= $record_length ?></span>.<br/>
    Values outside the range of the start and end time are silently ignored. To make the splitting effective (and stored), the start and end task must be different.</p>

  <form name='timesplittertry' method='GET' action='<?= $PHP_SELF ?>'>
    <input type='hidden' name='task_timer_id' value='<?= $task_timer_id ?>'/>
    <input type='hidden' name='new_task_timer_id' value='-1'/>
    <table border='3' style='empty-cells:show;border-collapse:3d' rules='groups' frame='box' width='100%'>
      <thead>
        <tr>
          <th class='theadleft'></th>
          <th class='theadmid'>start/split/end</th>
          <th class='theadmid'>Task</th>
          <th class='theadright'>Length</th>
        </tr>
      </thead>
      <tr>
        <th>Start</th>
        <td align='right'><?= $start_time ?></td>
        <td><?= $head_task_sel ?></td>
        <td  align='right'><?= $pre_split ?></td>
      </tr>
      <tr>
        <th>Split at</th>
        <td><input type='text' name='split_time' align='right' size='20' value='<?= $split_time ?>'></td>
      </tr>
      <tr>
        <th>End</th>
        <td align='right'><?= $stop_time ?></td>
        <td><?= $tail_task_sel ?></td>
        <td align='right'><?= $post_split ?></td>
      </tr>
      <tr><th colspan='3' align='right'>Sum</th><th align='right'><?= $record_length ?></th></tr>
      <thead>
        <tr>
          <th colspan='1' class='theadleft'><button type='submit' name='try' value='try'>try</button></th>
          <th class='theadmid'  colspan='2'></th>
          <th class='theadright'></th>
        </tr>
      </thead>
    </table>
    <?php
// echo '2=>'.$task_timer_id.'+'.$new_task_timer_id.'='.($task_timer_id+$new_task_timer_id).'<br/>';
    if ( $new_task_timer_id > 0 ) {
      $sql = "select prj_id,afko,description,task_id,task_description,\n" .
              "start_time,stop_time,\n" .
              "(stop_time-start_time) as record_length,id\n" .
              "from task_timer join project_tasks using (prj_id,task_id,snummer) join project using(prj_id)\n" .
              "where id in ($task_timer_id,$new_task_timer_id) and snummer=$snummer order by start_time, id";
    } else {
      $sql = "select prj_id,afko,description,task_id,task_description,\n" .
              "'$start_time'::timestamp as start_time,'$split_time'::timestamp as stop_time,\n" .
              "('$split_time'::timestamp-'$start_time'::timestamp) as record_length,\n" .
              "$task_timer_id as id\n" .
              "from project_tasks join project using(prj_id)\n" .
              "\t where snummer=$snummer and prj_id=$head_project and task_id=$head_task_id\n" .
              "union\n" .
              "select prj_id,afko,description,task_id,task_description,\n" .
              "'$split_time'::timestamp as start_time,'$stop_time'::timestamp as stop_time,\n" .
              "('$stop_time'::timestamp-'$split_time'::timestamp) as record_length, $new_task_timer_id as id\n" .
              "from project_tasks join project using(prj_id)\n" .
              "\twhere snummer=$snummer and prj_id=$tail_project and task_id=$tail_task_id\n" .
              "order by start_time,id";
    }
//    echo "<pre>$sql</pre>\n";
    $resultSet = $dbConn->Execute( $sql );
    if ( $resultSet === false ) {
      echo "cannot get task timer data with <pre>$sql</pre>, error " . $dbConn->ErrorMsg();
      $split_time = $stop_time;
    } else {
      ?>
      <table border='3' style='empty-cells:show;border-collapse:3d' rules='groups' frame='box' width='100%'>
        <thead>
          <tr>
            <th class='theadleft'>&nbsp;</th>
            <th class='theadmid' colspan='4' align='center'>The above would result in:</th>
            <th class='theadright'>&nbsp;</th>
          </tr>
          <tr>
            <th  class='theadleft'>start time</th>
            <th class='theadmid'>stop time</th>
            <th class='theadmid'>Project</th>
            <th colspan='2' class='theadmid'>Projecttask</th>
            <th class='theadright'>length</th>
        <?php if ( $new_task_timer_id > 0 ) { ?><th>Split</th><?php } ?>
          </tr>
        </thead>
        <?php
        $st1 = 'style=\'background:none\'';
        $st2 = 'style=\'background:white\'';
        $counter = 0;
        while ( !$resultSet->EOF ) {
          extract( $resultSet->fields );
          if ( $counter == 0 ) {
            $task_timer_id = $id;
          } else if ( $id >= 0 ) {
            $new_task_timer_id = $id;
            $split_time = $start_time;
          }
          if ( $record_length != '00:00:00' ) {
            echo "<tr>" .
            "<td $st1 align='right'>$start_time</td>\n" .
            "<td $st2 align='right'>$stop_time</td>" .
            "<td $st1>$afko</td>\n" .
            "<td $st2 align='right'>$task_id</td>" .
            "<td $st1>$task_description</td>" .
            "<td $st2 align='right'>$record_length</td>\n";
            if ( false && $id >= 0 && $new_task_timer_id > 0 )
              echo "<td  $st1>\n\t<a href='javascript:splitter($id,-1)'\n" .
              "title='split this record'>\n" .
              "\n\t\t<img src='images/scissors.png' border='0'></a></td>\n";
            echo "</tr>\n";
          } else {
            $new_task_timer_id = $task_timer_id;
          }
          $resultSet->moveNext();
          $counter++;
        }
        //echo 'end=>'.$task_timer_id.'+'.$new_task_timer_id.'='.($task_timer_id+$new_task_timer_id).'<br/>';
        //echo "$head_task, $tail_task, $task_timer_id, $new_task_timer_id, $split_time <br/>\n";
        ?>
        <thead>
          <tr>
            <th class='theadleft'>&nbsp;</th>
            <th colspan='4'  class='theadmid'>
              If this is what you want, press the scissors:<img src='images/scissors.png' border='0'>.<br/>
              To view the result, close <button onClick='javascript:bye()'>Close</button> this page, which will refresh the parent page.
            </th>

            <th class='theadright'>&nbsp;<button type='submit' name='commit' value='commit'><img src='images/scissors.png' border='0'></button></th>
          </tr>
        </thead>
      </table>
  <?php
}
?>
  </form>
</div>
<!-- <?= $db_name ?>
     $Id: timesplitter.php 1210 2012-05-05 21:09:43Z hom $
-->
</body>
</html>