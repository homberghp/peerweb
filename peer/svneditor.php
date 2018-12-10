<?php

require_once('validators.php');
include_once('tutorhelper.php');
include_once('navigation2.php');
require_once('conffileeditor.inc.php');
extract( $_SESSION );
if ( isSet( $_REQUEST['Edit'] ) ) {
  $fileNr = validate( $_REQUEST['Edit'], 'integer', '0' );
  $_SESSION['fileNr'] = $fileNr;
}
if ( isSet( $_POST['bsumbit'] ) && isSet( $_POST['description'] ) ) {
  $description = pg_escape_string( $_POST['description'] );
  $sql = "update personal_repos set description='$description' where id=$fileNr";
  $resultSet = $dbConn->Execute( $sql );
  if ( !$resultSet->EOF ) {
    $dbConn->log( "could not save description with sql " . $sql );
  }
  $dbConn->log( "saved $description" );
}
$pp = array( );
$sql = "select repospath,description,id as fileNr from personal_repos where owner=$snummer and id=$fileNr\n";
$resultSet = $dbConn->Execute( $sql );
if ( !$resultSet->EOF ) {
  $pp = array_merge( $pp, $resultSet->fields );
  $description = stripslashes( $description );
}

//pagehead('Edit Subversion repositories');
$page = new PageContainer();
$page->addHeadText( '<script type="text/javascript">
function closeAction() {
    document.edit_form.action = "isubversionrepos.php";
    return true;
}
</script>
  ' );
$page->setTitle( 'Edit Subversion repositories' );
$page_opening = "Edit Subversion repositories for $roepnaam $tussenvoegsel $achternaam ($snummer)";
$nav = new Navigation( $tutor_navtable, basename( $PHP_SELF ), $page_opening );
$nav->setInterestMap( $tabInterestCount );
$page->addBodyComponent( $nav );
$page->addHtmlFragment( 'templates/svneditor.html', $pp );
$page->show();
?>