<?php

requireCap( CAP_TUTOR );
require_once('querytotable.php');
require_once('navigation2.php');
require_once 'prjMilestoneSelector2.php';
$warn_members = false;
$prj_id = $milestone = 1;

$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
extract( $_SESSION );

$prjSel = new PrjMilestoneSelector2( $dbConn, $peer_id, $prjm_id );
extract( $prjSel->getSelectedData() );
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;

$doctype_set = array( );

if ( isSet( $_POST['update'] ) && isSet( $_POST['upload_description'] ) ) {
  //    echo"<pre>"; print_r($_POST); echo "</pre>\n";
  $sql = "begin work;\n";
  // "delete from project_deliverables where prj_id=$prj_id\n".
  // "and milestone=$milestone;\n";

  $duedate = date( 'Y-m-d', mktime() + 28 * 86400 ); // 28 days of seconds;
  $rowCount = $_POST['rowcount'];
  $postRow = 0;
  for ( $i = 0; $i < $rowCount; $i++ ) {
    $cVersionLimit = $_POST['version_limit'][$i];
    $description = $_POST['upload_description'][$i];
    $doctype = $_POST['doctype'][$i];
    $url = $_POST['url'][$i];
    $publish_early = isSet( $_POST['publish_early_' . $postRow] ) ? 'true' : 'false';
    $indiv_group = $_POST['indiv_group'][$i];
    if ( ( $cVersionLimit != '' ) && isSet( $doctype ) ) {
      $cDue = $_POST['due'][$i];
      $cDue = ( preg_match( "/^\d{4}-\d{2}-\d{2}$/", $cDue ) ) ? $cDue : $duedate;
      $rightsG = isSet( $_POST['rights_' . $i . '_G'] ) ? 't' : 'f';
      $rightsP = isSet( $_POST['rights_' . $i . '_P'] ) ? 't' : 'f';
      $rightsW = isSet( $_POST['rights_' . $i . '_W'] ) ? 't' : 'f';
      $warn_members = isSet( $_POST['warn_members_' . $i] ) ? 'true' : 'false';
      $newRights = '\'{' . "$rightsG,$rightsP,$rightsW" . '}\'';
      $sql .="insert into project_deliverables (doctype,version_limit,due,publish_early,rights,prjm_id)\n"
              . "\t select $doctype,$cVersionLimit,'$cDue',$publish_early,$newRights,$prjm_id"
              . " where ($prjm_id,$doctype) not in (select prjm_id,doctype from project_deliverables) ;\n"
              . "update project_deliverables set version_limit=$cVersionLimit,due='$cDue',publish_early=$publish_early,rights=$newRights\n"
              . " where prjm_id=$prjm_id and doctype=$doctype;"
              . "update uploaddocumenttypes set description='$description',url='$url',warn_members=$warn_members,indiv_group='$indiv_group' "
              . "where prj_id=$prj_id and doctype=$doctype;\n";
    }
    $postRow++;
  }

  $sql .= "commit";
  //$dbConn->log($sql);
  $resultSet = $dbConn->Execute( $sql );
  if ( $resultSet === false ) {
    die( "<br>Cannot set doctype types with <pre>" . $sql . "</pre> reason " . $dbConn->ErrorMsg() . "<br>" );
  }
}

