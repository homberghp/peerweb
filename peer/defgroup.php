<?php

include_once('./peerlib/peerutils.php');
include_once('navigation2.php');
require_once('prjMilestoneSelector2.php');

requireCap( CAP_TUTOR );
extract( $_SESSION );
$year = date( 'Y' );
if ( date( 'm' ) < '07' ) {
  $year -= 1;
}
$tutor = $tutor_code;
$milestone = 1;
$class_ids = array( );
$prjSel = new PrjMilestoneSelector2( $dbConn, $peer_id, $prjm_id );
$prjSel->setWhere('valid_until > now()::date')
        ->setExtraInfo("<span style='color:#800'><p>Note that you can only select <a href='alterproject.php'>project</a>s which have a <b>valid until</b> date in the future.</p></span><br/>");

extract( $prjSel->getSelectedData() );
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;

if ( isSet( $_SESSION['prjm_id'] ) ) {
  $sql = "select distinct class_id,cl.sclass as sclass \n" .
          "from prj_grp join student using (snummer) join prj_tutor using(prjtg_id)\n" .
          "  join student_class cl using(class_id)\n" .
          "where prjm_id=$prjm_id\n" .
          " order by sclass,class_id asc";
  $resultSet = $dbConn->Execute( $sql );
  if ( $resultSet === false ) {
    die( "<br>Cannot get groups with \"" . $sql . '", cause ' . $dbConn->ErrorMsg() . "<br>" );
  }
  $gcount = 0;
  while ( !$resultSet->EOF ) {
    $sclass = $resultSet->fields['sclass'];
    $class_id = $resultSet->fields['class_id'];
    $class_ids[$gcount] = $class_id;
    $gcount++;
    //		echo "class_id $class_id<br/>";
    $resultSet->moveNext();
  }
  $_SESSION['class_ids'] = $class_ids;
} else {

  // smart guess
  $sql = "select prj_id,afko,year,description from project\n" .
          " where prj_id=(select max(prj_id) as prj_id from project)";
  $resultSet = $dbConn->Execute( $sql );
  if ( $resultSet === false ) {
    die( "<br>Cannot get smart project data with $sql, cause" . $dbConn->ErrorMsg() . "<br>" );
  }

  if ( $resultSet->EOF ) {
    $prj_id = 0;
  } else {
    extract( $resultSet->fields );
  }
  $_SESSION['prj_id'] = $prj_id;
}

