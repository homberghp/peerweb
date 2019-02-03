<?php
requireCap(CAP_TUTOR);
require_once('navigation2.php');
require_once 'querytotable.php';
require_once 'validators.php';
require_once 'prjMilestoneSelector2.php';
require_once 'TemplateWith.php';

$prj_id=1;
$milestone=1;
$prjm_id = 0;
extract($_SESSION);

$prjSel=new PrjMilestoneSelector2($dbConn,$peer_id,$prjm_id);
extract($prjSel->getSelectedData());
$_SESSION['prj_id']=$prj_id;
$_SESSION['prjm_id']=$prjm_id;
$_SESSION['milestone']=$milestone;


if (isSet($_REQUEST['prj_id_milestone'])) {
    $_SESSION['prj_id_milestone'] = validate($_REQUEST['prj_id_milestone'],'prj_id_milestone',$prj_id.':'.$milestone);
    list($prj_id,$milestone)=explode(':',$_SESSION['prj_id_milestone']);
    $_SESSION['prj_id']=$prj_id;
    $_SESSION['milestone']=$milestone;
    //    echo "prj_id=$prj_id, milestone=$milestone<br>";
 }
if (isSet($_REQUEST['bsubmit']) && isSet($_REQUEST['contact'])) {
    $sql="begin work;\n";
    for ($i=0; $i < count($_REQUEST['contact']); $i++ ) {
	list($prjtg_id,$contact)= explode(':',validate($_REQUEST['contact'][$i],'grp_num_contact','1:879417'));
	$sql .= "delete from prj_contact where prjtg_id=$prjtg_id;\n".
	    "insert into prj_contact (snummer,prjtg_id) values($contact,$prjtg_id);\n";
    }
    $sql .= "commit";
    $dbConn->doSilent($sql);

 }
$page = new PageContainer();
$page->setTitle('Peer assessment, define project contact');
$page_opening="Define contact persons for project groups";
$nav=new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$page->addBodyComponent($nav);
$form1 = new HtmlContainer("<fieldset id='form1'><legend><b>For project and milestone.</b></legend>");
$form1Form = new HtmlContainer("<form id='project' method='get' name='project' action='$PHP_SELF'>");

$prjSel->setJoin('milestone_grp using (prj_id,milestone)');

$prj_id_selector=$prjSel->getSelector();

$templatefile='templates/defcontacts1.html';
$template_text= file_get_contents($templatefile, true);
$sql ="select * from project join tutor on(owner_id=userid) join student_email on(userid=snummer)\n".
    "where prj_id=$prj_id";
//$dbConn->log($sql);
$resultSet= $dbConn->doSilent($sql);
extract($resultSet->fields);
if ($template_text === false ) {
  $form1Form->addText("<strong>cannot read template file $templatefile</strong>");
} else {  
  $form1Form->addText(templateWith($template_text, get_defined_vars()));
}
$form1->add($form1Form);

$form2Form=new HtmlContainer("<form id='contacts' method='post' name='contacts' action='$PHP_SELF'>");
	$form2Table=new HtmlContainer("<table id='contact_table' style='border-collapse:collapse' border='1' summary='contacts table'>");
	$form2Table->addText("<tr><th colspan='3'>Grp,tutor</th><th colspan='3'>Current contact</th><th>New contact</th></tr>\n".
			     "<tr><th>Grp</th>".
			     "<th>Alias</th>".
			     "<th>Tutor</th>".
			     "<th>name</th>".
			     "<th>number</th>".
			     "<th>email</th>".
			     "<th>select</th>".
			     "</tr>");

$sql="select pt.grp_num,pt.prjtg_id,alias,\n".
    "cs.achternaam as cs_achternaam,cs.tussenvoegsel as cs_tussenvoegsel,cs.roepnaam as cs_roepnaam,cs.snummer as contact,\n".
    "rtrim(cs.email1) as cs_email1,pt.grp_num,\n".
    "ts.achternaam||', '||ts.roepnaam||coalesce(' '||ts.tussenvoegsel,'') as tutor_naam, tut.tutor\n".
    "  from prj_tutor pt join tutor tut on(pt.tutor_id=tut.userid) join student_email ts on(userid=snummer)\n".
    " left join grp_alias ga using(prjtg_id)\n".
    " left join prj_contact pc using(prjtg_id)\n".
    " left join student_email cs on (pc.snummer=cs.snummer)\n".
    " where prjm_id=$prjm_id order by pt.grp_num";
//$dbConn->log($sql);
$resultSet= $dbConn->Execute($sql);
$rowStyle=array("style='background:#FFC;'","style='background:#CFF'");
$rowNr=0;
if ($resultSet === false) {
    echo "cannot get contact data with<pre> {$sql}</pre>, reason: ".$dbConn->ErrorMsg()."<BR>\n";
 } else {
    if (!$resultSet->EOF) {
	while (!$resultSet->EOF) {
	    extract($resultSet->fields);
	    $sql = "select rtrim(achternaam)||', '||rtrim(roepnaam)||coalesce(' '||tussenvoegsel,'') as name,".
		"prjtg_id||':'||snummer as value from prj_grp join prj_tutor using(prjtg_id) join student_email using(snummer)\n".
		" where prjtg_id=$prjtg_id\n".
		"order by achternaam, roepnaam";
	    $tdStyle= $rowStyle[ $rowNr % 2 ];
	    $contact_selector="<select name='contact[]' style='width:20em' $tdStyle>\n".
		getOptionList($dbConn,$sql,$prjtg_id.':'.$contact)."\n</select>\n";
	    $row="<tr><td $tdStyle>$grp_num</td>".
		"<td $tdStyle>$alias</td>".
		"<td $tdStyle>$tutor_naam</td>".
		"<td $tdStyle>$cs_roepnaam $cs_tussenvoegsel $cs_achternaam</td>\n".
		"<td $tdStyle>$contact</td>".
		"<td $tdStyle><a href='mailto:$cs_email1'>$cs_email1</a></td>".
		"<td $tdStyle>".$contact_selector."</td>\n".
		"</tr>\n";
	    $form2Table->addText($row);
	    $resultSet->moveNext();
	    $rowNr++;
        }
    }
 }
$form2Form->add($form2Table);
$form2Form->addText("To set these contacts, press <input type='submit' name='bsubmit' value='Apply'/> or <input type='reset'/>".
		    "<input type='hidden' name='prj_id_milestone' value='$prj_id:$milestone'/>");
$form1->add($form2Form);
$page->addBodyComponent($form1);

$page->show();
?>
