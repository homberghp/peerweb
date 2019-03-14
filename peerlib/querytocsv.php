<?php


/**
 * queryToCSV creates a csv file that is presented as application/msexcel
 * @param $query the query send to the db
 * @param $filename the name for the presented file
 * @param $separator separates the fields in the records, the 'comma' in csv (Comma Separated Values)
 */
function queryToCSV($dbConn, $query, $filename, $separator = ';', $quoteText = false, $content_header = 'Content-type: text/x-comma-separated-values; charset: UTF-8;', $niceName = true) {
    global $ADODB_FETCH_MODE;
    $ADODB_FETCH_MODE = ADODB_FETCH_NUM;
    header("Pragma: public");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header($content_header);
    header('Content-disposition: attachment; filename=' . $filename);
    $resultSet = getFirstRecordSetFields($dbConn,$query);
    $colcount = $resultSet->FieldCount();
    $columntypes = array();
    $tmpSep = '';
    for ($i = 0; $i < $colcount; $i++) {
        $field = $resultSet->FetchField($i);
        if ($niceName) {
            $name = niceName($field->name);
        } else {
            $name = $field->name;
        }
        if ($quoteText) {
            $name = '"' . $name . '"';
        }
        echo $tmpSep . $name;
        $columntypes[$i] = $resultSet->MetaType($i);
        if ($tmpSep == '') {
            $tmpSep = $separator;
        }
    }
    while (!$resultSet->EOF) {
        echo "\n";
        $tmpSep = '';
        for ($i = 0, $max = $resultSet->FieldCount(); $i < $max; $i++) {
            $field = trim($resultSet->fields[$i]);
            switch ($columntypes[$i]) {
                case 'int2':
                case 'integer':
                case 'numeric':
                case 'float':
                case 'real';
                case 'N': print $tmpSep . $field;
                    break;
                default:
                    if ($quoteText && $field) {
                        $field = '"' . $field . '"';
                    }
                    echo $tmpSep . $field;
                    break;
            }
            if ($tmpSep == '') {
                $tmpSep = $separator;
            }
        }
        $resultSet->MoveNext();
    }
    $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
}
