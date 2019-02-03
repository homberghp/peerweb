<?php
/* $Id: rolesetter.php 1825 2014-12-27 14:57:05Z hom $ */
requireCap(CAP_SYSTEM);

include_once 'tutorhelper.php';
include_once 'navigation2.php';
require_once 'studentPrjMilestoneSelector.php';
$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
$prjtg_id = 1;
extract( $_SESSION );

$prjSel = new StudentMilestoneSelector( $dbConn, $judge, $prjtg_id );
extract( $prjSel->getSelectedData() );
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;
$_SESSION['grp_num'] = $grp_num;
$_SESSION['prjtg_id'] = $prjtg_id;
$may_change = hasStudentCap2( $snummer, CAP_SET_STUDENT_ROLE, $prjm_id );

if ( $may_change && isSet( $_REQUEST['submit_roles'] ) ) {
  $memberset = implode( ",", $_REQUEST['actor'] );

  // first put roles in a map
  $role_map = array( );
  for ( $i = 0; $i < count( $_REQUEST['actor'] ); $i++ ) {
    $actor = $_REQUEST['actor'][$i];
    $rolenum = $_REQUEST['rolenum'][$i];
    $role_map[$actor] = $rolenum;
  }
  //$dbConn->transactionStart( "begin work" );
  $resultSet = $dbConn->Execute( "select snummer as actor,rolenum \n" .
          "from prj_grp join prj_tutor using(prjtg_id)" .
          "left join student_role using( snummer, prjm_id )\n" .
          " where prjm_id=$prjm_id\n" .
          " and snummer in ($memberset)" );
  $sql = '';
  $querys= array();
  while ( !$resultSet->EOF ) {
    extract( $resultSet->fields );
    $rolenum = $role_map[$actor];
    if ( isSet( $resultSet->fields['rolenum'] ) ) {
      $queries[] = "update student_role set rolenum=$rolenum where prjm_id=$prjm_id and snummer=$actor;\n";
    } else {
      $queries[] = "insert into student_role (snummer,rolenum,prjm_id)\n" .
              "\t values($actor,$rolenum,$prjm_id);\n";
    }
    $resultSet->moveNext();
  }
  //    $dbConn->log($sql);
  $affected_rows = $dbConn->executeQueryList( $queries );
}
$sql = "select snummer,roepnaam,tussenvoegsel,achternaam,email1 \n" .
        "from student left join alt_email using(snummer) where snummer=$snummer";
