<?php
  /* $Id: groupresult.inc 1723 2014-01-03 08:34:59Z hom $ */

  /**
   * dispaly criteria in header
   * @param colspan: columns to cover per criterium
   */
function criteriaHead3($criteria,$lang,$rainbow,$colspan=1) {
    $startColor= $rainbow->restart();
    $crit=1;
    $result='';
    foreach ($criteria as $value ) {
	$result .= "\t<th  class='navleft' align='left' colspan='$colspan' style='background:".$startColor.";'>$crit ".$value[$lang.'_short']."</th>\n";
	$startColor = $rainbow->getNext();
	$crit++;
    }
    return $result;
}

function criteriaSubHead($criteria,$lang,$rainbow,$colspan,
			 $headers=array('grade','group','mult')) {
    $startColor= $rainbow->restart();
    $result='';
    foreach ($criteria as $value ) {
	foreach ($headers as $head) {
	    $result .= "\t<th colspan='$colspan' style='background:".$startColor.
		";'>$head</th>\n";
	}
	$startColor =$rainbow->getNext();
    }
    return $result;
}
function avgFoot($avg,$rainbow) {
    $startColor= $rainbow->restart();
    $result ='';
    foreach ($avg as $value ) {
	$result .= "\t<th style='text-align:right;background:".$startColor.";'>$value</th>".
	    "<th style='text-align:right;background:".$startColor.";'></th>\n";
	$startColor =$rainbow->getNext();
    }
    return $result;
}

/**
 * results with avg and multiplier
 */
