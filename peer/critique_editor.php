<?php
include_once('./peerlib/peerutils.php');
require_once('component.php');
require_once'rteSafe.php';
$referer=$_SESSION['referer'];
extract($_SESSION);
$new_critique='N';
$critique_id=-1;
$doc_id=1;
//$dbConn->logContext();

if (isSet($_REQUEST['doc_id'])) {
    $doc_id=validate($_REQUEST['doc_id'],'integer',1);
    $sql = "select title,roepnaam||' '||coalesce(tussenvoegsel||' ','')||achternaam as author from student\n".
	" join uploads using (snummer) where upload_id=$doc_id";
    //    $dbConn->log($sql);
    $resultSet=$dbConn->Execute($sql);
    if (!$resultSet->EOF) {
	extract($resultSet->fields);
    }
 } else {$author ='unknown';}

// get form and editor values
if (isSet($_REQUEST['critique_id'])) {
    $critique_id=validate($_REQUEST['critique_id'],'integer',-1);
    if ( $_REQUEST['critique_id'] == -1 ) {
	//	$critique_id='new';
	$doc_id=validate($_REQUEST['doc_id'],'integer',1);
	$sql = "select distinct $peer_id as critiquer, roepnaam,tussenvoegsel,achternaam,\n".
	    "date_trunc('seconds',now()) as critique_time,\n".
	    "date_trunc('seconds',now()) as edit_time\n".
	    "from student where snummer=$peer_id";
	$resultSet =$dbConn->Execute($sql);
	if (!$resultSet->EOF) {
	    extract($resultSet->fields);
	}
	$critique_text='<h4>My Feedback is</h4>';
	$sql ="select * from all_prj_tutor join uploads using(prjtg_id) where upload_id=$doc_id";
	$resultSet =$dbConn->Execute($sql);
	if (!$resultSet->EOF) {
	    extract($resultSet->fields);
	}
	$sql="select grp_num as critiquer_grp from project_grp_stakeholders join all_prj_tutor using(prjtg_id) where prjm_id=$prjtg_id and snummer=$peer_id";
	$resultSet =$dbConn->Execute($sql);
	if (!$resultSet->EOF) {
	    extract($resultSet->fields);
	}
    } else {
	$sql = "select distinct critiquer, roepnaam,tussenvoegsel,achternaam,doc_id,critique_id,\n".
	    "date_trunc('seconds',ts) as critique_time,critique_text,\n".
	    "date_trunc('seconds',edit_time) as edit_time,\n".
	    "prj.afko,prj.year,coalesce(ps.grp_num,0) as critiquer_grp\n".
	    "from document_critique dcr join student st on (dcr.critiquer=st.snummer)\n".
	    "join uploads u on(dcr.doc_id=u.upload_id)\n".
	    "left join ( select * from project_grp_stakeholders join "
	  ." all_prj_tutor using(prjtg_id) where snummer=$peer_id) ps on(ps.prjtg_id=u.prjtg_id )\n".
	    "join all_prj_tutor prj on(prj.prjtg_id=u.prjtg_id) where critiquer=$peer_id and critique_id=$critique_id";
	$resultSet =$dbConn->Execute($sql);
	$dbConn->log($sql);
	if (!$resultSet->EOF) {
	    extract($resultSet->fields);
	}
    }
 }
