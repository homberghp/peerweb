<?php

//require_once 'peerpgdbconnection.php';
/**
 * save and resore session data for user convenience
 */

/**
 * To be called on logout
 * @param \PeerPGDBConnection $dbConn
 */
function savesessiondata( PDO $dbConn, $user ) {
    unset( $_SESSION[ 'password' ] ); // throw out
    unset( $_SESSION[ 'userCap' ] );
    unset( $_SESSION[ 'userfile' ] ); // forget filename
    $sessionstring = base64_encode( gzdeflate( session_encode() ) );
    $sql = <<<'SQL'
    insert into session_data (snummer,session) values(?,?)
    on conflict(snummer) do update  set session=EXCLUDED.session
SQL;
    $dbConn->prepare( $sql )->execute( [ $user, $sessionstring ] );
}

/**
 * To be called right after login and succesful authentication
 */
function restoresessiondata( PDO $dbConn, $user ) {
    $sql = 'select session from session_data where snummer=?';
    $sth = $dbConn->prepare( $sql );
    if ( $sth->execute( [ $user ] ) ) {
        $result = $sth->fetch();

        session_decode( gzinflate( base64_decode( $result[0] ) ) );
    }
}

/* $Id: persistentsessiondata.php 1769 2014-08-01 10:04:30Z hom $ */
