<?php
requireCap(CAP_SYSTEM);
require_once 'validators.php';
require_once 'component.php';
$frole='';
$fgrpname='';
$fcourse='';
$debug=0;
$prjtg_id=1;
extract($_SESSION);
if (isSet($_POST['prjtg_id'])) {
    $prjtg_id=$_POST['prjtg_id'];
    if (isSet($_POST['fgrpname'])) {
	$fgrpname=$_POST['fgrpname'];
    } else $fgrpname='';
    if (isSet($_POST['fcourse'])) {
	$fcourse=$_POST['fcourse'];
    } else $fcourse='';
    if (isSet($_POST['frole'])) {
	$frole=$_POST['frole'];
    } else $frole='';
    $_SESSION['frole']=$frole;
    $_SESSION['fgrpname']=$fgrpname;
    $_SESSION['fcourse']=$fcourse;
    //    echo "$prj_id, $milestone,$grpn\n";
    $sql = "select prj_id,snummer as actor, rtrim(achternaam) as achternaam,rtrim(tussenvoegsel) as tussenvoegsel,\n" .
            "rtrim(roepnaam) as roepnaam, nationaliteit,hoofdgrp as class,rtrim(coalesce(alias,'grp'||grp_num)) as alias,faculty.faculty_short as faculty,\n" .
            "rtrim(course_short) as course_description,rtrim(role) as current_role,rolenum,pr.capabilities as capabilities\n" .
            " from prj_grp \n" .
            "join prj_tutor using(prjtg_id)\n" .
            "join prj_milestone using(prjm_id)\n" .
            " left join student_role using(prjm_id,snummer) \n" .
            " left join project_roles pr using(prj_id,rolenum)\n" .
            " left join grp_alias using(prjtg_id)\n" .
            " join student_email st using(snummer)\n" .
            "left join fontys_course fc on(st.opl=fc.course)\n" .
            " join faculty on(st.faculty_id=faculty.faculty_id)\n " .
            " where  prjtg_id=$prjtg_id \n " .
            " order by achternaam,roepnaam";
    $linewidth=200;
    $lineheight=30;
        $texdir = $site_home.'/tex';
    $basename='tablecards'.'_'.$prj_id.'_'.$milestone.'_'.$grp_num;
    $filename = $basename.'.tex';
    $pdfname  =  $basename.'.pdf';
    $fp  =  fopen($texdir.'/'.$filename, "w");
    fwrite($fp,"\n\\input{tablecarddef}%\n\\begin{document}\n");
    $resultSet= $dbConn->Execute($sql);
    if ($resultSet === false) {
      fwrite($fp, "cannot create table cards with \begin{verbatim}$sql, \n reason".$dbConn->ErrorMsg()
              ."\n\end{verbatim}\n");
    } else

    while (!$resultSet->EOF) {
	extract($resultSet->fields);
	$name="$roepnaam $tussenvoegsel $achternaam";
	if (strlen($name) < 10) { $name = '{\\color{white}|}'.$name.'{\\color{white}|}'; }
	$trole=($frole!='')?$frole:$current_role;
	$talias=($fgrpname!='')?$fgrpname:$alias;
	$tcourse=($fcourse!='')?$fcourse:$course_description;
	$latexline="\\tablecard{".$name."}{".$talias.'/'.$trole."}{{\\color[rgb]{.3,0.0,0.3}".$tcourse."}}{".$actor."}\n";
	fwrite($fp,$latexline);
	$resultSet->MoveNext();
    }
    fwrite($fp,"\n\\end{document}");
    fclose($fp);
    $result = @`(cd $texdir; /usr/bin/pdflatex -interaction=batchmode $filename)`;
       $fp = @fopen($texdir.'/'.$pdfname, 'r');
       //   $fp = @fopen($texdir.'/'.$filename, 'r');
    if ($fp != false) {
	if ($debug ) {
	    echo "$name<br/>$mimetype </br>$filename<br/>$sql\n";
	} else {
	    // send the right headers
	    header("Content-type: application/pdf");
	    header("Pragma: public");
	    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	    header("Content-Length: " . filesize($texdir.'/'.$pdfname));
	    header("Content-Disposition: attachment; filename=\"$pdfname\"");
	    
	    // dump the picture and stop the script
	    fpassthru($fp);
	}
	fclose($fp);
	exit;
    }

 }
?>
