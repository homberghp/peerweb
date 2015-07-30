<?php
include_once('./peerlib/peerutils.inc');
require_once('component.inc');

$page = new PageContainer();
$page->setTitle('Critique history');
$page->addHeadComponent( new Component("<script type='text/javascript'>\n".
					   "/*\n".
					   " * refresh parent page on close\n".
					   " */\n".
					   "function bye(){ \n".
					   "   opener.focus();\n".
					   "   self.close();\n".
					   "}\n".
					   "</script>"));
$page->addHeadComponent(new Component("<link rel='stylesheet' type='text/css' href='$root_url/style/newdivstyle.css'/>"));
$page->addHeadComponent( new Component("<style type='text/css'>\n".
				       " p {text-align: justify;}\n".
				       " p:first-letter {font-size:180%; font-family: script;font-weight:bold; color:#800;}\n".
				       " </style>"));
$maindiv=new HtmlContainer("<div id='main'>");
$page->addBodyComponent( $maindiv );
if (isSet($_REQUEST['critique_id'])) {
    $critique_id=validate($_REQUEST['critique_id'],'integer',1);
    $maindiv->addText("<div class='navopening'><h1>Critique history of critique $critique_id <button onClick='javascript:bye()'>Close</button></h1></div>");

    $sql = "select distinct critiquer, roepnaam,voorvoegsel,achternaam,critique_id,id,\n".
    "date_trunc('seconds',ch.edit_time) as critique_time,ch.critique_text as critique_text,\n".
    "afko,year,apt.grp_num as critiquer_grp\n".
    "from document_critique dcr\n".
    "join critique_history ch using(critique_id)\n".
    "join student st on (dcr.critiquer=st.snummer)\n".
    "join uploads u on(dcr.doc_id=u.upload_id)\n".
      //    "join project_grp_stakeholders ps on(ps.prjtg_id=u.prjtg_id and ps.snummer=dcr.critiquer)\n".
    "join all_prj_tutor apt on(u.prjtg_id=apt.prjtg_id) where critique_id=$critique_id\n".
    "order by id desc";
    $resultSet=$dbConn->Execute($sql);
    if ($resultSet=== false) {
	die('Error: '.$dbConn->ErrorMsg().' with '.$sql);
    }
    $table_div=new HtmlContainer("<div id='tablediv' style='padding: 0 2em 0 2em'>");
    $table=new HtmlContainer("<table id='critique_table' class='layout' style='padding:0;margin:0;'");
    $table_div->add($table);
    $maindiv->add($table_div);
    while (!$resultSet->EOF) {
	extract($resultSet->fields);
	$table->add( 
		    new Component("<tr><td>\n".
				  "\t<div class='critique' style='background:#ffffe0;'>\n".
				  "\t<fieldset style='margin: .2em border:2;'>\n".
				  "\t\t<legend>Critique $critique_id by $roepnaam $voorvoegsel $achternaam ($critiquer)&nbsp;</legend>\n".
				  "\t\t\t<table class='layout'>\n".
				  "\t\t\t\t<tr><td>Group</td><th align='left'>$critiquer_grp ($afko $year) </th></tr>\n".
				  "\t\t\t\t<tr><td>Critique time</td><th align='left'> $critique_time</th></tr>\n".
				  "\t\t\t</table>\n".
				  "$critique_text\n".
				  "\t</fieldset>\n".
				  "\t</div>\n".
				  "</td></tr>"));
	$resultSet->MoveNext();
    }
 }
$page->show();
?>