if ( isSet( $_POST['baddtype'] ) ) {
  $description = pg_escape_string( $_POST['upload_description'] );
  $warn_members = isSet( $_POST['warn_members'] ) ? 'true' : 'false';
  $sql = "select nextval('doctype_id_seq')";
  $resultSet = $dbConn->execute( $sql );
  if ( $resultSet === false ) {
    die( "<br>Cannot get sequence next value with " . $dbConn->ErrorMsg() . "<br>" );
  }
  $id = $resultSet->fields['nextval'];
  $sql = "insert into uploaddocumenttypes (doctype,description,prj_id,warn_members) values ('$id','$description',$prj_id,$warn_members)";
  //    echo "<pre>$sql</pre>";
  $resultSet = $dbConn->execute( $sql );
  if ( $resultSet === false ) {
    echo "<br>Cannot insert doctype types with " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>";
  }
}
// always get actual db document set
$sql = "select doctype,version_limit from project_deliverables where prjm_id=$prjm_id order by doctype";
$resultSet = $dbConn->Execute( $sql );
if ( $resultSet === false ) {
  die( "<br>Cannot get document types with " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>" );
}
$i = 0;
$doctype_set = array( );
while ( !$resultSet->EOF ) {
  extract( $resultSet->fields );
  array_push( $doctype_set, $doctype );
  $resultSet->moveNext();
}
//echo "<pre>doctype_set \n";print_r($doctype_set);echo "</pre>\n";
$pp = array( );
pagehead2( 'Define types of deliverables students can upload.', file_get_contents( '../templates/simpledatepicker.html' ) );

$page_opening = "Define the types of deliverables students can upload per milestones in a project. " .
        "<font style='font-size:6pt'>prj_id=$prj_id milestone=$milestone</font>";

$page = new PageContainer();
$page->setTitle( 'Define types of deliverables students can upload.' );
$nav = new Navigation( $tutor_navtable, basename( __FILE__ ), $page_opening );
$page->addBodyComponent( $nav );

$prjSel->setJoin( 'milestone_grp using (prj_id,milestone)' );

$pp['prj_id_selector'] = $prjSel->getSelector();
$sql = "select  distinct udt.description as upload_description,udt.url,coalesce(version_limit,0) as version_limit," .
        "due, pd.publish_early,udt.doctype,pd.rights[0:2],warn_members,indiv_group\n" .
        "\tfrom uploaddocumenttypes udt left join " .
        "( select * from project_deliverables join prj_milestone using(prjm_id) where prjm_id=$prjm_id ) pd \n" .
        "\tusing(prj_id,doctype)\n" .
        "\twhere prj_id=$prj_id order by due,udt.doctype";
// echo "<pre>$sql</pre>\n";
$inputColumns = array( '0' => array( 'type' => 'T', 'size' => '40' ),
    '1' => array( 'type' => 'U', 'size' => '40' ),
    //		     '' => array( 'type' => 'C', 'size' => '1'),
    '2' => array( 'type' => 'N', 'size' => '3' ),
    '3' => array( 'type' => 'D', 'size' => '10' ),
    '4' => array( 'type' => 'B', 'size' => '1', 'colname' => 'publish_early' ),
    '5' => array( 'type' => 'H', 'size' => '0' ),
    '6' => array( 'type' => 'R', 'size' => '2', 'rightsChars' => 'GPW' ), // groupmembed,projectparticipants, world
    '7' => array( 'type' => 'B', 'size' => '1', 'colname' => 'warn_members' ),
    '8' => array( 'type' => 'Z', 'size' => 1,
        'options' => array( 'Ind' => 'I', 'Grp' => 'G' ) ),
);
$pp['checked'] = ($warn_members == 'true') ? 'checked' : '';
$pp['prj_id'] = $prj_id;
$pp['milestone'] = $milestone;

$datePickers = array( );

$page->addScriptResource( 'js/jquery.min.js' );
$page->addScriptResource( 'js/jquery-ui.custom.min.js' );

$pp['rtable'] = getQueryToTableChecked2( $dbConn, $sql, true, -1, new RainBow(), 'doctype[]', $doctype_set,
        $inputColumns );

if ( count( $datePickers ) > 0 ) {
  foreach ( $datePickers as $dp ) {
    $page->addJqueryFragment( "$('#" . $dp . "').datepicker(dpoptions);" );
  }
}
$pp['prjSelectionDetails'] = $prjSel->getSelectionDetails();
$page->addHtmlFragment( '../templates/defuploadtype.html', $pp );
$page->show();
?>