$resultSet = $dbConn->Execute( $sql );
if ( $resultSet === false ) {
  die( 'Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql );
}
extract( $resultSet->fields );
extract( $resultSet->fields, EXTR_PREFIX_ALL, 'stud' );

$page_opening = "Roles in project groups";
$page = new PageContainer();
$page->setTitle( 'Set peer roles in projects' );
$nav = new Navigation( $tutor_navtable, basename( $PHP_SELF ), $page_opening );
$nav->setInterestMap( $tabInterestCount );

$nav->addLeftNavText( file_get_contents( 'news.html' ) );
ob_start();
tutorHelper( $dbConn, $isTutor );
$page->addBodyComponent( new Component( ob_get_clean() ) );
$page->addBodyComponent( $nav );
ob_start();
$prjList = $prjSel->getSelector();

$sql = "select pm.prj_id,s.snummer as actor, rtrim(achternaam) as achternaam,rtrim(tussenvoegsel) as tussenvoegsel,\n" .
        "rtrim(roepnaam) as roepnaam, nationaliteit,hoofdgrp as class,alias,f.faculty_short as faculty,\n" .
        "course_description,role as current_role,sr.rolenum,pr.capabilities as capabilities\n" .
        " from prj_grp pg join student s using(snummer)\n" .
        " join prj_tutor pt on (pt.prjtg_id=pg.prjtg_id) join prj_milestone pm on(pt.prjm_id=pm.prjm_id)\n" .
        " left join fontys_course on (opl=course)\n" .
        " join faculty f on(s.faculty_id=f.faculty_id)\n " .
        " left join student_role sr on(sr.snummer=s.snummer and pt.prjm_id=sr.prjm_id)\n" .
        " left join project_roles pr on(pr.prj_id=pm.prj_id and pr.rolenum=sr.rolenum) " .
        " left join grp_alias ga on(ga.prjtg_id=pt.prjtg_id)" .
        " where (pg.prjtg_id) in (select prjtg_id \n" .
        " from prj_grp pga join prj_tutor pta using(prjtg_id) where pga.snummer=$snummer and pta.prjm_id=$prjm_id)\n " .
        " order by pm.prj_id,achternaam,roepnaam";
$resultSet = $dbConn->Execute( $sql );
if ( $resultSet === false ) {
  echo("cannot excute <pre>\n$sql\n</pre>, cause " . $dbConn->ErrorMsg());
}
extract( $resultSet->fields );
$dbConn->log( $sql );

$sql3 = "select role as name, rolenum as value from project_roles where prj_id=$prj_id";
$resultSet3 = $dbConn->Execute( $sql3 );
//$dbConn->log( $sql3 );
?>
<div style='padding:1em'>
  <form method='post' name='project' action='<?= $PHP_SELF; ?>'>
    <h2 class='normal'>
      <b>Project, milestone</b>: <?= $prjList ?><input type='submit' value='Get'>
      <input type='hidden' name='peerdata' value='$prjm_id'/>
      Project_id <?= $prj_id ?> group <?= $grp_num ?>:"<?= $alias ?>" milestone <?= $milestone ?> prjm_id=<?= $prjm_id ?>
    </h2>
  </form>

  <fieldset><legend>These are the roles of the project peers</legend>
    <h2 align='center'>Roles in <?= $afko ?> <?= $year ?>: <?= $description ?> 
      <br/>group <?= $grp_num ?> (<?= $alias ?>) prjtg_id <?= $prjtg_id ?></h2>
    <form name='setroles' method='post' action='<?= $PHP_SELF ?>'>
      <table summary='student roles' frame='box' border='3' style='border-collapse:3D'>
        <tr>
          <th>snumber</th>
          <th>Name</th>
          <th>Nat.</th>
          <th>Faculty</th>
          <th>Course</th>
          <th>Class</th>
          <th>Current role</th>
          <th>Cap</th>
          <?php
          if ( $may_change ) {
            echo "<th>New role</th>\n";
          }
          ?>
        </tr>
        <?php
        while ( !$resultSet->EOF ) {
          extract( $resultSet->fields );
          $course_description = $course_description;
          $role_input = "<select name='rolenum[]'>\n" .
                  getOptionListFromResultSet( $resultSet3, $rolenum ) .
                  "</select>\n";
          echo "<tr>\n" .
          "\t\t<td>$actor</td>\n" .
          "\t\t<td><input type='hidden' name='actor[]' value='$actor'/>$roepnaam $tussenvoegsel $achternaam</td>\n" .
          "\t\t<td>$nationaliteit</td>\n" .
          "\t\t<td>$faculty</td>\n" .
          "\t\t<td>$course_description</td>\n" .
          "\t\t<td>$class</td>\n" .
          "\t\t<td>$current_role</td>\n" .
          "\t\t<td>$capabilities</td>\n";
          if ( $may_change ) {
            echo "\t\t<td>" . $role_input . "</td>\n";
          }
          echo "</tr>\n";
          $resultSet->moveNext();
        }
        ?>
      </table>
      <?php
      if ( $may_change ) {
        ?><input type='reset'/> To update roles press <input type='submit' name='submit_roles'/><?php
    }
      ?>
    </form>
  </fieldset>
  <fieldset><legend>Tablecards for this group</legend>
    <table>
      <tr><td>
          <form method="POST" name="tablecard" target="_blank" action="grouptablecards.php">
            <input type="hidden" name="prjtg_id" value="<?= $prjtg_id ?>"/>
            You can make tablecards for this group by pressing this <button type="submit">button</button> <br/>
            The layout of the tablecard wil be like this:<br/>
            <table border='0' style='background:white'>
              <tr><th colspan='2'><?= $roepnaam . ' ' . $tussenvoegsel . ' ' . $achternaam ?></th></tr>
              <tr><td colspan='2'>Role in the project</td></tr>
              <tr><td width='20%'>Groupname</td><td>Major course descr.</td></tr>
            </table>
            You can set your own values for the fields 'Role in the project', 'Groupname' and <br/>'Major course description below'.<br/>
            The default values are taken from the database.<br/>
            <table>
              <tr><td>Role</td><td><input name='frole' value='<?= $frole ?>'/></td></tr>
              <tr><td>Groupname</td><td><input name='fgrpname' value='<?= $fgrpname ?>'/></td></tr>
              <tr><td>Major</td><td><input name='fcourse' value='<?= $fcourse ?>'/></td></tr>
            </table>
          </form>
        </td><td><img src='images/vouwvoorbeeld.png' alt='fold example'/></td></tr>

    </table>
  </fieldset>
</div>
<!-- db_name=<?= $db_name ?> -->
<?php
$page->addBodyComponent( new Component( ob_get_clean() ) );
$page->show();
?>
