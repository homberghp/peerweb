<?php

include_once('peerutils.php');
require_once('navigation2.php');
include_once 'project_selector.php';
require_once 'TemplateWith.php';

requireCap( CAP_TUTOR );
$page_opening = 'Select or define a project ';
$afko = 'WHATFR';
$description = 'Refine me or catch flies';
$comment = '';
$year = date( 'Y' );
$prj_id = 0;
extract( $_SESSION );
$successReport = '';
$course = 112; // default inf
if ( date( 'm' ) < 07 ) {
  $year -=1;
}
if ( isSet( $_REQUEST['prj_id'] ) ) {
  $_SESSION['prj_id'] = $prj_id = $_REQUEST['prj_id'];
}

$tutor = $tutor_code;
//$dbConn->log($tutor_code);
if ( hasCap( CAP_SYSTEM ) && isSet( $_REQUEST['owner_id'] ) ) {
  $owner_id = validate( $_REQUEST['owner_i'], 'snummer', '879417' );
  $sql = "update project set owner_id='$owner_id' where prj_id=$prj_id";
  $resultSet = $dbConn->Execute( $sql );
}
if ( $validator_clearance ) {
  if ( isSet( $_POST['bsubmit'] ) ) {
    if ( isSet( $_POST['comment'] ) )
      $comment = pg_escape_string( $_POST['comment'] );
    else
      $comment = '';
    if ( $_POST['bsubmit'] == 'New' && isSet( $_POST['afko'] ) && ($_POST['afko'] != '')
            && isSet( $_POST['project_description'] ) && isSet( $_POST['year'] ) ) { // new
      $afko = trim( $_POST['afko'] );
      $year = $_POST['year'];
      $course = $_POST['course'];
      // test if uniq constraint not violated
      $sql = "select * from project where afko='$afko' and year=$year and course=$course";
      $resultSet = $dbConn->Execute( $sql );
      if ( $resultSet === false ) {
        die( "<br>Cannot get sequence next value with " . $dbConn->ErrorMsg() . "<br>" );
      }
      if ( $resultSet->EOF ) { // there is no such project already
        // get seq and insert data
        // first gt prj_id from sequence
        $sql = "select nextval('project_prj_id_seq')";
        $resultSet = $dbConn->Execute( $sql );
        if ( $resultSet === false ) {
          die( "<br>Cannot get sequence next value with " . $dbConn->ErrorMsg() . "<br>" );
        }
        $description = $_POST['project_description'];
        $prj_id = $resultSet->fields['nextval'];
        $sql = "begin work;\n"
                . "insert into project (prj_id,afko,description,year,comment,course,owner_id)\n"
                . " values( $prj_id,'$afko','$description',$year,'$comment',$course,$peer_id);\n"
                . "insert into prj_milestone (prj_id,milestone) values ($prj_id,1);\n";

        if ( isSet( $_POST['activity_project'] ) ) {
          $sql .="insert into activity_project select $prj_id where $prj_id not in (select prj_id from activity_project);\n";
        } else {
          $sql .="delete from activity_project where prj_id=$prj_id;\n";
        }

        $sql .= "commit";

        $resultSet = $dbConn->Execute( $sql );
        if ( $resultSet === false ) {
          die( "<br>Cannot set project values with " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>" );
        }
        // insert into session
        $_SESSION['prj_id'] = $prj_id;
        $successReport = '';
      } else {
        $successReport = "<h1 style='padding:.4em;color:white;background:#c00;font-weight:bold'>" .
                "Creating the new project failed. Please use unique abriviation and year.</h1><br/>";
      }
    } else if ( $_POST['bsubmit'] == 'Update' && isSet( $_POST['prj_id'] ) && isSet( $_POST['afko'] )
            && isSet( $_POST['project_description'] ) && isSet( $_POST['year'] ) ) { // new
      $description = $_POST['project_description'];
      $afko = trim( $_POST['afko'] );
      $year = $_POST['year'];
      $sql = "update project set prj_id=$prj_id, afko='$afko', description='$description',\n" .
              "year=$year, comment='$comment'\n" .
              "where prj_id=$prj_id and owner_id='$peer_id'";
      $resultSet = $dbConn->Execute( $sql );
      if ( $resultSet === false ) {
        die( "<br>Cannot update project values with " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>" );
      }
      $_SESSION['prj_id'] = $prj_id;
    }
  }

  //$prj_id= isSet($_SESSION['prj_id'])?$_SESSION['prj_id']:-1;
  $extra_constraint = "and owner_id=$peer_id";
  if ( hasCap( CAP_SYSTEM ) ) {
    $extra_constraint = '';
  }
  $sql = "select * from project where prj_id=$prj_id $extra_constraint";
  $resultSet = $dbConn->Execute( $sql );
  if ( $resultSet === false ) {
    die( "<br>Cannot project data with <pre>$sql</pre> " . $dbConn->ErrorMsg() . "<br>" );
  }
  if ( !$resultSet->EOF ) {
    extract( $resultSet->fields );
  } else {
    $sql = "select prj_id,rtrim(afko) as afko,description,owner_id,year,comment,valid_until" .
            " from project where prj_id > 0 $extra_constraint order by prj_id desc limit 1";
    $resultSet = $dbConn->Execute( $sql );
    if ( $resultSet === false ) {
      die( "<br>Cannot project data with <pre>$sql</pre> " . $dbConn->ErrorMsg() . "<br>" );
    }
    if ( !$resultSet->EOF ) {
      extract( $resultSet->fields );
    }
  }
} // validator clearance
else {
  extract( $_POST );
}

//echo "<pre>$sql</pre>\n";
$_SESSION['prj_id'] = $prj_id;
extract( getTutorOwnerData( $dbConn, $prj_id ) );
$isTutorOwner = ($tutor == $tutor_code);

$page = new PageContainer();
$page->setTitle( 'Peer assessment, define project' );
$nav = new Navigation( $tutor_navtable, basename( $PHP_SELF ), $page_opening );

$form1 = new HtmlContainer( "<div>" );
$input_module_code = "<input type='text' size='10' maxlength='10' class='" . $validator->validationClass( 'afko' ) . "' name='afko' value='$afko' title='Progress module code'/>";
$input_year = "<input type=text size='4' maxlength='4' align='right' name='year' class='" . $validator->validationClass( 'year' ) . "' value='$year' title='starting year of scollastic year' />";
$input_description = "<input type='text' size='30' maxlength='30' name='project_description' class='" . $validator->validationClass( 'project_description' ) . "' value='$description' title='module description in 30 characters'/>\n";
$input_valid_until = "<input type='text' maxlength='10' size='8' class='" . $validator->validationClass( 'valid_until' ) . "' " .
        "name='valid_until' id='embeddedPicker' value='$valid_until' title='Project entry to be used until. Date in yyyy-mm-dd format' style='text-align:right'/>\n";
$input_comment = "<textarea class='" . $validator->validationClass( 'comment' ) . "' name='comment' cols='72' rows='5'>$comment</textarea>\n";
$input_update_button = ($isTutorOwner) ? "<input type='submit' name='bsubmit'\n" .
        "value='Update' title='Use this to update project data for project_id=$prj_id' />" : '';
$input_course = "<select name='course' title='set base course'>\n" .
        getOptionListGrouped( $dbConn,
                "select trim(course_short)||':'||trim(course_description)||'('||course||')' as name,\n"
                . " course as value,\n"
                . " faculty_short as namegrp\n"
                . " from fontys_course fc natural join faculty f\n"
                . " order by namegrp,name"
                , $course );
$project_selector = getprojectSelector( $dbConn, $peer_id, $prj_id );

$sql = "select count(prj_id) as active_project_set from activity_project where prj_id=$prj_id";
$resultSet = $dbConn->Execute( $sql );
if ( $resultSet === false ) {
  die( "<br>Cannot activity_project data with <pre>$sql</pre> " . $dbConn->ErrorMsg() . "<br>" );
}
$activity_project_checked = $resultSet->fields['active_project_set'] ? 'checked' : '';

$input_activity_project = "<input type='checkbox' name='activity_project' value='set' $activity_project_checked/>";

$templatefile = 'templates/defproject.html';
$template_text = file_get_contents( $templatefile, true );
if ( $template_text === false ) {
  $form1Form->addText( "<strong>cannot read template file $templatefile</strong>" );
} else {
  $text = templateWith($template_text, get_defined_vars() );
  $form1->addText( $text );
}
$page->addBodyComponent( $nav );
$page->addBodyComponent( $form1 );
$page->addHeadText( file_get_contents( 'templates/simpledatepicker.html' ) );
$page->addScriptResource( 'js/jquery-1.7.1.min.js' );
$page->addScriptResource( 'js/jquery-ui-1.8.17.custom.min.js' );
$page->addJqueryFragment( '$(\'#embeddedPicker\').datepicker(dpoptions);' );
$page->show();
?>
