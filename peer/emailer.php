<?php

function nepmail( $to, $sub, $body, $head ) {
  global $dbConn;
  $dbConn->log( "To: $to\n\nsubject:$to\n\n$body\n" );
}

include 'navigation2.php';
$sender_name = '';
$sql = "select email1 as replyto,roepnaam||coalesce(' '||voorvoegsel,'')||' '||achternaam as sender_name," .
        "coalesce(signature," .
        "'sent by the peerweb service on behalf of '||roepnaam||coalesce(' '||voorvoegsel,'')||' '||achternaam)\n" .
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
<div>
<table><tr><th>subject:</th><td>
<input type='text' name='mailersubject' id='mailersubject' value='$mailersubject' 
size='80'/></td></tr>
<tr><th>Recipient:</th><td><input type='text' size='100' name='mailerto' value='$mailerto'/></td></tr>
</table>
<textarea cols='120' rows='20' id='mailertext' name='mailertext' class='mceEditor'>
$mailertext
</textarea >
<input type='submit' name='send' value='send'/>

</div>
Note that you can change the default signature in 'personal data and settings &gt; email signature'
</fieldset>
</form>
";

$page->addBodyComponent( new Component( $mailer ) );
$page->addHeadText(
        '<script language="javascript" type="text/javascript" src="' . SITEROOT . '/js/tiny_mce/tiny_mce.js"></script>
 <script language="javascript" type="text/javascript">
   tinyMCE.init({
        theme: "advanced",
        /*auto_resize: true,*/
        gecko_spellcheck : true,
        theme_advanced_toolbar_location : "top",
	mode : "textareas", /*editor_selector : "mceEditor",*/

        theme_advanced_styles : "Header 1=header1;Header 2=header2;Header 3=header3;Table Row=tableRow1",
        plugins: "advlink,searchreplace,insertdatetime,table",
	plugin_insertdate_dateFormat : "%Y-%m-%d",
	plugin_insertdate_timeFormat : "%H:%M:%S",
	table_styles : "Header 1=header1;Header 2=header2;Header 3=header3",
	table_cell_styles : "Header 1=header1;Header 2=header2;Header 3=header3;Table Cell=tableCel1",
	table_row_styles : "Header 1=header1;Header 2=header2;Header 3=header3;Table Row=tableRow1",
	table_cell_limit : 100,
	table_row_limit : 5,
	table_col_limit : 5,
	theme_advanced_buttons1_add : "search,replace,insertdate,inserttime,tablecontrols",


/*        theme_advanced_buttons2 : "",
	theme_advanced_buttons3 : ""*/
    });
 </script>
' );
$page->show();
?>