<?php
require_once './peerlib/peerutils.php';
require_once 'TableBuilder.class.php';
require_once 'TaskRowFactory.class.php';

class PersonalTaskRowFactory extends TaskRowFactory {
  /** Overridden */
  public function startRow($valueArray){
    extract($valueArray);
    $this->rowColor = $this->rainbow->getNext();
    return "\t<tr style='background-color:".$this->rowColor
      ."'>\n\t\t<td>$prj_id</td>\n".
      "\t\t<td>$project_name</td>\n".
      "\t\t<td>$project_year</td>\n".
      "\t\t<td>$project_description</td>\n";
  }

  public function buildHeader($data) {
    return "<th>prj_id</th><th>project</th><th>year</th><th>description</th>\n";
  }

}
function taskTable($prj_id, $snummer,$tableBuilder) {
  $sql="select p.prj_id,snummer,"
    ." '#'||task_number||': '||pt.name||': '||pt.description as checktitle,\n"
    ." p.afko as project_name, p.year as project_year,\n"
    ." p.description as project_description,"
    ." pt.name as task_name,\n"
    ." coalesce(grade::text,mark) as check, ptc.comment as title \n"
    ." from project_member\n"
    ." natural join student st \n"
    ." join project p using(prj_id)\n"
    ." join project_task pt using(prj_id)\n"
    ." left join project_task_completed_latest ptc using(task_id,snummer)\n"
    ." where prj_id=$prj_id and snummer=$snummer \n"
    ." order by achternaam,roepnaam, task_number\n";
  $task_table = $tableBuilder->getTable($sql,'snummer');
  global $dbConn;
  $dbConn->log($sql);
  return $task_table;
}
function personalTaskList($dbConn,$snummer) {
  $tableBuilder = new TableBuilder($dbConn,new PersonalTaskRowFactory());
  $sql = "select distinct snummer,prj_id,year,afko \n"
    ."from project_member \n"
    ."join project using(prj_id) \n"
    ."join project_task using(prj_id) \n"
    ." where snummer=$snummer order by year desc,afko";
  $resultSet = $dbConn->Execute( $sql );
  $dbConn->log($sql);
  if ($resultSet === false){ 
    $result= ('error getting  data with <strong><pre>'.$sql.'</pre></strong> reason : '.
	      $dbConn->ErrorMsg().'<BR>');
  } else {
    $result="<table>\n";
    while(!$resultSet->EOF ) {
      $prj_id = $resultSet->fields['prj_id'];
      $result .= "<tr><td>\n".taskTable($prj_id,$snummer,$tableBuilder)."\n</td></tr>\n";
      $resultSet->moveNext();
    }
    $result .= "\n</table>";
  }
  return $result;
}
?>