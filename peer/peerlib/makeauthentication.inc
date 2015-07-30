<?php
require_once 'validators.inc';
  /**
   * @param $login code: known user id on database, either studentnumber (snummer) or userid.
   * @return string with result description. To be displayed to the user.
   * @precondition: peerutils is included, database connection is setup and has name $dbConn
   */
function makenewlogincode($logincode,$secret) {
  global $dbConn;
  $result='Creation of a new authentication code failed for '.$logincode.'. You are unknown to this service.';
  // first validate userid
  if (!preg_match('/^\d{1,8}$/',$logincode)) {
      $result=3;
    return $result;
  }
  if ( $secret != validate_date($secret)) {
    $result .= ' 3.1';
    return $result;
  }
  $sql="select rtrim(roepnaam) as roepnaam, rtrim(voorvoegsel) as voorvoegsel,\n".
      "rtrim(achternaam) as achternaam,rtrim(email1) as email,peer_password('Bab11Ba@b') as password from student where snummer='$logincode' and gebdat='$secret'";
  $resultSet= $dbConn->execute($sql);
  if ($resultSet === false) {
    //   echo "Cannot execute select statement <pre>\"".$sql."\"</pre>, cause=".$dbConn->ErrorMsg()."\n";
    $result .= ' 4';
    return $result;
  }
  if ($resultSet->EOF) {
    $result .= ' 5';
    return $result;
  }
  extract($resultSet->fields);
//  $password=`/home/f/fontysvenlo.org/bin/genpasswd Bab11Ba@b`;
  $result = "<strong>A new password for $roepnaam $voorvoegsel $achternaam is sent to email address &lt;$email&gt;</strong>";
  mkpasswordmail($email,$logincode,$roepnaam,$voorvoegsel,$achternaam,$password);
  return $result;
    
}
/**
 * @param $email address
 * @param $logincode key in password table
 * @param $roepnaam, $voorvoegsel,$achternaam name
 * @param $password new password (in plaintext)
 * @return string with failure or succes message
 */
function mkpasswordmail( $email,$logincode,$roepnaam,$voorvoegsel,$achternaam,$password ) {
  global $dbConn;
  global $db_name;
  global $site_home;
  global $root_url;
  if ($db_name == 'peer2') $email=ADMIN_EMAILADDRESS;
  $texdir = $site_home.'/tex/';
  $texoutdir= $texdir.'out/';
  @`mkdir -p $texoutdir`;
  $basename = 's'.$logincode;
  $pdffilename =$basename.'.pdf';
  $basename = $texoutdir.$basename;
  $filename = $basename.'.tex';
  $pdfname  =  $basename.'.pdf';
  $handle  =  fopen("$filename", "w");
  $notestring = '\\briefje{'.$achternaam.','.$roepnaam.' '.
      $voorvoegsel.'}{'.$logincode.'}{'.$db_name.'}{'.$password."}\n";
  fwrite($handle,"\\input{../notestart}%\n");
  fwrite($handle,$notestring);
  fwrite($handle,"\\input{../notesend}\n");
  fclose($handle);
  $handle=fopen($texdir.'maillog', "a");
  $result = @`(cd $texoutdir; /usr/bin/pdflatex $filename)`;
  $makemailcmd="(echo 'authentication data in attachement for $root_url using database $db_name.' "
  ."| /usr/bin/mutt -s 'peerweb authentication' -a $pdfname -- $email)";
  fwrite($handle,$makemailcmd."\n");
  fclose($handle);
  //  $result = @`$makemailcmd`;
  mail_attachment($pdffilename,$texoutdir,$email,'peerweb@fontysvenlo.org','Peerweb service','peerweb authentication',"authentication data in attachement for $root_url using database $db_name.");
  $sql ="begin work;\n".
      "update passwd set password =crypt('$password',gen_salt('des')) where userid='$logincode';\n".
      "insert into password_request (userid) values('$logincode');".
      "commit;";
  $resultSet= $dbConn->execute($sql);
  if ($resultSet === false) {
      echo "Cannot execute update statement <pre>\"".$sql."\"</pre>, cause=".$dbConn->ErrorMsg()."\n";

  }
  return $result; 
}
function mail_attachment($filename,$path, $mailto, $from_mail, $from_name, $replyto, $subject, $message) {
  $file=$path.$filename;
  $file_size = filesize($file);
  $handle = fopen($file, "r");
  $content = fread($handle, $file_size);
  fclose($handle);
  $content = chunk_split(base64_encode($content));
  $uid = md5(uniqid(time()));
  $name = basename($file);
  $header = "From: ".$from_name." <".$from_mail.">\r\n";
  $header .= "Reply-To: ".$replyto."\r\n";
  $header .= "MIME-Version: 1.0\r\n";
  $header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";
  $header .= "This is a multi-part message in MIME format.\r\n";
  $header .= "--".$uid."\r\n";
  $header .= "Content-type:text/plain; charset=iso-8859-1\r\n";
  $header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
  $header .= $message."\r\n\r\n";
  $header .= "--".$uid."\r\n";
  $header .= "Content-Type: application/pdf; name=\"".$filename."\"\r\n"; // use different content types here
  $header .= "Content-Transfer-Encoding: base64\r\n";
  $header .= "Content-Disposition: attachment; filename=\"".$filename."\"\r\n\r\n";
  $header .= $content."\r\n\r\n";
  $header .= "--".$uid."--";
  if (mail($mailto, $subject, "", $header)) {
    echo "mail send ... OK"; // or use booleans here
  } else {
    echo "mail send ... ERROR!";
  }
}
?>