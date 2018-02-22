<?php

require_once 'peerpgdbconnection.php';
require_once 'TemplateWith.php';

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
        $subject = templateWith($fsubject, $resultSet->fields);
        $message = templateWith($fsubject, $resultSet->fields);

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
 * Create a prepared statement parameter list from a set
 * @param type $set
 */
function setToParamList($set, $firstnumber = 1) {
    $paramset = [];
    $pc = $firstnumber;
    for ($i = 0; $i < count($set); $i++) {
        $paramset[] = '$' . "{$pc}";
        $pc++;
    }
    return join(",", $paramset);
}

/**
 * Create a form, process data.
 */
class FormMailer {

    private $dbConn;
    private $subject;
    private $bodytemplate;
    private $senderid;
    private $bodypostfix = "\n</body>\n</html>\n";

    /**
     * Create a form mailer using dbConn, mail template and senderid
     * @param dbConn connection to use.
     * @param mailtemplate, to be evaluated with query values.
     * @param senderid, id of sender (snummer) to retreive email address,
     * name and signature.
     */
    public function __construct($dbConn, $subject, $bodytemplate, $senderid) {
        $this->dbConn = $dbConn;
        $this->subject = $subject; // preg_replace("/\"/", "'", $subject);
        $this->bodytemplate = $bodytemplate; //preg_replace("/\"/", "'", $bodytemplate);
        $this->senderid = $senderid;
    }

    /**
     * Send the mail with data from a result. The resultSet must contain at 
     * least one column named 'email' and one called name, containing the 
     * full name of the recipient.
     * @param type query.
     * @param argments to prepared statement.
     */
    public function mailWithData($query, $params = []) {
        $recipients = '';
        $sql = "select roepnaam||' '||coalesce(tussenvoegsel||' ','')||achternaam "
                . " as sendername, email1 as sendermail from student where snummer=$this->senderid";
//        echo "<pre>[{$query}]</pre><br/>";
//        echo "<pre> params=".print_r($params, true)."</pre>";
//        echo "<br/>";

        $resultSet = $this->dbConn->Execute($sql);
        if (!$resultSet) {
            echo "cannot get data with {$sql} for " + $dbConn->$dbConn->ErrorMsg() + "\n";
            return;
        }
        extract($resultSet->fields);
        $resultSet = $this->dbConn->Prepare($query)->execute($params);

        while (!$resultSet->EOF) {
            extract($resultSet->fields);

            $recipients .= "{$name} ({$email})\n";
            $headers = htmlmailheaders($sendermail, $sendername, $email);
            //print_r($headers);
            $subject = templateWith($this->subject, $resultSet->fields);
            $message = templateWith($this->bodytemplate, $resultSet->fields);

            //echo "{$subject}<br/>{$message}<br/>";
            $bodyprefix = FormMailer::htmlWrapSubject($subject);
            domail("$name <$email>", $subject, $bodyprefix . $message . $this->bodypostfix, $headers);
            $resultSet->moveNext();
        }
        $headers = htmlmailheaders($sendermail, $sendername, $sendermail);
        domail($sendermail, 'your copy of mail ' . $subject, $bodyprefix . $message
                . "\n<br/><hr/>The above mail has been sent to the following recipients:\n<pre>\n"
                . $recipients . "</pre>\n" . $this->bodypostfix, $headers);
    }

    public static function htmlWrapSubject($subject) {
        return <<<BODY
<!DOCTYPE html>
<html>
<head>
<title>{$subject}</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" >
</head>
<body>
BODY;
    }

    /**
     * @return a form string to be put on a page.
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
