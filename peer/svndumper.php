<?php
  // This script dumps a repository to the webbrowser, advertising it as basename(repo)-date.tar.bz2

  // Parameters are retreived using post
  // userid must be owner of repos
$prepos_id=0;
$isSystem=hasCap(CAP_SYSTEM)?'true':'false';
extract($_SESSION);
if (isSet($_REQUEST['repos_id'])) {
    $repos_id=validate($_REQUEST['repos_id'],'integer',$act_id);
 }    
$sql = "select owner,repospath,url_tail,id,youngest from personal_repos where id=$repos_id and (owner='$snummer' or $isSystem) ";
$resultSet = $dbConn->Execute($sql);
if ($resultSet=== false) {
  die('Error: '.$dbConn->ErrorMsg().' with '.$sql);
 }

if (!$resultSet->EOF) {
  $dumpname='tarfile.tar.bz2';
  extract($resultSet->fields);
  $repossubdir=basename($repospath);
  $dumpname=$owner. '-'.basename($repossubdir).'-'.date('Y-m-d').'.tar.bz2';
  $reposparent=dirname($repospath);
  // open stream with proper name and execute tar cmnd
  header("Pragma: no-cache");
  header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
  header( 'Content-Description: File Download' );
  header( 'Content-Type: application/octet-stream' );
  //	header( 'Content-Length: '.trim(`stat -c%s "$file"`) );
  header( 'Content-Disposition: attachment; filename="'. $dumpname .'"' );
  header( 'Content-Transfer-Encoding: binary' );
  $fp = popen("cd $reposparent; /bin/tar -cjf - $repossubdir",'r' );
  fpassthru($fp);
  
  pclose($fp);

 } else {
?>
<html><head><title>Oh ooh, sorry</title></head>
<body>
    Sorry <?=$snummer?>, for some reason you cannot have the repository dump.
</body>
</html>
<?php
}
?>