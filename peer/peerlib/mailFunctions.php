<?php
/**
 * Send an html formatted email from a form.
 * @param $dbConn connection
 * @param $sql query. Must produce email1 and name, optional email2.
 * @param $fsubject subject
 * @param $body mail body
 * @param $sender sender email address
 * @param $sender_name sic.
 * and open the template in the editor.
 */
function formMailer($dbConn, $sql, $fsubject, $body, $sender, $sender_name) {
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        $dbConn->log("<br>Cannot read mail data with <pre>" . $sql
                . "</pre> reason " . $dbConn->ErrorMsg() . "<br>");
        return;
    }
    $recipients = "";
    $triggerSubject = 'You have mail at your fontys email address';
    $triggerBody = "See the subject. Read your mail"
            . " e.g. at <a href=\'http://webmail.fontys.nl\'>"
            . "http://webmail.fontys.nl</a><br/>Kind regards, the peerweb service.";
    $bodypostfix = "\n</body>\n</html>\n";
    while (!$resultSet->EOF) {
        extract($resultSet->fields);
        $headers = htmlmailheaders($sender, $sender_name, $email1, $tutor_email);
        $recipients .= "$name ($email1)\n";
        // eval user created subject and body template
        eval("\$subject=\"$fsubject\";");
        eval("\$message=\"$body\";");

        $bodyprefix = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>' . $subject . '</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" >
</head>
<body>';

        domail("$name <$email1>", $subject, $bodyprefix . $message . $bodypostfix, $headers);
        $resultSet->moveNext();
    }
    $headers = htmlmailheaders($sender, $sender_name, $sender, $tutor_email);
    domail($sender, 'your copy of mail ' . $subject, $bodyprefix . $message 
                . "\n<br/><hr/>The above mail has been sent to the following recipients:\n<pre>\n" 
            . $recipients . "</pre>\n" . $bodypostfix, $headers);
}

/**
 * Create a form, process data.
 */
class FormMailer {

    /**
     * Create a form mailer using dbConn, mail template and senderid
     * @param dbConn connection to use.
     * @param mailtemplatefile, file to read template from.
     * @param senderid, id of sender (snummer) to retreive email address,
     * name and signature.
     */
    public function __construct($dbConn, $mailtemplatefile, $senderid) {
        
    }

    /**
     * @return a form stringto be put on a page.
     */
    public function createForm() {
        
    }

    /**
     * Process the response from the user. (Filled in form, selected users).
     */
    public function processResponse() {
        
    }

}

?>
