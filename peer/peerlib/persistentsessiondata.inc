<?php

/**
 * save and resore session data for user convenience
 */

/**
 * To be called on logout
 */
function savesessiondata($dbConn, $user) {
    unset($_SESSION['password']);
    unset($_SESSION['userCap']);
    unset($_SESSION['userfile']); // forget filename
    $sessionstring = base64_encode(gzdeflate(session_encode()));
    $sql = "begin work;\n";
    $dbConn->Execute($sql);
    $sql = "delete from session_data where snummer=$user;\n";
    $dbConn->Execute($sql);
    $sql = "insert into session_data (snummer,session) values($user,'$sessionstring');\n";
    $dbConn->Execute($sql);
    $sql = "commit";
    $dbConn->Execute($sql);
}

/**
 * To be called right after login and succesful authentication
 */
function restoresessiondata($dbConn, $user) {
    $sql = "select session from session_data where snummer=$user";
    $resultSet = $dbConn->Execute($sql);
    if (!$resultSet->EOF) {
        session_decode(gzinflate(base64_decode($resultSet->fields['session'])));
    }
}

/* $Id: persistentsessiondata.inc 1769 2014-08-01 10:04:30Z hom $ */
?>