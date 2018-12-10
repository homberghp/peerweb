<?php
requireCap(CAP_SYSTEM);
if (isSet($_REQUEST['compact'])) {
    // try to join adjecent records with same snummer,project and task attributes
    // must be done in one transaction
    // first get potetential records
    $sql = "begin work;\n".
	"select snummer||':'||prj_id||':'||milestone||':'||task_id as task_timer_task,t1.id as t1_id,\n".
	"t2.id as t2_id,t1.start_time as start_time,\n".
	"t2.stop_time as stop_time from task_timer t1\n".
	" join task_timer t2 using(snummer,prj_id,milestone,task_id)\n".
	" where t1.stop_time=t2.start_time\n".
	"and t1.id <> t2.id order by start_time,task_timer_task;\n";
    $resultSet = $dbConn->Execute($sql);
    if ( $resultSet === false ) {
	echo "cannot get task timer data with <pre>$sql</pre>, error ".$dbConn->ErrorMsg();
	$resultSet = $dbConn->Execute("rollback");
    } else {
	$sql2='';
	$idlist=array();
	while ( !$resultSet->EOF ) {
	    extract( $resultSet->fields );
	    $first_start_time=$start_time;
	    $first_task_timer_id= $t1_id;
	    $idlist=array($t2_id);
	    // $stop_time is already extracted
	    $resultSet->moveNext(); // must do some sort of look ahead
	    while ( ( $task_timer_task == $resultSet->fields['task_timer_task'] ) && (!$resultSet->EOF) ) {
		$stop_time= $resultSet->fields['stop_time'] ;
		array_push($idlist ,$resultSet->fields['t1_id'],$resultSet->fields['t2_id']);
		$task_timer_task= $resultSet->fields['task_timer_task'];
		$resultSet->moveNext();
	    }
	    // collapse array into (1,..) list
	    $idlist_string='('.implode(",",$idlist).')';
	    $sql2 .= "delete from  task_timer where id in $idlist_string;\n".
		"update task_timer set stop_time='$stop_time' where id=$first_task_timer_id;\n";
	}
	//	echo $sql2;
	if ($sql2 != '') {
	    //	    $dbConn->logError($sql2);
	    $resultSet2= $dbConn->Execute($sql2);
	    if ( $resultSet === false ) {
		echo "cannot get task timer data with <pre>$sql\n$sql2</pre>, error ".$dbConn->ErrorMsg();
		$resultSet = $dbConn->Execute("rollback");
	    } else {
		$resultSet = $dbConn->Execute("commit");
	    }
	}
	//	echo "<pre>$sql\n$sql2</pre>\n";
    }
 } // end compact
header("Location: timebook.php");
