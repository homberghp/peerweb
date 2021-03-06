<?php
require_once 'validators.php';
require_once 'Mail.php' ;
require_once 'Mail/mime.php' ;
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
  $sql="select rtrim(roepnaam) as roepnaam, rtrim(tussenvoegsel) as tussenvoegsel,\n".
      "rtrim(achternaam) as achternaam,rtrim(email1) as email,peer_password('Bab.11Ba@b') as password from student_email where snummer='$logincode' and gebdat='$secret'";
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
  $result = "<strong>A new password for $roepnaam $tussenvoegsel $achternaam is sent to email address &lt;$email&gt;</strong>";
  mkpasswordmail($email,$logincode,$roepnaam,$tussenvoegsel,$achternaam,$password);
  return $result;
    
}
/**
 * @param $email address
 * @param $logincode key in password table
 * @param $roepnaam, $tussenvoegsel,$achternaam name
 * @param $password new password (in plaintext)
 * @return string with failure or succes message
 */
function mkpasswordmail( $email,$logincode,$roepnaam,$tussenvoegsel,$achternaam,$password ) {
  global $dbConn;
  global $db_name;
  global $site_home;
  global $root_url;
  global $debug;
  if ($db_name == 'peer2') $email=ADMIN_EMAILADDRESS;
  $texdir = $site_home.'/tex/';
  $texoutdir= $texdir.'makeauthentication_out/';
  @`mkdir -p $texoutdir`;
  $basename = 's'.$logincode;
  $pdffilename =$basename.'.pdf';
//  $basename = $texoutdir.$basename;
  $filename = $texoutdir.$basename.'.tex';
  $pdffilename  =  $basename.'.pdf';
  $handle  =  fopen("$filename", "w");
  $notestring = "\\SaveVerb{pass}*{$password}*\n" . '\\briefje{'.$achternaam.','.$roepnaam.' '.
      $tussenvoegsel.'}{'.$logincode."}\n";
  fwrite($handle,"\\input{../notestart}%\n");
  fwrite($handle,$notestring);
  fwrite($handle,"\\input{../notesend}\n");
  fclose($handle);
  $result = @`(cd $texoutdir; /usr/bin/pdflatex -interaction=batchmode $filename;/usr/bin/pdflatex -interaction=batchmode $filename)`;

  mail_attachment($pdffilename,$texoutdir,$email,'peerweb@fontysvenlo.org','Peerweb service','peerweb@fontysvenlo.org'
                  ,'peerweb authentication',"authentication data in attachement for $root_url using database $db_name.");
  // cleanup files
  if (! $debug) {
      $result = @`(cd $texoutdir; rm $basename.*)`;
  }
  $cpassword = password_hash($password,PASSWORD_BCRYPT);
  $sql ="begin work;\n".
      "update passwd set password ='{$cpassword}' where userid='$logincode';\n".
      "insert into password_request (userid) values('$logincode');".
      "commit;";
  $resultSet= $dbConn->execute($sql);
  if ($resultSet === false) {
      echo "Cannot execute update statement <pre>\"".$sql."\"</pre>, cause=".$dbConn->ErrorMsg()."\n";

  }
  return $result; 
}


function mail_attachment($filename,$path, $mailto, $from_mail, $from_name, $replyto, $subject, $message) {
  $html = "<html><body>{$message}</body></html>";
  $file = $path.'/'.$filename;
  $crlf = "\n";
  $hdrs = array(
		'From'    => "{$from_name} <{$from_mail}>",
		'Subject' => $subject
		);
  
  $mime = new Mail_mime(array('eol' => $crlf));
  
  $mime->setTXTBody($message);
  $mime->setHTMLBody($html);
  $mime->addAttachment($file, 'application/pdf');
  
  $body = $mime->get();
  $hdrs = $mime->headers($hdrs);
  
  $mail = Mail::factory('mail');
  $mail->send($mailto, $hdrs, $body);
  
}

/**
 * Create a new password request token and insert it into the database.
 * If a token is already is present, and has not expired, 
 * @param type $peerid
 * @return token created
 */
function newPasswordToken($peerid){
    $sql= <<<"EOT"
    insert into password_request_token 
    select $peerid,md5(now()||{$peerid}||(random()*10^15)::text)
EOT;
    
}
