<?php
require_once 'peerutils.php';
echo pg_escape_string("Hello world nice isn't it");
require_once 'rainbow.php';
echo "<br/>";
$rb= new RainBow();
for ($i=0; $i < $rb->count(); $i++) {
    echo $rb->getCurrentAsARGBString()."<br/>";
    $rb->getNext();
}
function expandListRowTemplate($la){
    $result='';
    $con=', ';
    foreach ($la as $expr => $colName) {
        $result .= $con."$expr as $colName";
    }
    return $result;
}

$a=array('a', 'b', "achternaam||','||roepnaam"=>'C');
$aA=array();
foreach ($a as $key => $value) {
    echo "$key = $value</br>";
}
foreach ($a as $key => $value) {
    if (is_numeric($key)) {
        $aA[$value] = $value;
    } else {
        $aA[$key] = $value;
    }
}
/* print_r($aA); */
/* print_r($a); */
/* echo expandListRowTemplate($aA); */
$dbConn=pg_connect('host=localhost port=5432 user=peerweb dbname=peer2 password=eysGhawfOaw4');
echo 'hallo<br/>';
$pq = pg_prepare($dbConn,'','select * from student where achternaam ~* $1');
echo print_r($dbConn,false)."<br/>";
$rs=pg_execute($dbConn,'',array("den$"));
echo print_r($rs,false)."<br/>";
$nr=pg_num_rows($rs);
echo "found {$nr} rows<br/>";
echo "<pre>";
while($row=pg_fetch_assoc($rs)){
    print_r($row);
}
echo "</pre>";

echo "<br/>done";
//echo pg_affected_rows($rs);
/* while(!$rs->EOF){ */
/*     print_r($s->fields); */
/*     $rs->MoveNext(); */
/* } */
//phpinfo();
//setlocale(LC_CTYPE,'en_US.UTF-8');
//setlocale(LC_ALL,'en_US.UTF-8','en_EN');
//passthru('export LC_CTYPE=en_US.UTF-8;/usr/bin/locale');
/* echo"<br/>\n"; */
/* $testfile='part/one(two)[three];four| five'; */
/* echo $testfile."<br/>\n"; */
/* echo "result=".sanitizeFilename($testfile)."<br/>\n"; */
/* $sql ="select upload_id,rel_file_path,mime_type from uploads where prjm_id=385 and mime_type ='image/jpeg'"; */

/* $resultSet = $dbConn->Execute($sql); */
/* if ($result !== false) { */
/*     while(!$resultSet->EOF){ */
/*         echo "upload_id=$upload_id   $mime_type $rel_file_path <br/>\n"; */
/*         $resultSet->moveNext(); */
/*     } */
    
/* } */
//passthru('/home/hombergp/testsite/peer/locale.sh');

//$finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
//foreach (glob("*") as $filename) {
//  echo "$filename=".finfo_file($finfo, $filename) . "\n";
//}
//finfo_close($finfo);

?>