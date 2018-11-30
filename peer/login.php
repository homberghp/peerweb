<?php

require_once 'TemplateWith.php';
if (is_file($site_dir . '/CLOSED')) {
    require_once 'templates/loginclosed.html';
    exit;
}


/**
 * prepended by all
 * if not logged in, append query string to $PHP_SELF, to present the parameters to the same page again
 */
session_start();
//include_once('peerutils.php');
require_once('makeauthentication.php');
require_once 'persistentsessiondata.php';
$loginError = 0;
$loginattempt = false;
if (isSet($_POST['peer_id']) && isSet($_POST['peer_pw'])) {
    $login_id = validate($_POST['peer_id'], 'peer_id', 0);
    if ($login_id != 0) {
        $_SESSION['peer_id'] = $login_id;
        $peer_pw = $_POST['peer_pw'];
        $_SESSION['logfilename'] = $site_home . '/log/log_u' . $_SESSION['peer_id'] . '_' . date('YmdHis') . '.txt';
        $loginattempt = true;
    }
    unset($login_id);
}
if (isSet($_SESSION['logfilename'])) {
    $dbConn->setLogFilename($_SESSION['logfilename']);
}
if (isSet($_SESSION['peer_id']) && isSet($peer_pw) && ( ($loginError = authenticate($_SESSION['peer_id'], $peer_pw)) == 0)) {

    restoresessiondata($dbConn, $_SESSION['peer_id']);
    if (!isSet($_SESSION['snummer'])) {
        $snummer = $_SESSION['snummer'] = $_SESSION['peer_id'];
    }

    $_SESSION['auth_user'] = $_SESSION['peer_id'];
    $_SESSION['logfilename'] = $site_home . '/log/log_u' . $_SESSION['peer_id'] . '_' . date('YmdHis') . '.txt';
    $dbConn->setLogFilename($_SESSION['logfilename']);
    // build a new peertree at login 
    unset($_SESSION["NodesHasBeenAddedUrl"]);

    // record login
    $from_ip = $_SERVER['REMOTE_ADDR'];
    if ($loginattempt) {
        $sql1 = "select userid,date_trunc('seconds',since) as since,id,from_ip from logon where userid={$_SESSION['peer_id']} order by id desc limit 1";
        $resultSet = $dbConn->Execute($sql1);
        if (!($resultSet === false) && !$resultSet->EOF) {
            $_SESSION['last_login_record'] = $resultSet->fields;
        }
        $peer_id = $_SESSION['peer_id'];
        $sql = "insert into logon (userid,from_ip) values ( $peer_id, '$from_ip' )";
        $resultSet = $dbConn->Execute($sql);
        $resultSet = $dbConn->Execute($sql1);
        if (!($resultSet === false) && !$resultSet->EOF) {
            $_SESSION['newest_login_record'] = $resultSet->fields;
            if (!isSet($_SESSION['last_login_record']))
                $_SESSION['last_login_record'] = $resultSet->fields;
        }
    }
    if (!(isSet($_SESSION['prj_id']) && isSet($_SESSION['milestone']))) {
        // HACK
        $sql = "select prj_id,milestone from project_grp_stakeholders join all_prj_tutor using(prjtg_id) where snummer=$peer_id order by prj_id desc,milestone limit 1";
        $resultSet = $dbConn->Execute($sql);
        if ($resultSet === false) {
            echo('cannot get tutor data:' . $dbConn->ErrorMsg() . ' with ' . $sql);
            stacktrace(1);
            die();
        }
        if (!$resultSet->EOF) {
            extract($resultSet->fields);
        } else {
            $prj_id = 1;
            $milestone = 1;
        }
        $_SESSION['prj_id'] = $prj_id;
        $_SESSION['milestone'] = $milestone;
    }
} else if (( isSet($_SESSION['peer_id']) && isSet($_SESSION['password'])) && authenticateCrypt($_SESSION['peer_id'], $_SESSION['password']) != 0) {
    unSet($_SESSION['auth_user']);
    unSet($_SESSION['peer_id']);
    unSet($_SESSION['password']);
}
if (isSet($_SESSION['peer_id'])) {
    $peer_id = $_SESSION['peer_id'];
}
if (isSet($_SESSION['userCap'])) {
    $userCap = $_SESSION['userCap'];
    if ($userCap == 0 && isSet($_SESSION['peer_id']))
        $_SESSION['snummer'] = $_SESSION['peer_id'];
}

if (!isSet($_SESSION['auth_user'])) { // make login screen
    //    pagehead('Peerweb login');
    $action_uri = $_SERVER['REQUEST_URI'];
    $templatefile = 'templates/logintemplate.html';
    $result = '';
    if (isSet($_REQUEST['baccessrequest'])) {
        $result = makenewlogincode($_REQUEST['newlogincode'], $_REQUEST['secret']);
    }
    $ktipicon = IMAGEROOT . '/ktip.png';
    $pdficon = IMAGEROOT . '/pdf.png';
    $template_text = file_get_contents($templatefile, true);
    if ($template_text === false) {
        $form1Form->addText("<strong>cannot read template file $templatefile</strong>");
    } else {
        echo templateWith($template_text, get_defined_vars());
    }

    exit;
} // else continue with includer
// get login user data prefixed with login_
$sql = "select * from student s left join tutor t on(s.snummer=t.userid) where snummer=$peer_id";
$resultSet = $dbConn->Execute($sql);
$LOGINDATA = array();
if ($resultSet !== false && !$resultSet->EOF) {
    $LOGINDATA = array_merge($LOGINDATA, $resultSet->fields);
    extract($resultSet->fields, EXTR_PREFIX_ALL, 'login');
}
if (basename($PHP_SELF) == 'login.php') {
    header("location: $root_url/index.php");
}
?>