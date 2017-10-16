<?php
require_once('peerutils.php');
$sql="select prj_id||':'||milestone||':'||task_id as task_timer from task_timer where \n".
    "snummer=$peer_id and \n".
    "start_time=(select max(start_time) from task_timer where snummer=$peer_id)";

$resultSet = $dbConn->Execute($sql);
if ($resultSet=== false) {
    echo ('Error: '.$dbConn->ErrorMsg().' with <pre>'.$sql."</pre>\n");
 }
if (!$resultSet->EOF) {
    extract($resultSet->fields);
    $_SESSION['task_timer']=$task_timer;
 } else {
    $_SESSION['task_timer']='1:1:0';
 }

$remote_address=$_SERVER['REMOTE_ADDR'];
$peer_id=$_SESSION['peer_id'];
if (isSet($_REQUEST['tasktimer_set'])) {
    // test if this user has a task_timer;
    // test if this user has a running idle task
    // if not, create idle task (add to project 0, 
    if ($_REQUEST['tasktimer_set'] == 'pause') {
	// try to retrieve previous task
	// by getting the last but one record.
	$sql = "select prj_id||':'||milestone||':'||task_id as task_timer_task, start_time \n".
	    "from task_timer where snummer=$peer_id order by start_time desc limit 2";
	$resultSet=$dbConn->Execute($sql);
	if ($resultSet=== false) {
	    echo ('Error: '.$dbConn->ErrorMsg().' with <pre>'.$sql."</pre>\n");
	}
	$_SESSION['task_timer']='1:1:0'; // default for pause button
	if (!$resultSet->EOF) {
	    extract( $resultSet->fields );
	    if ( $task_timer_task =='1:1:0' ) {
		$resultSet->moveNext(); // skip last time if it was the pause task
		if (!$resultSet->EOF) {
		    $_SESSION['task_timer'] = $resultSet->fields['task_timer_task'];
		}
	    } 
	}
    } else if (isSet($_REQUEST['task_timer'])) {
	$_SESSION['task_timer']=$_REQUEST['task_timer'];
    } else {
	$_SESSION['task_timer']='1:1:0';
    }
    //    echo "SESSION task timer".$_SESSION['task_timer']."<br/>\n";
    $sql = "select prj_id,milestone,task_id \n".
	"from task_timer join all_prj_tutor using(prj_id) join prj_grp using(snummer,prjtg_id) where snummer=$peer_id and prj_id=1";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet=== false) {
	echo ('Error: '.$dbConn->ErrorMsg().' with <pre>'.$sql."</pre>\n");
    }
    if ($resultSet->EOF) { // No null task, create one and start it
	// insert prj_grp entry, project_task for snummer and task_timer, with start and stop time set to now.
	$dbConn->transactionStart("begin work");
	$dbConn->doSilent("delete from prj_grp where snummer=$peer_id and prj_id=1;\n");
	$dbConn->doSilent("insert into prj_grp (prj_id,snummer,grp_num) values(1,$peer_id,0)");
	$dbConn->doSilent(	    "delete from project_tasks where snummer=$peer_id and prj_id=1");
	$dbConn->doSilent(	    "insert into project_tasks (prj_id,task_id,task_description,snummer)\n".
				    " values(1,0,'idle',$peer_id)");
	$dbConn->doSilent(	    "insert into task_timer  (snummer,prj_id,milestone,task_id,from_ip) values".
				    " ($peer_id,1,1,0,'$remote_address')");
	$dbConn->transactionEnd();
    }
    // get the task_timer with the latest start_time. That is the one that is open
    $sql="select prj_id as ott_prj_id,milestone as ott_milestone,task_id as ott_task_id ,start_time\n".
	"from task_timer where snummer=$peer_id and start_time=\n".
	"(select max(start_time) from task_timer where snummer=$peer_id)";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet=== false) {
	echo ('Cannot create idle task '.$dbConn->ErrorMsg().' with <pre>'.$sql."</pre>\n");
    }
    if(!$resultSet->EOF) {
	extract($resultSet->fields);
	list($ntt_prj_id,$ntt_milestone,$ntt_task_id) = split(':',$_SESSION['task_timer']);
	// if old and new task are same, simply update stoptime
	// else stop old task and insert new record for new time
	if ( ( $ott_prj_id != $ntt_prj_id ) || ($ott_milestone != $ntt_milestone ) 
	     || ( $ott_task_id != $ntt_task_id ) ) {
	    // new task
	    //	echo "new task ( $ott_prj_id != $ntt_prj_id ) 
	    // || ($ott_milestone != $ntt_milestone ) || ( $ott_task_id != $ntt_task_id ) $start_time<br/>\n";
	    
	    $sql ="begin work;\n".
		"update task_timer set stop_time=date_trunc('seconds',now()) where prj_id=$ott_prj_id and\n".
		" milestone=$ott_milestone and task_id=$ott_task_id and start_time='$start_time';\n".
		"insert into task_timer (snummer,prj_id,milestone,task_id,from_ip)\n\t".
		"values ($peer_id,$ntt_prj_id,$ntt_milestone,$ntt_task_id,'$remote_address');".
		"commit";
	} else {
	    // same task
	    //	echo "same task<br/>\n";
	    $sql = "update task_timer set stop_time=date_trunc('seconds',now()) where prj_id=$ott_prj_id and\n".
		" milestone=$ott_milestone and task_id=$ott_task_id and start_time='$start_time'";
	}
	$resultSet = $dbConn->Execute($sql);
	if ($resultSet=== false) {
	    echo ('could not set task_timer: '.$dbConn->ErrorMsg().' with <pre>'.$sql."</pre><br/>\n");
	}
    }
 }
