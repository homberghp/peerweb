<?php

/**
 * @param $snummer
 * @param $doc_id
 * 
 * $snummer and $doc_id are tested for authorisation.
 * The student, represented by snummer is allowed access if
 * \item he is the uploader
 * \item he is list of the group of the project
 * \item he participates in the project, and the due date of the document is passed.
 * Pre: $dbConn is valid, tutor_helper included.
 */
function authorized_document($snummer, $doc_id) {
    global $dbConn;
    global $isTutor;
    if ($isTutor)
        return true;

    $result = false; // optimistic from the server point of view ;-)
    $sql = "select snummer from authorized_document($doc_id) where snummer=$snummer";
    $resultSet = $dbConn->execute($sql);
    if ($resultSet === false) {
        echo "Cannot execute select statement <pre>\"" . $sql . "\"</pre>, cause=" . $dbConn->ErrorMsg() . "\n";
        return $result;
    }
    if ($resultSet->EOF) {
        return $result;
    }
    // we have at least one row
    $result = true;
    return $result;
}

?>