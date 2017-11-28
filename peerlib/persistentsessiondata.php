<?php

require_once 'peerpgdbconnection.php';
/**
 * save and resore session data for user convenience
 */

/**
 * To be called on logout
 * @param \PeerPGDBConnection $dbConn
 */
function savesessiondata(PeerPGDBConnection $dbConn, $user) {
    unset($_SESSION['password']); // throw out
    unset($_SESSION['userCap']);
    unset($_SESSION['userfile']); // forget filename
    $sessionstring = base64_encode(gzdeflate(session_encode()));
    $sql = <<<'SQL'
insert into session_data (snummer,session) values($1,$2)
    on conflict(snummer) do update  set session=EXCLUDED.session
SQL;
    $dbConn->Prepare($sql)->execute(array($user, $sessionstring));
}

/**
 * To be called right after login and succesful authentication
 */
function restoresessiondata($dbConn, $user) {
    $sql = 'select session from session_data where snummer=$1';
    $resultSet = $dbConn->Prepare($sql)->execute(array($user));
    if (!$resultSet->EOF) {
        session_decode(gzinflate(base64_decode($resultSet->fields['session'])));
    }
}

/* $Id: persistentsessiondata.php 1769 2014-08-01 10:04:30Z hom $ */