function groupResultTable( $dbConn, $prj_id,$milestone,$grp_num,$overall_criterium,$productGrade, $header, $criteria, $lang , $rainbow, $addlink=false ){
    echo getGroupResultTable( $dbConn, $prj_id,$milestone,$grp_num,$overall_criterium,$productGrade, $header, $criteria, $lang , $rainbow, $addlink );
}
function getGroupResultTable( $dbConn, $prj_id,$milestone,$grp_num,$overall_criterium,$productGrade, 
			      $header, $criteria, $lang , $rainbow , $addlink=false){
    global $root_url;
    $sql = "select afko,year,description,prjtg_id from project natural join prj_milestone natural join prj_tutor"
      ." where prj_id=$prj_id and milestone=$milestone and grp_num=$grp_num";
    $resultSet=$dbConn->Execute($sql);
    if (!$resultSet->EOF ) extract($resultSet->fields);
    $sql = "SELECT snummer as contestant,roepnaam||' '||coalesce(voorvoegsel,'')||' '||achternaam as naam,\n".
	"to_char(commit_time,'YYYY-MM-DD&nbsp;HH24:MI')as commit_time,".
	"prj_id,grp_num,criterium,milestone,round(grade,2) as grade, round(grp_avg,2) as grp_avg, \n".
	"case when grp_avg<>0 then round(grade/grp_avg,2) else 1 end as multiplier,achternaam,prj_grp.prj_grp_open as open,role \n".
	"from student join stdresult using(snummer) join grp_average using(prj_id,criterium,milestone,grp_num) \n".
	"join prj_grp using(snummer,prj_id,milestone,grp_num)\n".
	"left join last_assessment_commit using(snummer,prj_id,milestone)".
	"left join student_role using (snummer,prj_id,milestone)\n".
	"left join project_roles using(prj_id,rolenum)\n".
	"where prj_id=$prj_id and milestone=$milestone and grp_num='$grp_num' \n".
	"union \n".
	"select snummer as contestant,roepnaam||' '||coalesce(voorvoegsel,'')||' '||achternaam as naam,\n".
	"to_char(commit_time,'YYYY-MM-DD HH24:MI:SS')as commit_time,".
	"prj_id,grp_num,$overall_criterium as criterium,milestone,\n".
	"case when grp_avg<>0 then round($productGrade*grade/grp_avg,2) else 0 end as grade, \n".
	"round($productGrade,2) as grp_avg, \n".
	"case when grp_avg<>0 then round(grade/grp_avg,2) else 1 end as multiplier,\n".
	"achternaam,prj_grp.prj_grp_open as open,role \n".
	"from student join stdresult_overall using(snummer) join \n".
	"grp_overall_average using (prj_id,milestone,grp_num) ".
	"join prj_grp using(snummer,prj_id,milestone,grp_num)\n".
	"left join student_role using (snummer,prj_id,milestone)\n".
	"left join project_roles using(prj_id,rolenum)\n".
	"left join last_assessment_commit using(snummer,prj_id,milestone)".
	"where prj_id=$prj_id and milestone=$milestone and grp_num='$grp_num' \n".
	"order by achternaam,contestant,criterium";
    $result='<!-- start groupResultTable-->'."\n";
    //    $dbConn->log($sql);
    $resultSet2=$dbConn->Execute($sql);
    $oldContestant=0;
    $avg=array();
    if ( $resultSet2 === false ) {
	$dbConn->logError("cannot get result data with $sql, cause ".$dbConn->ErrorMsg());
	$result .= "<h1>Sorry, no data available (db)</h1>\n";
	return $result;
    } else if ( $resultSet2->EOF ) {
	$result .= "<h1>Sorry, no data available (empty)</h1>\n";
	return $result;
    }
    $result .= "\n<table  id='groupresult' border='1' class='tabledata' width='100%' summary='group result'>\n";
    $result .= "\n<thead style='background:white;'>\n";
    $result .= "\t<caption style='font-size:14pt;font-weight:bold;'>"
      ."Group assessment result for project/milestone<br/> $afko $year \"$description\" milestone $milestone group $grp_num ($prjtg_id) with average grade [$productGrade]<caption>\n";
    $result .= "<colgroup>\n";
    $result .= "\t<col width='8%'/>\n".
	"\t<col width='17%'/>\n".
	"\t<col width='13%'/>\n".
	"\t<col/>\n";
    $rainbow->restart();
    $color=$rainbow->getCurrent();
    // this is not reliable under firefox 1.07
    for ($i=0; $i < count($criteria); $i++) {
	$result .= "\t<col style='background:$color'/>\n".
	    "\t<col style='background:$color'/>\n";
	$color = $rainbow->getNext();
    }
    
    $result .= "</colgroup>\n";
    if($header) {
      	$result .= "<tr><td colspan='4'></td>\n";
// 	$result .= "\t<th align='left'>Number</th>\n";
// 	$result .= "\t<th align='left'>Student</th>\n";
// 	$result .= "\t<th align='left'>Role</th>\n";
// 	$result .= "\t<th align='left'>Open</th>\n";
	$result .= criteriaHead3($criteria,$lang,$rainbow,2);
	$result .= "</tr>\n";
	$result .= "<tr>\n";
	$result .= "\t<th align='left'>Number</th>\n";
	$result .= "\t<th align='left'>Student</th>\n";
	$result .= "\t<th align='left'>Role</th>\n";
	$result .= "\t<th align='left'>Open</th>\n";
	$result .= criteriaSubHead($criteria,$lang,$rainbow,1,
			array('grade','mult'));
	$result .= "</tr>\n";
    }
	$result .= "\n</thead>\n";
    $continuation='';
    $rainbow->restart();
    $color=$rainbow->getCurrent();
    $colCounter=0;
    while(!$resultSet2->EOF) {
	$contestant = $resultSet2->fields['contestant'];
	if ($oldContestant !== $contestant) {
	    extract($resultSet2->fields);
	    $result .= $continuation;
	    $result .= "<tr>\n";
	    $result .= "\t<td>$contestant</td>\n";
	    if (isSet($commit_time)) {
		$result .= "\t<td title='$naam: committed at $commit_time'>";
	    } else {
		$result .= "\t<td title='$naam: never committed' style='background:#F00;color:#FFF'>";
	    }
	    if ($addlink) {
		$astyle=isSet($commit_time)?"style='text-decoration:none'":"style='text-decoration:line-through'";
		$result .="<a href='$root_url/ipeer.php?prj_id_milestone=$prj_id:$milestone&amp;".
		    "snummer=$contestant' $astyle target='_blank' >";
	    }
	    $result .= $naam;
	    if ($addlink)
		$result .="</a>";
	    $result .= "</td>\n";
	    $result .= "\t<td>$role</td>\n";
	    $open=($resultSet2->fields['open']=='t')?'open':'closed';
	    $result .= "\t<td>".$open."</td>\n";
	    $continuation= "</tr>\n";
	    $color=$rainbow->restart();
	    $colCounter=0;
	}
	$oldContestant = $contestant;
	$grade = $resultSet2->fields['grade'];
	$criterium =$resultSet2->fields['criterium'];
	$grp_num = $resultSet2->fields['grp_num'];
	$grp_avg = $resultSet2->fields['grp_avg'];
	$multiplier = $resultSet2->fields['multiplier'];
	//$result .= "\t<td align='right'>$grade</td>\n"; //for firefox >= 1.5
	$result .= "\t<td align='right'  style='background:$color'>$grade</td>\n";
	$avg[$colCounter]=$grp_avg;
	//$result .= "\t<td align='right'>$multiplier</td>\n"; //for firefox >= 1.5
	$result .= "\t<td align='right' style='background:$color;'>$multiplier</td>\n";
	
	$color = $rainbow->getNext();
	$resultSet2->moveNext();
	$colCounter++;
    }   
    $result .= $continuation;
    if($header) {
	$result .= "<tr>\n";
	$result .= "\t<th colspan='4' class='tabledata head'>Group average</th>\n";
	$result .= avgFoot($avg,$rainbow);
	$result .= "</tr>\n";
    }
    $result .= "</table>\n".'<!-- end groupResultTable-->';

    return $result;
} // groupresulttable
?>