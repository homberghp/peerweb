<?php
requireCap(CAP_SYSTEM);
require_once 'component.php';
require_once 'navigation2.php';

require_once 'SimpleTableFormatter.php';
require_once 'mailFunctions.php';

$pp = array( );
$page = new PageContainer();
$page->setTitle( "Plan absentdag" );
$nav = new Navigation( $tutor_navtable, basename(__FILE__), "Plan een absentdag" );
$page->addBodyComponent( $nav );


//$css = "<link rel='stylesheet' type='text/css' href='style/tablesorterstyle.css'/>";
//$page->addScriptResource('js/jquery.min.js');
//$page->addScriptResource('js/jquery.tablesorter.js');
//$page->addJqueryFragment( '$("#myTable").tablesorter({widgets: [\'zebra\'],headers: {0:{sorter:false}}});' );
//$page->addHeadText($css);
$page->addHeadFragment( '../templates/tinymce_include.html' );
$pp['subject_de'] = 'Einladung zum Schnuppertag an der Fontys FH in Venlo';
$pp['subject_nl'] = 'Uitnodiging voor een absentdag bij Fontys Hogescholen in Venlo';

$pp['mailbody_de'] = file_get_contents( '../templates/absent_mailbody_de.html', true );
$pp['mailbody_nl'] = file_get_contents( '../templates/absent_mailbody_nl.html', true );

$sql = "select 0 as sorter,m.*,s.roepnaam||coalesce(' '||s.tussenvoegsel||' ',' ')||s.achternaam as mail_author\n"
        . " from absentmail m join student_email s on (owner=snummer) where owner=$peer_id \n"
        . "union\n"
        . "select 1 as sorter,m.*,s.roepnaam||coalesce(' '||s.tussenvoegsel||' ',' ')||s.achternaam as mail_author\n"
        . "from absentmail m join student_email s on (owner=snummer) \n"
        . "order by sorter,absent_datum desc limit 1";
$resultSet = $dbConn->Execute( $sql );
if ( $resultSet !== false && !$resultSet->EOF ) {
  $pp = array_merge( $pp, $resultSet->fields );
}
$sqlsender = "select rtrim(email1) as sender,roepnaam||coalesce(' '||tussenvoegsel,'')||' '||achternaam as sender_name," .
        "coalesce(signature," .
        "'sent by the peerweb service on behalf of '||roepnaam||coalesce(' '||tussenvoegsel,'')||' '||achternaam)\n" .
        "  as signature from student_email left join email_signature using(snummer) where snummer='$peer_id'";
$rs = $dbConn->Execute( $sqlsender );
if ( !$rs->EOF ) {
  extract( $rs->fields );
} else {
  $replyto = 'Pieter.van.den.Hombergh@fontysvenlo.org';
  $sender_name = 'Pieter van den Hombergh';
  $signature = '';
}

if ( isSet( $_POST['mailbody_de'] ) ) {
  $pp['mailbody_de'] = preg_replace( '/"/', '\'', $_POST['mailbody_de'] );
}
if ( isSet( $_POST['subject_de'] ) ) {
  $pp['subject_de'] = $_POST['subject_de'];
}

if ( isSet( $_POST['mailbody_nl'] ) ) {
  $pp['mailbody_nl'] = preg_replace( '/"/', '\'', $_POST['mailbody_nl'] );
}
if ( isSet( $_POST['subject_nl'] ) ) {
  $pp['subject_nl'] = $_POST['subject_nl'];
}

if ( isSet( $_POST['absent_datum'] ) ) {
  $pp['absent_datum'] = $_POST['absent_datum'];
}
if ( isSet( $_POST['domail'] ) ) {
  $mailbody_nl = preg_replace( "/'/", "''", $pp['mailbody_nl'] );
  $mailbody_de = preg_replace( "/'/", "''", $pp['mailbody_de'] );
  $sql = "insert into absentmail (owner,absent_datum,subject_nl,subject_de,mailbody_nl,mailbody_de)\n" .
          "select {$peer_id},'{$pp['absent_datum']}','{$pp['subject_nl']}','{$pp['subject_de']}','{$mailbody_nl}','{$mailbody_de}'"
          . "where ($peer_id,'{$pp['absent_datum']}') not in (select owner,absent_datum from absentmail)";
  $dbConn->Execute( $sql );
  $result = $dbConn->Affected_Rows();
  if ( $result == 0 ) {
    $sql = "update absentmail set subject_nl='{$pp['subject_nl']}',\n"
            . "subject_de='{$pp['subject_de']}',\n"
            . "mailbody_nl='{$mailbody_nl}',\n"
            . "mailbody_de='{$mailbody_de}'\n"
            . "where owner={$peer_id} and absent_datum='{$pp['absent_datum']}'";
    $dbConn->Execute( $sql );
    $result = $dbConn->Affected_Rows();
  }
}


if ( isSet( $_POST['mail'] ) && isSet( $_POST['domail'] ) ) {

  $mail = $_POST['mail'];
  $mailset = '\'' . implode( "','", $mail ) . '\'';

  $sql_de = "select absent_id,email as email1, \n"
          . "roepnaam ||' '||coalesce(tussenvoegsel,'')||' '||achternaam as name\n"
          . "from absent\n"
          . "where taal ='DE' and absent_id in ($mailset)";
  $sql_nl = "select absent_id,email as email1, \n"
          . "roepnaam ||' '||coalesce(tussenvoegsel,'')||' '||achternaam as name\n"
          . "from absent\n"
          . "where taal ='NL' and absent_id in ($mailset)";
  //$dbConn->log( $sql );
  formMailer( $dbConn, $sql_de, $pp['subject_de'], $pp['mailbody_de'], $sender, $sender_name );
  formMailer( $dbConn, $sql_nl, $pp['subject_nl'], $pp['mailbody_nl'], $sender, $sender_name );
  // update invitation
  $sql = "begin work;\n"
          . "update absent set invitation=now()::date where absent_id in ($mailset);\n"
          . "update absentmail set invitation_datum = now()::date where owner=$peer_id and absent_datum='{$pp['absent_datum']}';\n"
          . "commit";
  $rs = $dbConn->Execute( $sql );
  if ( $rs === false ) {
    $dbConn->Execute( 'rollback' );
  }
}

$sql = "select '<input type=''checkbox'' name=''mail[]'' value='''||absent_id||'''/>' as chk,\n"
        . "achternaam,roepnaam,tussenvoegsel,plaats,land,postcode,email,sex,datum_in,invitation \n"
        . "from  where participation isnull order by invitation desc,land,achternaam";

$pp['rtable'] = new SimpleTableFormatter( $dbConn, $sql, $page );
$pp['rtable']->setCheckColumn( 0 )
        ->setCheckName( 'mail[]' )
        ->setColorChangerColumn( 10 )
        ->setTabledef( "<table id='myTable' class='tablesorter' summary='absent studenten'"
                . " style='empty-cells:show;border-collapse:collapse' border='1'>" );
$page->addHtmlFragment( '../templates/absentdag.html', $pp );
$page->addHeadText( file_get_contents( '../templates/simpledatepicker.html' ) );
$page->addScriptResource( 'js/jquery.min.js' );
$page->addScriptResource( 'js/jquery-ui.custom.min.js' );
$page->addJqueryFragment( '$(\'#absent_datum\').datepicker(dpoptions);' );

$page->show();
?>
