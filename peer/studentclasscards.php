<?php
requireCap(CAP_MKCLASSES);

/**
 * create class selectiojn cards based on current class assignments.
 * For WTB/IPO, marc dessi. 
 */
require_once('peerutils.php');
require_once('navigation2.php');
require_once('prjMilestoneSelector2.php');
require_once('classMultiSelector.php');
require_once 'TemplateWith.php';

unset($_SESSION['class_ids']);
$class_ids = array();
extract($_SESSION);

if (isSet($_POST['bsubmit']) && isSet($_POST['class_ids'])) {
    $class_ids = $_POST['class_ids'];
    $class_set = implode(",", $class_ids);
    $sql = "select sclass from student_class where class_id in({$class_set}) order by sort1,sort2,sclass";
    $rs = $dbConn->Execute($sql);
    $class_names = array();
    if (!$rs === false) {
        while (!$rs->EOF) {
            $class_names[] = $rs->fields['sclass'];
            $rs->moveNext();
        }
    }
    $filename='classcardsbyclass.pdf';
    $class_names_string = implode(',', $class_names);
    $cmdstring = "{$site_home}/scripts/makeclasscards -d ../output --classes {$class_names_string}";
    $handle = popen($cmdstring, 'r');
    header("Content-type: application/pdf");
    header("Pragma: public");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    fpassthru($handle);

    fclose($handle);
    exit(0);
}
$class_set = implode(",", $class_ids);

$submit_button = "<button name='bsubmit' value='submit'>Submit</button>";
$page = new PageContainer();
$page_opening = 'Create class assignment cards for sitting students';
$page->setTitle($page_opening);
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$page->addBodyComponent($nav);

$form2Form = new HtmlContainer("<form method='post' name='group_def' action='$PHP_SELF'>");

//$form2Form->addText( "Legend:class name [class size]<br/>\n" );
$sql = "select distinct rtrim(student_class.sclass) as sclass,class_id,sort1,sort2,sort_order,\n" .
        "rtrim(faculty.faculty_short) as opl_afko, student_count,rtrim(faculty.faculty_short) as faculty_short,\n" .
        "trim(cluster_name) as cluster_name,class_cluster \n" .
        "from student_class\n" .
        " join faculty using(faculty_id)\n" .
        "join class_cluster using(class_cluster)\n" .
        "join current_student_class using (class_id) join class_size using(class_id)\n " .
        //"where sort2 < 9 and sclass not like 'UIT%' \n" .
        "order by sort_order,faculty_short desc,cluster_name,sort1,sort2,sclass asc";
$classmultiselector = classMultiSelector($dbConn, $sql, $submit_button, $class_ids); //$tablist.$curriculum;
//$classmultiselector = $tablist.$curriculum;
$class_names = array();
if (strlen($class_set) > 0) {
    $sqlCount = "select count(*) as membercount from student_email where class_id in ({$class_set})";
    $rsc = $dbConn->Execute($sqlCount);
    $membercount = $rsc->fields['membercount'];
    $sql = "select sclass from student_class where class_id in({$class_set}) order by sort1,sort2,sclass";
    $rs = $dbConn->Execute($sql);
    if (!$rs === false) {
        while (!$rs->EOF) {
            $class_names[] = $rs->fields['sclass'];
            $rs->moveNext();
        }
    }
} else {
    $membercount = 0;
}
$form2Form->addText($classmultiselector);

//$form1Table->add( $form1Form );
$form2Fieldset = new HtmlContainer("<div id='demo' style='margin:2em;background:rgba(255,255,255,0.5);'><b>Current member count =$membercount</b>");
$form2Fieldset->add($form2Form);

$page->addBodyComponent(new Component('<!-- db_name=$db_name $Id: defgroup.php 1829 2014-12-28 19:40:37Z hom $ -->'));
$page->addHeadText('
<link type="text/css" href="css/pepper-grinder/jquery-ui-1.8.17.custom.css" rel="stylesheet" />	
<script type="text/javascript" src="js/jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.17.custom.min.js"></script>
  <script>
	$(function() {
		$( "#tabs" ).tabs();
	});
	</script>
');
$templatefile = 'templates/studentclasscards.html';
$template_text = file_get_contents($templatefile, true);
if ($template_text === false) {
    $page->addBodyComponent(new Component("<strong>cannot read template file $templatefile</strong>"));
} else {
    $page->addBodyComponent(new Component(templateWith($template_text, get_defined_vars())));
}
$page->addBodyComponent($form2Fieldset);
$page->show();
?>



