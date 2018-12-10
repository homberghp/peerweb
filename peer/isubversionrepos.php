<?php

requireCap(CAP_SUBVERSION);
require_once('validators.php');
include_once('navigation2.php');
require_once('conffileeditor2.php');
if ( $db_name == 'peer2' ) {
  $reposroot = '/home/svnpt';
} else {
  $reposroot = '/home/svnp';
}
$reposroot = '/home/svnp';

//function readDFile( $File ) {
//  if ( $fp2 = fopen( $File, 'r' ) ) {
//    while ( !feof( $fp2 ) ) {
//      $dline = trim( fgets( $fp2 ) );
//      $content .=$dline;
//    }
//    fclose( $fp2 );
//  }
//  return $content;
//}

define( 'MAXROW', '3' );
define( 'MAXCOL', '5' );
extract( $_SESSION );

if ( isSet( $snummer ) ) {
  $sql = "SELECT roepnaam, tussenvoegsel,achternaam,lang FROM student WHERE snummer=$snummer";
  $resultSet = $dbConn->Execute( $sql );
  if ( $resultSet === false ) {
    die( 'Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql );
  }
  extract( $resultSet->fields );
}
$page = new PageContainer();
$pageTitle = "Subversion repositories for $roepnaam $tussenvoegsel $achternaam ($snummer)";
$page->setTitle($pageTitle);
ob_start();
tutorHelper( $dbConn, $isTutor );
$page->addBodyComponent( new Component( ob_get_clean() ) );
$nav = new Navigation( $tutor_navtable, basename( $PHP_SELF ), $pageTitle );
$page->addBodyComponent( $nav );
$pp = array( );
$pp['executionResult']='';
$pp['authzfile']='';
if ( isSet( $_REQUEST['repos_name'] ) ) {
  $repos_name = $_REQUEST['repos_name'];
  $repos_name = preg_replace( '/\s+/', '_', trim( $repos_name ) );
}

// Delete action?
if ( isSet( $_POST['Delete'] ) ) {
  $deleteId = validate( $_POST['Delete'], 'integer', '0' );
  $sql = "select owner,repospath,isroot from personal_repos where id=$deleteId";
  $resultSet = $dbConn->Execute( $sql );
  if ( $resultSet === false ) {
    $dbConn->log( 'error getting repos data with <strong><pre>' . $sql . '</pre></strong> reason : ' .
            $dbConn->ErrorMsg() . '<br/>' );
  } else if ( !$resultSet->EOF ) {
    extract( $resultSet->fields );
    if ( ($isroot != 't') && $snummer == $owner ) {
      echo "<strong>deleting $repospath</strong><br/>";
      $cmdstring = "rm -fr $repospath";
      passthru( $cmdstring );
      $sql = "delete from personal_repos where id = $deleteId";
      $resultSet = $dbConn->Execute( $sql );
    }
  }
}

$reposauthzroot = $reposroot . '/' . $snummer . '/svnroot';
$authzfile = 'conf/authz';
$authzfilepath=$reposauthzroot.'/'.$authzfile;
if ( is_file( $authzfilepath ) ) {
  $_SESSION['mustCommit'] = 1;
  $pp['executionResult'] = ConfFileEditor::save();
  $_SESSION['conf_editor_basedir'] = $reposauthzroot;
  $_SESSION['fileToEdit'] = $authzfile;
  $fileeditor = new ConfFileEditor( $PHP_SELF,'templates/authzeditor.html' );
}
//$fileeditor->setMustCommit( true );
//$fileeditor->save();
$isRoot = 'false';
if ( isSet( $_REQUEST['bcreate'] ) && isSet( $repos_name ) && '' != $repos_name ) {
  if ( $repos_name == 'svnroot' ) {
    $isRoot = 'true';
    $cmdstring = $subversionscriptdir . "/mksvnrootrepos.sh $reposroot $snummer";
    unset( $_SESSION['repos_name'] );
  } else {
    $cmdstring = $subversionscriptdir . "/mksimplerepos.sh $reposroot $snummer $repos_name";
  }
  $url_tail = 'svnp/' . $snummer . '/' . $repos_name;
  $repospath = '/home/' . $url_tail;
  $repoURL = $svnserver_url . '/' . $url_tail;
  $pp['executionResult'] .= "<fieldset><legend>The result of executing <b>'$cmdstring'</b> is:</legend>"
          . "<pre style='color:#00F; font-weight:bold;'>\n";
  $retval = 0;
  ob_start();
  passthru( $cmdstring, $retval );
  $pp['executionResult'] .= ob_get_clean();
  if ( $retval == 0 ) {
    $sql = "insert into personal_repos (owner,repospath,url_tail,isroot) \n"
            . " values('$snummer','$repospath','$url_tail',$isRoot);\n";
    $resultSet = $dbConn->Execute( $sql );
    if ( $resultSet === false ) {
      $dbConn->log( 'Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql );
    }
    $pp['executionResult'] .="<p>The repository will live at <a href='$repoURL'\n"
            . "target='_blank'>$repoURL</a><br/></pre>\n"
            . "<p>If you want to view the repository with our current browser you should restart the browser.</p>";
  } else {
    $pp['executionResult'] .= "command '$cmdstring' failed with retval $retval\n";
  }
  $pp['executionResult'] .= "</fieldset>\n";
}
$pp['reposTable'] = '';
$sql = "select repospath,url_tail, isroot, id, coalesce(description,split_part(url_tail,'/',3)) "
        . "as description,youngest as versions from personal_repos\n"
        . "where owner=$snummer order by isroot ,url_tail";
$resultSet = $dbConn->Execute( $sql );
if ( $resultSet === false ) {
  $dbConn->log( 'error getting data with <strong><pre>' . $sql . '</pre></strong> reason : ' .
          $dbConn->ErrorMsg() . '<br/>' );
} else {
  if ( !$resultSet->EOF ) {
    $pp['reposTable'] = " <p>Currently you have the following repositories:</p>\n"
            . "<table style='border-collapse:collapse;empty-cells:show;' border='1' >\n"
            . "<tr><th>repo</th><th>URL</th><th>Revisions</th><th>path or description</th>\n"
            . "<th>Manage description</th><th>Manage repo</th><th>Get copy</th></tr>\n";

    while ( !$resultSet->EOF ) {
      extract( $resultSet->fields );
      $repoURL = $svnserver_url . '/' . $url_tail;
      $repos_name = explode( '/', $url_tail );
      if ( $isroot == 't' ) {
        $deleteBut = "&nbsp;";
      } else {
        $deleteBut = "\t\t<form method='post' name='deleteForm' action='$PHP_SELF'>" .
                "<button name='Delete' value='$id' onClick=\"javascript:return confirm('Sure to delete repo $url_tail')\">"
                . "Delete</button></form>\n";
      }
      $pp['reposTable'] .= "<tr><td><a href='$repoURL'>$repos_name[2]</a></td><td>$repoURL</td>"
              . "<td>$versions</td>"
              . "<td>$description</td>\n<td>"
              . "\t<form method='get' name='svnedit' action='svneditor.php'>\n"
              . "\t\t<button name='Edit' value='$id'>Edit description</button>"
              . "</form>\n"
              . "</td>\n"
              . "\t\t<td>$deleteBut</td>\n"
              . "\t\t<td><a href='svndumper.php?repos_id=$id'>tar dump</a></td>\n"
              . "</tr>\n";
      $resultSet->moveNext();
    }

    $pp['reposTable'] .="</table>\n";
  }
}
$page->addHtmlFragment( 'templates/isubversionrepos.html', $pp );
if ( isSet( $fileeditor ) ) {
  $fileeditor->getWidgetForPage( $page);
}
$page->show();
// authz file is there, we have a root repos
?>