//array_walk($_POST,myprint);
if ( isSet( $_POST['bsubmit'] ) ) {
  if ( isSet( $_POST['class_ids'] ) ) {
    $class_ids = $_POST['class_ids'];
    //	array_walk($class_ids,myprint);
    $sstudent_classet = implode( ",", $class_ids );
    //	echo "sstudent_classet=$sstudent_classet<br>\n";
    // how to change the set of grps.
    // possible elements (U) = ABCDE
    // current set ADE, new set BCE
    // that is remove where not in (new)
    // then add where not in (new) and U.
    $sql = "begin work;\n"
            . "delete from prj_grp where prjtg_id in (select prjtg_id from prj_tutor where prjm_id=$prjm_id) \n"
            . " and (snummer not in\n" .
            "(select snummer from student where class_id in ($sstudent_classet))) and\n" .
            "(snummer not in (select snummer from fixed_student2 where prjm_id=$prjm_id ));\n" .
            "commit";
    //		echo "<br/>sql=$sql<br/>";
    $dbConn->log( $sql );
    $resultSet = $dbConn->Execute( $sql );
    if ( $resultSet === false ) {
      die( "<br>Cannot delete group members " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>" );
    }
  } else {
    $class_ids = array( );
    $sql = "delete from prj_grp where prjtg_id in (select prjtg_id from prj_tutor where prjm_id=$prjm_id)";
    //	echo "<br/>sql=$sql<br/>";
    $resultSet = $dbConn->Execute( $sql );
    if ( $resultSet === false ) {
      echo( "<br>Cannot delete prj/milestone " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>");
      stacktrace( 1 );
      die();
    }
  }

  // get current set and then compute intersection
  $sql = "select distinct class_id from student \n" .
          "where snummer in (select snummer from prj_grp join prj_tutor using(prjtg_id) where " .
          "prjm_id=$prjm_id)";
  //    echo "<br/>sql=$sql<br/>";
  $curSet = array( );
  $resultSet = $dbConn->Execute( $sql );
  if ( $resultSet === false ) {
    die( "<br>Cannot get project values with " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>" );
  }
  while ( !$resultSet->EOF ) {
    $curSet[] = $resultSet->fields['class_id'];
    $resultSet->moveNext();
  }
  //    echo "<br> current set".implode(",",$curSet)."<br>\n";
  $toAdd = implode( ",", array_diff( $class_ids, $curSet ) );
  //echo "toadd=$toAdd<br/>\n";
  // insert new student_class in new prj_grp
  // and give them to a 'new' project_tutor.
  $sql = "select max(grp_num) as new_grp_num from prj_tutor\n" .
          " where prjm_id=$prjm_id";
  $resultSet = $dbConn->Execute( $sql );
  //    $dbConn->log("<pre>sql=$sql</pre>");
  if ( $resultSet === false ) {
    echo( "<br>Cannot get new_grp_num with <pre>" . $sql . "</pre> reason " . $dbConn->ErrorMsg() . "<br>");
    stacktrace( 1 );
    die();
  }
  if ( !$resultSet->EOF ) {
    $new_grp_num = $resultSet->fields['new_grp_num'];
    //echo "re use group [$new_grp_num]<br/>";
  }

  if ( isSet( $new_grp_num ) ) {
    //$dbConn->log( "toadd = $toAdd<br/>" );
    if ( $toAdd != '' ) {
      $sql = "begin work;\n";
      $sql .= "insert into prj_grp (snummer, prjtg_id)\n"
              . "select snummer, pt.prjtg_id \n"
              . "from ( select max(prjtg_id) as prjtg_id from prj_tutor where prjm_id=$prjm_id)  pt cross join \n"
              . " ( select snummer from student where class_id in ($toAdd))  sc where \n"
              . " sc.snummer not in (select snummer from prj_grp pg join prj_tutor using(prjtg_id) where prjm_id=$prjm_id)\n";

      $dbConn->log( "sql=$sql" );
      //echo $sql;
      $resultSet = $dbConn->Execute( $sql );
      if ( $resultSet === false ) {
        echo( "<br>Cannot set project values with<pre>" . $sql . "</pre> reason " . $dbConn->ErrorMsg() . "<br>");
        $dbConn->Execute( "rollback" );
        stacktrace( 1 );
        die();
      } else {
        $dbConn->Execute( "commit" );
      }
    }
  }
  // insert into session
  $_SESSION['class_ids'] = $class_ids;
}
$prj_id = isSet( $_SESSION['prj_id'] ) ? $_SESSION['prj_id'] : -1;
extract( getTutorOwnerData( $dbConn, $prj_id ), EXTR_PREFIX_ALL, 'ot' );
$_SESSION['prj_id'] = $prj_id = $ot_prj_id;
$isTutorOwner = ($ot_tutor == $tutor_code);
if ( $isTutorOwner ) {
  $submit_button = '<button name=\'bsubmit\' value=\'submit\'>Submit</button>';
} else
  $submit_button = '';
$resultSet = $dbConn->execute( "select count(*) as participants from prj_grp join prj_tutor using(prjtg_id) where prjm_id=$prjm_id" );
extract( $resultSet->fields );
// generating output
$page = new PageContainer();
$page->setTitle( 'Select participating student_class' );
$page_opening = "Select the student_class of the participating students";
$nav = new Navigation( $tutor_navtable, basename( $PHP_SELF ), $page_opening );
$page->addBodyComponent( $nav );
$resultSet = $dbConn->Execute( "select afko,description from project where prj_id=$prj_id" );
extract( $resultSet->fields );
$form1Form = new HtmlContainer( "<div id='projectsel'>" );
$prjSel->setJoin( " (select distinct prjm_id from prj_milestone natural join project  where owner_id=$peer_id) p_mil using (prjm_id)" );
//$dbConn->log($prjSel->getQuery());

$form1Form->addText( $prjSel->getWidget() );


$form2Form = new HtmlContainer( "<form method='post' name='group_def' action='$PHP_SELF'>" );
//$form2Table = new HtmlContainer( "<table border='3' style='border-collapse:3d; padding:2pt;'" .
//                " align='left'  rules='groups' frame='box'" .
//                "summary='class selection' id='form2table'>" );
$form2Table = new HtmlContainer( "<div id='tabs'>");
//$form2Form->addText( "Legend:class name [class size]<br/>\n" );

$sql = "select distinct rtrim(student_class.sclass) as sclass,class_id,sort1,sort2,sort_order,\n" .
        "rtrim(faculty.faculty_short) as opl_afko, student_count,rtrim(faculty.faculty_short) as faculty_short,\n" .
        "trim(cluster_name) as cluster_name,class_cluster \n" .
        "from student_class\n" .
        " join faculty using(faculty_id)\n" .
        "join class_cluster using(class_cluster)\n" .
        "join current_student_class using (class_id) join class_size using(class_id)\n " .
        "where sort2 < 9 and sclass not like 'UIT%' \n" .
        "order by sort_order,faculty_short desc,cluster_name,sort1,sort2,sclass asc";
$resultSet = $dbConn->Execute( $sql );
if ( $resultSet === false ) {
  die( "<br>Cannot get groups with \"" . $sql . '", cause ' . $dbConn->ErrorMsg() . "<br>" );
}
//ob_start();
$opl_afko = '';
$colcount = 0;
$curriculum = '';
$complete = false;
$result = '';
$row = '';
$cluster_name='';
if ( !$resultSet->EOF ) {
  $opl_afko = $resultSet->fields['opl_afko'];
  $sort1 = $resultSet->fields['sort1'];
  $sort2 = $resultSet->fields['sort2'];
  $cluster_name = $resultSet->fields['cluster_name'];
}
$divcount=0;
$cluster_name='';
$tablist="<ul>\n";
while ( !$resultSet->EOF ) {
  $opl_afko = $resultSet->fields['opl_afko'];
  if ( $cluster_name != $resultSet->fields['cluster_name']  ) {
    // close cluster
    // append last row
    if ( $curriculum != '' ) {
      $colsleft = (6 - $colcount);
      while ( $colsleft > 0 ){
        $curriculum .="\n\t\t\t<td >&nbsp;</td>";
        $colsleft--;
      }
      $curriculum .= "\n\t\t<tr>\n" 
       . "\t\t</tr></table>\n".
        "<br/><b>Legend:class name [class size]</b>\n</div><!-- end tabs-${divcount} -->\n";
    }
    $divcount++;
    $faculty_short= $resultSet->fields['faculty_short'];
    $cluster_name = $resultSet->fields['cluster_name'];
    $curriculum .="<div id='tabs-${divcount}'>\n<table border='1' style='border-collapse:collapse;'>\n"
              . "\t<thead><tr>"
              . "<th colspan='6' style='align:center'>"
              . "<span style=''>Faculty/cluster/Curriculum</span> $faculty_short/$cluster_name </th>"
              . "</tr></thead>\n\t\t<tr>\n";
    $colcount = 0;
    $tablist .= "\t\t<li><a href='#tabs-${divcount}'>$faculty_short/$cluster_name</a></li>\n";  
  }
  extract( $resultSet->fields );
  
  $checked = '';
  if ( in_array( $class_id, $class_ids ) ) {
    $checked = 'checked';
  }
  $curriculum .= "\n\t\t\t<td >" 
          ."<input type='checkbox' name='class_ids[]' value='$class_id' $checked />&nbsp;" 
          ."<a href='classphoto.php?class_id=$class_id' target='_blank'>$sclass&nbsp;</a>[$student_count]</td>";
  $colcount++;
  if ($colcount > 5) {
    $curriculum .= "\n\t\t</tr>\n";
    $colcount = 0;
  }
  $resultSet->moveNext();

}
if ($colcount > 0){      $colsleft = (6 - $colcount);
    while ( $colsleft > 0 ){
      $curriculum .="\t\t\t<td >&nbsp;</td>\n";
      $colsleft--;
    }
      $curriculum .= "\n\t\t</tr>\n" ;
}
$curriculum .= "</table>\n".
        "<br/><b>Legend:class name [class size]</b>\n".
"</div><!-- end tabs-${divcount} -->\n\n</div><!-- end tabs div -->\n";
$tablist .="\t</ul>\n";


$sqlCount = "select count(*) as membercount from prj_grp join prj_tutor using(prjtg_id) where prjm_id=$prjm_id";
$rsc = $dbConn->Execute( $sqlCount );
$membercount = $rsc->fields['membercount'];
//$result .="</table>\n</div>\n";
$curriculum .= "<table border='0' style='border-collapse:collapse;><thead>  <tr>
<th class='theadleft'>&nbsp;</th>
    <th colspan='1' class=''><input type='reset' name='reset' value='Reset'/></th>
    <th colspan='1' class=''>$submit_button</th>
  </tr>
  <tr>
<th class='theadleft'>&nbsp;</th>
    <th colspan='2' class=''>
      Owning tutor: $ot_owner_id,&nbsp;$ot_roepnaam $ot_voorvoegsel $ot_achternaam
      <input type='hidden' name='prjm_id' value='$prjm_id'/>
    </th>
  </tr></thead>\n<table>\n";

$form2Table->addText( $tablist.$curriculum );
$form2Form->add( $form2Table );

//$form1Table->add( $form1Form );
$form2Fieldset = new HtmlContainer( "<div id='demo' style='margin:2em;background:rgba(255,255,255,0.5);'><b>Current member count =$membercount</b>" );
$form2Fieldset->add( $form2Form );
$page->addBodyComponent( $form1Form );
$page->addBodyComponent( $form2Fieldset );

$page->addBodyComponent( new Component( '<!-- db_name=$db_name $Id: defgroup.php 1829 2014-12-28 19:40:37Z hom $ -->' ) );
$page->addHeadText( '
<link type="text/css" href="css/pepper-grinder/jquery-ui-1.8.17.custom.css" rel="stylesheet" />	
<script type="text/javascript" src="js/jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.17.custom.min.js"></script>
  <script>
	$(function() {
		$( "#tabs" ).tabs();
	});
	</script>
' );
$page->show();
?>
