<?php
  /**
   * create a set of authz rules for a project repository
   */
class Authz {
  private $dbConn;
  private $prj_id;
  private $milestone;
  private $divname='authz';
  /**
   * Constructor with connection, $prj_id and $milestone
   */
  public function __construct($dbConn,$prj_id,$milestone) {
    $this->dbConn=$dbConn;
    $this->prj_id= $prj_id;
    $this->milestone = $milestone;
  }
  
  public function show() {
    $sql="select groupname,username from svn_group \n".
      "where prj_id=$this->prj_id and milestone=$this->milestone order by groupname,username";
    //    echo $sql;
    $ogname='';
    $con=' = ';
    $resultSet= $this->dbConn->Execute($sql);
    if ($resultSet=== false) {
      die('Error: '.$dbConn->ErrorMsg().' with '.$sql);
    }
    $ghash = array();
    while (! $resultSet->EOF ) {
      extract($resultSet->fields);
      if ($ogname != $groupname) {
	if ($ogname != '') {
	  $ghash[$ogname] = $gline;
	}
	$ogname=$groupname;
	$con=' = ';
	$gline=$groupname;
      }
      $gline .= $con."$username";
      $con=',';
      $resultSet->moveNext();
    
    }
    // pick up remainder
    if ($ogname != '') {
      $ghash[$ogname] = $gline;
    }
    // get tutors
    $sql="select username,tutor from svn_tutor \n".
      "where prj_id=$this->prj_id and milestone=$this->milestone";
    $resultSet= $this->dbConn->Execute($sql);
    if ($resultSet=== false) {
      die('Error: '.$dbConn->ErrorMsg().' with '.$sql);
    }
    $admins ='';
    $con='';
    while (! $resultSet->EOF ) {
      extract($resultSet->fields);
      $admins .= $con.$username;
      $con=',';
      $resultSet->moveNext();
    }    


    echo "<pre class='screen' style='border:4px;border-style:inset; width:800px;".
      "background:white;font-family:courier;font-size=12pt;overflow:scroll;' id='$this->divname'>\n";
    echo "[groups]\n";
    echo "tutor = $admins\n";
    foreach ($ghash as $key => $val) {
      echo "$val\n";
    }
    echo "\n";
    foreach ($ghash as $key => $val) {
      echo "\n[/$key]\n".
	"@$key = rw\n".
	"@tutor = r\n".
	"* =\n";
    }
    echo "\n\n</pre>\n";
 
    
  } /* show */
  }
?>