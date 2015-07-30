<?php
require_once 'peerlib/peerutils.inc';
require_once 'peerlib/imageresize.php';
//
// phpinfo();
//setlocale(LC_CTYPE,'en_US.UTF-8');
//setlocale(LC_ALL,'en_US.UTF-8','en_EN');
//passthru('export LC_CTYPE=en_US.UTF-8;/usr/bin/locale');
echo"<br/>\n";
$testfile='part/one(two)[three];four| five';
echo $testfile."<br/>\n";
echo "result=".sanitizeFilename($testfile)."<br/>\n";
$sql ="select upload_id,rel_file_path,mime_type from uploads where prjm_id=385 and mime_type ='image/jpeg'";

$resultSet = $dbConn->Execute($sql);
if ($result !== false) {
    while(!$resultSet->EOF){
        extract($resultSet->fields);
        $source =$upload_path_prefix.'/'.$rel_file_path;
        echo "upload_id=$upload_id   $mime_type $source <br/>\n";
        $resized = imageresize($source);
        if ($resized > 0) {
            $sql2="update uploads set filesize=$resized where upload_id=$upload_id";
            $dbConn->Execute($sql2);
            echo "RESIZED<br/>";
        }
        $resultSet->moveNext();
    }
    
}
//passthru('/home/hombergp/testsite/peer/locale.sh');

//$finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
//foreach (glob("*") as $filename) {
//  echo "$filename=".finfo_file($finfo, $filename) . "\n";
//}
//finfo_close($finfo);

?>