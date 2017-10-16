<?php
/* $Id: project_tasks.php 1723 2014-01-03 08:34:59Z hom $ */
include_once('peerutils.php');
include_once('tutorhelper.php');
include_once 'navigation2.php';
$task_prj_id_milestone = '1:1';
extract( $_SESSION );
$snummer = $peer_id; // this page is always personal
$sql = "select snummer,roepnaam,tussenvoegsel,achternaam,email1,email2 \n" .
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
  if ( $task_name != '' ) { // test for empty taks descriptions
    $new_task_id = $last_task_id + 1;
    $sql = "insert into project_tasks (prj_id,task_id,task_description,snummer) values($task_prj_id,$new_task_id,'$task_name',$snummer)";
    $resultSet = $dbConn->Execute( $sql );
    if ( $resultSet === false ) {
      echo('cannot add task: ' . $dbConn->ErrorMsg() . ' with ' . "<pre>$sql</pre>");
    }
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
$sql = "SELECT roepnaam, tussenvoegsel,achternaam,lang,email1,email2 FROM student left join alt_email using(snummer) WHERE snummer=$snummer";
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
$page_opening = "The project tasks of $roepnaam $tussenvoegsel $achternaam ($snummer)";
$page = new PageContainer();
$page->setTitle( 'Project tasks' );
$nav = new Navigation( $navtable, basename( $PHP_SELF ), $page_opening );
$nav->setInterestMap( $tabInterestCount );
$nav->addLeftNavText( file_get_contents( 'news.html' ) );
ob_start();
tutorHelper( $dbConn, $isTutor );
$page->addBodyComponent( new Component( ob_get_clean() ) );
$page->addBodyComponent( $nav );
ob_start();
?>
<div id="content">
  <div style='padding: 2em;'>
    <h2 class='normal'>Defined project task defined by you</h2>
    <fieldset><legend>My defined tasks</legend>
      <p>Each user in this application can use a personal task timer. This task timer can be used to record time spent on
        tasks. To use the task timers you must define at least one task. The personal tasks are visible only to you. Task time 
        recorded on behalf of group work can be viewed by group members as well. To be able to use this facility, 
        you must enable it for you by pressing the next clock symbol: 
        <a href='<?= $PHP_SELF ?>?tasktimer_set=t' title='enable task_timers' style='text-decoration:none;'>
          <img src='<?= IMAGEROOT ?>/stopwatch.png' alt='clock' border='0'/></a> </p>

      <p>Here you control your personal tasks. You can use a generic personal project or one of the projects 
        that you participate in. Note that the tasks you define apply to all past, current and future milestones.</p>
      <form name='newtask' method='post' action='<?= $PHP_SELF ?>'>
        <table border='0' frame='box' rules='group' summary='your defined tasks'>
          <thead><tr><th>Project</th><th>Task description</th><th>id</th></tr></thead>
          <?php
          $sql = "select afko||':'||description as project ,prj_id,task_id,task_description\n" .
                  "from project_tasks join project using(prj_id)\n" .
                  "where snummer=$snummer order by prj_id,task_id";
          $row = 0;
          $resultSet = $dbConn->Execute( $sql );
          if ( $resultSet === false ) {
            echo "cannot get task timer data with <pre>$sql</pre>, error " . $dbConn->ErrorMsg();
          }
          while ( !$resultSet->EOF ) {
            extract( $resultSet->fields );
            echo "\t<tr><td>$project</td>\n" .
            "\t\t<td><input type='text' name='task_description[]' value='$task_description' size='40' title='short (sub) task decription'/></td>\n" .
            "\t\t<td align='right'>$prj_id:$task_id</td>" .
            "<td align='center'><button type='submit' name='update_task_description' " .
            "value='$row'>Update</button><input type='hidden' name='update_task[]' value='" . $prj_id . ':' . $task_id . ':' . $row . "'/></td>\n" .
            "</tr>\n";
            $resultSet->moveNext();
            $row++;
          }
          $sql = "select distinct afko||'('||year||'):'||description as name, prj_id||':'||milestone as value "
                  . " from prj_grp join all_prj_tutor using(prjtg_id) where snummer=$snummer";
          $prj_selector = "<select name='task_prj_id_milestone'>" . getOptionList( $dbConn, $sql, $task_prj_id_milestone ) . "</select>";
          ?>
          <thead><tr><td><?= $prj_selector ?></td>
              <td><input type='text' name='task_name' value='' size='40'/></td>
              <td align='right'>&nbsp;</td>
              <td align='center'><button type='submit' name='newtask' value='new'>new</button></td></tr></thead>
        </table>
      </form>
    </fieldset>
  </div>
</div>
<!-- db_name=<?= $db_name ?> -->
<?php
$page->addBodyComponent( new Component( ob_get_clean() ) );
$page->show();
?>
