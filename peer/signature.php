<?php
include 'navigation2.php';
$sender_name='';
$sql="select email1 as replyto,roepnaam||coalesce(' '||voorvoegsel,'')||' '||achternaam as sender_name,signature".
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

<b>Signature:</b><br/>
<textarea cols='120' rows='20' id='signature' name='signature' class='mceEditor'>
$signature
</textarea >
<input type='submit' name='set' value='Update signature'/>
<input type='reset' name='reset' value='reset'/>
</div>
</fieldset>
</form>
";

$page->addBodyComponent(new Component($mailer_signature));
$page->addHeadText(
'<script language="javascript" type="text/javascript" src="'.SITEROOT.'/js/tiny_mce/tiny_mce.js"></script>
 <script language="javascript" type="text/javascript">
   tinyMCE.init({
        theme: "advanced",
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
');
$page->show();
?>