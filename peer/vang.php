<?php
  //  phpinfo();
require_once('peerutils.php');
if (isSet($_REQUEST['id']) &&
    isSet($_REQUEST['boxes'])) {
  $id=$_REQUEST['id'];
  $sql1 = "select id, \n".
    "rtrim(coalesce(ju.diplvo,j.diplvo)) as diplvo,\n".
    "rtrim(coalesce(ju.cijfer,j.cijfer)) as cijfer,\n".
    "rtrim(coalesce(ju.betbew,j.betbew)) as betbew,\n".
    "rtrim(coalesce(ju.pasprt,j.pasprt)) as pasprt,\n".
    "rtrim(coalesce(ju.uittre,j.uittre)) as uittre, \n".
    "rtrim(coalesce(ju.renrij,j.renrij)) as renrij, \n".
    "coalesce(trans_id,0) as trans_id from jaaglijst j \n".
    "left join (select * from  jaaglijst_update where id=$id and trans_id=(select max(trans_id) ".
    "from jaaglijst_update where id=$id)) ju using(id)\n"."where id=$id\n";
  $dbConn->log($sql1);
  $resultSet=$dbConn->execute($sql1);
  if ($resultSet === false ) {
    echo "cannot execlute <pre>$sql1</pre>, cause <pre>".$dbConn->ErrorMsg()."</pre>\n";
  }
  $mustInsert=false;
  $ov = array();
  $ov['diplvo'] = $resultSet->fields['diplvo'];
  $ov['cijfer'] = $resultSet->fields['cijfer'];
  $ov['betbew'] = $resultSet->fields['betbew'];
  $ov['pasprt'] = $resultSet->fields['pasprt'];
  $ov['uittre'] = $resultSet->fields['uittre'];
  $ov['renrij'] = $resultSet->fields['renrij'];
  // echo "<pre>\n$sql1</pre>\n";
  // echo "<pre>\nov=";
  // print_r($resultSet->fields);
  // print_r($ov);

  foreach ($_REQUEST['boxes'] as $box) {
    if (isSet($_REQUEST[$box])) {
      $ov[$box] = 'Ingeleverd'; 
    } else {
      $ov[$box] = 'Gestart'; 
    }
    if ($resultSet->fields[$box] != $ov[$box] ) { 
      $mustInsert = $mustInsert || true; 
    }
  }
  if ($mustInsert) {
    $trans_id = $dbConn->transactionStart('jaaglijst update');
    extract($ov);
    $sql = "insert into jaaglijst_update (id,cijfer,betbew,diplvo,pasprt,uittre,renrij,trans_id)\n".
      "values($id ,'$cijfer','$betbew','$diplvo','$pasprt','$uittre','$renrij',$trans_id);\n";
    $dbConn->log($sql);
    $rts=$dbConn->execute($sql);      
    if ($rts===false){
      $dbConn->Execute("rollback;");
      die("Cannot get update with $sql cause ".$dbConn->ErrorMsg());
    } else {
      $dbConn->transactionEnd();
    }
  }
  // // echo "<pre>\nov=";
  // print_r($ov);
  // echo $mustInsert.' + '.$sql;
  // echo "</pre>\n";
 }
header('Location: '.$_SERVER['HTTP_REFERER']); 
?>