if (isSet($_REQUEST['delete_critique'])) {
    $critique_id=validate($_REQUEST['delete_critique'],'integer',-11);
    $doc_id=validate($_REQUEST['doc_id'],'integer',1);
    // delete only in critiquer is current user.
    $sql="delete from document_critique where critique_id=$critique_id and critiquer=$peer_id";
    //    $resultSet $dbConn->Execute($sql);
    header("Location: ".$_SESSION['referer']."?doc_id$doc_id");
    die();
 } else if (isSet($_REQUEST['bsubmit_critique'])) {
    $critique_text=$_REQUEST['critique_text'];
    $critique_text_i=pg_escape_string($critique_text);
    $doc_id=validate($_REQUEST['doc_id'],'integer',1);
    if ( $critique_id =='-1' ) {
	$sql = "SELECT roepnaam as jroepnaam, tussenvoegsel as jtussenvoegsel,".
	    "achternaam as jachternaam,email1 as jemail1, lang as jlang FROM student WHERE snummer=$peer_id";
	$resultSet=$dbConn->Execute($sql);
	if ($resultSet=== false) {
	    die('Error: '.$dbConn->ErrorMsg().' with '.$sql);
	}
	extract($resultSet->fields);
	// transaction
	$dbConn->transactionStart("begin work");
	$sql="select nextval('public.doc_critique_seq'::text) as critique_id";
	$resultSet =$dbConn->doSilent($sql);
	extract($resultSet->fields);
	$sql="insert into document_critique(critique_id,doc_id,critiquer,critique_text) \n".
	    "values($critique_id,$doc_id,$peer_id,'$critique_text_i')";
	$resultSet =$dbConn->doSilent($sql);
        $dbConn->transactionEnd();          
	// mail that a critique was added to uploader/author
	$sql = "select roepnaam,tussenvoegsel,achternaam,email1,email2 from student\n".
	    " left join alt_email using(snummer)\n".
	    "join uploads using(snummer) where upload_id=$doc_id";
	$resultSet = $dbConn->execute($sql);
	if ( $resultSet === false ) {
	    echo 'Error: '.$dbConn->ErrorMsg().' with <br/><pre>'.$sql.'</pre>';
	}
	extract($resultSet->fields,EXTR_PREFIX_ALL,'author');
	$subject='You received feedback on one of your uploaded doucments at the peerweb';
	$body="Dear $author_roepnaam,\n\n".
	    "You received feedback on the document you uploaded to the peerweb.\n".
	    "Have a look at\n$server_url$root_url/upload_critique.php?doc_id=$doc_id\nKind regards,\nThe peerweb service";
	$replyto=$jemail1;
	//	if ($isTutor)
	//  $replyto=$tutor_email;
	$headers = "From: peerweb@fontysvenlo.org\n".
	    "Reply-To: $replyto\n";
	$toAddress=$author_email1;
	domail($toAddress,$subject,$body,$headers);
	/* if (isSet($author_email2))  { */
	/*     $subject="You received an email at your fontys email address"; */
	/*     $body="Dear $author_roepnaam,\n\n". */
	/* 	"You received an email at your fontys email address. Have a look.\n". */
	/* 	"Kind regards\nThe peerweb service"; */
	/*     $toAddress=$author_email2; */
	/*     domail($toAddress,$subject,$body,$headers); */
	/* } */
    } else {
	$sql="begin work;\n".
	    "insert into critique_history (critique_id,edit_time,critique_text) select critique_id,edit_time,critique_text\n".
	    "from document_critique where critique_id=$critique_id and critiquer=$peer_id;\n ".
	    "update document_critique set critique_text='$critique_text_i',edit_time=now() \n".
	    "where critique_id=$critique_id and critiquer=$peer_id;".
	    "commit";
    }
    $resultSet =$dbConn->doSilent($sql);
 } else if (isSet($_REQUEST['edit_critique'])){
    $critique_id=validate($_REQUEST['edit_critique'],'integer',1);
 }
$page = new PageContainer();
$page->setTitle('Critique_editor');
$page->addHeadText("<script type='text/javascript'>\n".
		   "/*\n".
		   " * refresh parent page on close\n".
		   " */\n".
		   "function bye(){ \n".
		   "   opener.focus();\n".
		   "   opener.location.reload();\n".
		   "   self.close();\n".
		   "}\n".
		   "/*\n".
		   " * refresh parent page on close\n".
		   " */\n".
		   "function edit_submit(){ \n".
		   "   opener.location.reload();\n".
		   "  return true;\n".
		   "}\n".
		   "</script>");
$form1=new HtmlContainer("<div id='main'>");
//$dbConn->log($sql);
$critique_text = stripslashes($critique_text);
$editor_result=$critique_text;
$critique_text_rte=rteSafe($critique_text);
$critique_id_text=($critique_id>0)?$critique_id:'\'<i>new</i>\'';
$templatefile='templates/critique_editor_form.html';
$template_text= file_get_contents($templatefile, true);
if ($template_text === false ) {
    $form1->addText("<strong>cannot read template file $templatefile</strong>");
 } else {  
    eval("\$text = \"$template_text\";");
    $form1->addText($text);
 }
$page->addBodyComponent( $form1 );
$page->addHtmlFragment('templates/tinymce_include.html', $pp);

$page->show();
?>
