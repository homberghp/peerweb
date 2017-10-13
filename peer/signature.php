<?php
include 'navigation2.php';
$sender_name='';
$sql="select email1 as replyto,roepnaam||coalesce(' '||tussenvoegsel,'')||' '||achternaam as sender_name,signature".
"  from student left join email_signature using(snummer) where snummer='$peer_id'";
$rs=$dbConn->Execute($sql);
if (!$rs->EOF) {
  $replyto=$rs->fields['replyto'];
  $sender_name=$rs->fields['sender_name'];
  if (isSet($rs->fields['signature'])) {
    $signature=$rs->fields['signature'];
  } else $signature="<hr/><div>Sent by the peerweb service at".
	   " fontysvenlo.org on behalf of $sender_name</div>";
}
else {
  $replyto='Pieter.van.den.Hombergh@fontysvenlo.org';
  $sender_name='Pieter van den Hombergh';
  $signature='';
}
if (isSet($_POST['signature'])){
  $signature=$_POST['signature'];
  $sql_signature=pg_escape_string($signature);
  $sql="begin work;\n".
    " delete from email_signature where snummer=$peer_id;\n".
    " insert into email_signature (snummer,signature) values($peer_id,'$signature');\n".
    "commit";
 $dbConn->doSilent($sql);
}
$page_opening="Set you mailer signature";
$page= new PageContainer();
$page->setTitle($page_opening);
$nav=new Navigation(array(),basename($PHP_SELF),$page_opening);
$page->addBodyComponent($nav);


$mailer_signature="
<form method='post' action='$PHP_SELF' name='mailerform' id='mailerform'>
<fieldset><legend>Mailer</legend>
This signature will be appended to your email editor on startup. 
You may use html enriched tex.
<div>

<div style='background-color:#eee'><b>Signature:</b><br/>
<textarea cols='120' rows='20' id='signature' name='signature' class='mceEditor tinymce'>
$signature
</textarea >
<input type='submit' name='set' value='Update signature'/>
<input type='reset' name='reset' value='reset'/></div>
</div>
</fieldset>
</form>
";

$page->addBodyComponent(new Component($mailer_signature));
$page->addHtmlFragment('templates/tinymce_include.html', $pp);
$page->show();
?>