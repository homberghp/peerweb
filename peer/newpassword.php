<?php
include_once('./peerlib/peerutils.inc');
$username=$auth_user;
$snummer=2037775;
$sql= "select roepnaam,voorvoegsel,achternaam,email1\n".
  "from student where snummer=$snummer";
// outputs the username that owns the running php/httpd process
// (on a system with the "whoami" executable in the path)
$resultSet= $dbConn->execute($sql);
if ($resultSet=== false) {
  die('<br/>cannot get data:'.$dbConn->ErrorMsg().' with '.
      "<pre>$sql</pre>,\ncause ".$dbConn->ErrorMsg()."<br/>");
 }
if ( !$resultSet->EOF )  {
    // There is a group, prj_id and milestone are valid in assessment table
  extract($resultSet->fields);
  $filename='s'.$snummer.'.tex';
  $password=system('/usr/bin/genpasswd Bab11Ba@b');
  $handle = fopen("$filename", "w");
  $notestring='\\briefje{'.$achternaam.','.$roepnaam.' '.
    $voorvoegsel.'}{'.$snummer.'}{peerweb}{'.$password."}\n";
  fwrite($handle,"\\input{notestart}%\n");
  fwrite($handle,$notestring);
  fwrite($handle,"\\input{notesend}\n");
  fclose($handle);
  echo exec('/usr/bin/pdfxlatex '."$site_dir/$filename");
 }
?> 