function taskTimer($peer_id) {
    echo taskTimerText($peer_id);
}
/**
 * tasktimer html string to insert on pages
 * sideeffect: invoking this functions updates the active task_timer.
 */
function taskTimerText( $peer_id ) {//taskTimerText
    global $dbConn;
    global $PHP_SELF;
    global $database_time;
    $result='';
    $task_time='00:00:00';
    $sqltt="select distinct rtrim(afko)||':M'||milestone||':'||rtrim(task_description) as name\n".
	", prj_id||':'||milestone||':'||task_id as value,prj_id,milestone,task_id\n".
	"from project_tasks join all_prj_tutor using(prj_id) join prj_grp using(snummer,prjtg_id) where snummer=$peer_id\n".
        "order by prj_id,milestone,task_id";
    $resultSettt = $dbConn->Execute($sqltt);
    $show_timer=false;
    if ($resultSettt=== false) {
	echo('tt Error: '.$dbConn->ErrorMsg().' with <pre>'.$sql."</pre>\n");
    }
    if (!$resultSettt->EOF) {
	$show_timer=true;
	$sql ="update task_timer set stop_time=date_trunc('seconds',now()) \n".
	    "where snummer=$peer_id and start_time = \n".
	    "(select max(start_time) from task_timer where snummer=$peer_id)";
	$resultSet = $dbConn->Execute($sql);
    }
    $sql = "select prj_id as ott_prj_id,milestone as ott_milestone,task_id as ott_task_id,start_time,\n ".
	"date_trunc('seconds',now()) as current_time\n".
	"from task_timer where snummer=$peer_id and start_time = \n".
	"(select max(start_time) from task_timer where snummer=$peer_id)";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet=== false) {
	echo ('Cannot get current timer task:<br/> '.$dbConn->ErrorMsg().' with <pre>'.$sql."</pre>\n");
    }
    if (!$resultSet->EOF) {
	extract($resultSet->fields);
	$sql="select date_trunc('seconds',stop_time-start_time) as task_time,\n".
	    "prj_id||':'||milestone||':'||task_id as task_timer_task \n".
	    " from task_timer\n".
	    "where snummer=$peer_id and prj_id=$ott_prj_id and\n".
	    " milestone=$ott_milestone and task_id=$ott_task_id and start_time='$start_time'";
	$resultSet = $dbConn->Execute($sql);
	if ($resultSet=== false) {
	    echo('tt Error: '.$dbConn->ErrorMsg().' with '.$sql);
	}
	extract($resultSet->fields);
    } else {
	$ott_prj_id=1;
	$ott_milestone=1;
	$ott_task_id=0;
    }
    if (isSet($task_timer_task)) {
	if ($task_timer_task=='1:1:0' ) { 
	    $pause_title='resume previous task';
	    $pause_image_background='style=\'background:red\''; 
	} else  {
	    $pause_image_background='';
	    $pause_title='pause task, go idle';
	}
	if ( $show_timer ) {
	    $result= "<form action='$PHP_SELF' method='get' name='tasktimer' class='nav'>\n".
		"<b title='This is your personal task timer. Use it at your own discretion.".
		" Look in the settings page for more...'>Task</b>\n".
		"<select name='task_timer' title='select task to time'>".
		getOptionList($dbConn,$sqltt,$_SESSION['task_timer'])."</select>\n".
		"<button type='submit' name='tasktimer_set' style='background:none;border-width:0;'".
		" title='start/stop task timer' value='new_task'><img src='".IMAGEROOT."/player_play.png'".
		" alt='start/stop/lap'/>".
		"</button>&nbsp;<b>$task_time</b>".
		"<a href='$PHP_SELF?tasktimer_set=pause' title='$pause_title' ".
		"style='text-decoration:none; background:inherit;'>".
		"&nbsp;&nbsp;<img src='".IMAGEROOT."/player_pause.png' border='0' ".
		"alt='stopsign' $pause_image_background/></a>" .
		" <b>DB time = $database_time</b>".
		"</form>\n";
	} else {
	}
    }
    return $result;
}

?>