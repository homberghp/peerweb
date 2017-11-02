<?php

function nepmail( $to, $sub, $body, $head ) {
  global $dbConn;
  $dbConn->log( "To: $to\n\nsubject:$to\n\n$body\n" );
}

include 'navigation2.php';
$sender_name = '';
$sql = "select email1 as replyto,roepnaam||coalesce(' '||tussenvoegsel,'')||' '||achternaam as sender_name," .
        "coalesce(signature," .
        "'sent by the peerweb service on behalf of '||roepnaam||coalesce(' '||tussenvoegsel,'')||' '||achternaam)\n" .
        "  as signature from student left join email_signature using(snummer) where snummer='$peer_id'";
$rs = $dbConn->Execute( $sql );
if ( !$rs->EOF ) {
  extract( $rs->fields );
} else {
  $replyto = 'Pieter.van.den.Hombergh@fontysvenlo.org';
  $sender_name = 'Pieter van den Hombergh';
  $signature = '';
}
ini_set( 'sendmail_from', $replyto );
$page_opening = "Simple Mailer";
$page = new PageContainer();
$page->setTitle( $page_opening );
$nav = new Navigation( array( ), basename( $PHP_SELF ), $page_opening );
$page->addBodyComponent( $nav );

$mailerto = '';
$mailersubject = 'test from mailer';
$mailertext = '<h1>Put your own text here. The mail will be sent as a html formatted mail.</h1>' . $signature;

if ( isSet( $_POST['mailersubject'] ) && isSet( $_POST['mailertext'] )
        && isSet( $_POST['mailerto'] ) ) {
  extract( $_POST );
  $mailtimestamp = @`date -R`;
  $msgid = @`date +%Y%m%d%H%M%S`;
  $msgid = rtrim( $msgid );
  $msgid .='.E1C32240F1@hermes.fontys.nl';
  $headers = "Received: from hermes.fontys.nl (145.85.2.2) by fontysvenlo.org (81.169.175.156) with PEERWEB mailer for <$mailerto>; $mailtimestamp 
From: \"$sender_name\" <$replyto>
Reply-To: \"$sender_name\" <$replyto>
MIME-Version: 1.0
Content-Transfer-Encoding: 8bit
Content-Type: text/html; charset=\"utf-8\"
Return-Path: " . '<' . "$replyto>" . '>' . "
Message-Id: " . '<' . "$msgid" . '>' . "
";

  $bodyprefix = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>' . $_POST['mailersubject'] . '</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" >
</head>
<body>';
  $bodytail = '</body></html>';
  mail( $_POST['mailerto'], $_POST['mailersubject'], $bodyprefix . $_POST['mailertext'] . $bodytail, $headers );
}
$mailer = "
<form method='post' action='$PHP_SELF' name='mailerform' id='mailerform'>
<fieldset><legend>Mailer</legend>
<p>Mailer is a simple mailer that sends out emails on behalf of your peerweb registered email account.<br/>
You mails will appear to come from your fontys email address.</p>
<div style='background-color:#eee;'>
<label for='mailerto'>Recipient</label>
<input type='text' size='100' name='mailerto' id='mailerto' value='$mailerto'/><br/>
<label for='mailersubject'>Subject</label>
<input type='text' name='mailersubject' id='mailersubject' value='$mailersubject' 
size='80'/>
<textarea cols='120' rows='20' id='mailertext' name='mailertext' class='tinymce'>
$mailertext
</textarea >
<input type='submit' name='send' value='send'/>

</div>
Note that you can change the default signature in 'personal data and settings &gt; email signature'
</fieldset>
</form>
";

$page->addBodyComponent( new Component( $mailer ) );
$page->addHtmlFragment('templates/tinymce_include.html', $pp);

$page->show();
?>