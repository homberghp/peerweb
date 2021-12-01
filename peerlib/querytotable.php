<?php

require_once 'TemplateWith.php';
/*
 * $Id: querytotable.php 1769 2014-08-01 10:04:30Z hom $
 */

function queryToTableChecked2( PDO $dbConn, $query, $numerate, $watchColumn, $rb, $checkName, $checkSet = array(), $inputColumns = array(), $tally = false ): void {
    echo getQueryToTableChecked2( $dbConn, $query, $numerate, $watchColumn, $rb, $checkName, $checkSet, $inputColumns, $tally );
}

/**
 * @param $dbConn database connection to use
 * @param $query, sql statement to produce the table
 * @param $numerate boolean if the table should be numbered
 * @param $watchColumn column which is watched for changes. When changed the color of the rows changes
 * @param $rb rainbow; color set producing object interface rainbow
 * @param $checkName name of the column in the query that proces the check value
 * @param $checkSet set of values in checkcolumn that must have checkbox checked
 * @param $inputColumn array columns that are inputs. In array, type and width is defined
 * @param $tally sum numerical columns
 */
function getQueryToTableChecked2( PDO $dbConn, $query, $numerate, $watchColumn, $rb, $checkName, $checkSet = array(), $inputColumns = array(), $tally = false ): string {
    global $ADODB_FETCH_MODE;
    global $datePickers;
    $result = "<!-- table created by" . '$Id: querytotable.php 1769 2014-08-01 10:04:30Z hom $' . " -->\n";
    $ADODB_FETCH_MODE = ADODB_FETCH_NUM;
    $coltypes = array();
    $sums = array();
    $watchVal = '';
    $rowNr = 0;
    $pickerCount = 0;
    $pstm = $dbConn->query( $query );
    if ( $pstm === false ) {
        $result .= "<pre>Cannot read table data with \n\t{$query}\n\treason \n\t{$dbConn->errorInfo()[ 2 ]}at\n";
        stacktrace( 1 );
        $result .= "</pre>";
    }
    $colcount = $pstm->columnCount();
    if ( !$pstm->EOF && $watchColumn >= 0 && $watchColumn < $colcount )
        $watchVal = $pstm->fields[ $watchColumn ];
    //  $rb = new RainBow(0xFF8844,-20,20,40);
    $result .= "<table class='tabledata' border='1' width='100%' style='empty-cells:show; border-collapse:collapse;' summary='query table'>\n";
    $result .= "<tr>\n";
    if ( $numerate )
        $result .= "\t\t<th class='tabledata head num'>#</th>\n";
    $columnNames = array();
    $hide = false;
    for ( $i = 0; $i < $colcount; $i++ ) {
        $fieldMeta = $pstm->getColumnMeta( $i );
        $columnNames[ $i ] = $fieldMeta[ 'name' ];
        if ( isSet( $inputColumns[ $i ] ) ) {
            $hide = $inputColumns[ $i ][ 'type' ] == 'H';
        } else {
            $hide = false;
        }
        if ( !$hide )
            $result .= "\t\t<th class='tabledata head' style='text-algin:left;'>" . niceName( $fieldMeta->name ) . "</th>\n";
        $columntypes[ $i ] = $fieldMeta[ 'natural_type' ];
        $sums[ $i ] = 0;
    }
    $result .= "</tr>\n";
    $rowColor = $rb->restart();
    $rowstyle = " style='background-color:" . $rowColor . "'";
    while ( ($row = $pstm->fetch()) !== false ) { // process all rows
        $nr = $rowNr + 1;
        if ( $watchColumn >= 0 ) {
            if ( stripslashes( $row[ $watchColumn ] ) != $watchVal ) {
                $rowColor = $rb->getNext();
            }
            $watchVal = stripslashes( $row[ $watchColumn ] );
        }
        $rowstyle = " style='background-color:" . $rowColor . "'";
        $result .= "\t<tr $rowstyle>\n";
        if ( $numerate )
            $result .= "\t\t<td class='tabledata num'>" . $nr . "</td>\n";
        for ( $i = 0, $max = $pstm->columnCount(); $i < $max; $i++ ) {
            $val = isSet( $row[ $i ] ) ? trim( $row[ $i ] ) : '';
            $tdclass = 'tabledata';
            switch ( $columntypes[ $i ] ) {
                case 'I':
                case 'N':
                    $tdclass .= ' num';
                    $sums[ $i ] += $val;
                    break;
                default:
                    break;
            }
            if ( isSet( $inputColumns[ $i ] ) ) {
                $cName = $columnNames[ $i ] . '[]';
                $cSize = $inputColumns[ $i ][ 'size' ];
                $cAlign = ($inputColumns[ $i ][ 'type' ] == 'N') ? 'right' : 'left';
                switch ( $inputColumns[ $i ][ 'type' ] ) {
                    case 'S':
                        // select box. Defined is a querystring that should yield name, value and namegrp as in @see getOptionlistGrouped.
                        // the querystring is "evaluated" to expand references to other columns in the 'parent' query
                        $querytext = $inputColumns[ $i ][ 'option_query' ];
                        $selectedvalue = $inputColumns[ $i ][ 'option_value' ];
                        $selectedvalue = templateWith( $selectedvalues, get_defined_vars() ); // $columnNames)
                        $sql2 = templateWith( $querytext, get_defined_vars() ); //$columnNames)
                        echo "sql2=[$sql2]<br/>\n";
                        $rowText = "\t\t<td  class='$tdclass'>\n" .
                                "\t\t\t<select name='$cName'>\n\t" . getOptionListGrouped( $dbConn, $sql2, $selectedvalue, $selectColumn = 'value', $preload = array( '' => '' ) ) .
                                "\t\t\t</select>\n\t\t</td>\n";
                        break;
                    case 'Z':
                        $rowText = "\t\t<td  class='$tdclass'>\n" .
                                "\t\t\t<select name='$cName'>\n\t";
                        foreach ( $inputColumns[ $i ][ 'options' ] as $key => $value ) {
                            $selected = ($val == $value) ? 'selected' : '';
                            $rowText .= "\t\t<option value='$value' $selected>$key</option>\n";
                        }
                        $rowText .= "\t\t\t</select>\n\t\t</td>\n";
                        break;
                    case 'H':
                        $rowText = "\t\t<td style='display:none'>\n\t\t\t<input type='hidden' name='$cName' value='$val'/>\n\t\t</td>\n";
                        break;
                    case 'B':
                        if ( isSet( $inputColumns[ $i ][ 'colname' ] ) ) {
                            $check_colname = $inputColumns[ $i ][ 'colname' ];
                        } else {
                            $check_colname = $checkName;
                        }
                        $checked = ($val == 't') ? ' checked ' : '';
                        $rowText = "\t\t<td  class='$tdclass'>" .
                                "\n\t\t\t<input type='checkbox' name='" . $check_colname . '_' . $rowNr . "' value='" . $val . "'$checked />\n\t\t</td>\n";
                        break;
                    case 'C':
                        if ( isSet( $inputColumns[ $i ][ 'colname' ] ) ) {
                            $check_colname = $inputColumns[ $i ][ 'colname' ];
                        } else {
                            $check_colname = $checkName;
                        }
                        $checked = in_array( $val, $checkSet ) ? ' checked ' : '';
                        $rowText = "\t\t<td  class='$tdclass'>\n\t\t\t<input type='hidden' name='" . $check_colname . "' value='" . $val . "'/>" .
                                "\n\t\t\t<input type='checkbox' name='checkedrow[]' value='" . $rowNr . "'$checked/>\n\t\t</td>\n";
                        break;
                    case 'R': // rights
                        // value is array of boolean, read rights group, project, world
                        // take value of form {t,f,f} appart
                        if ( isSet( $inputColumns[ $i ][ 'rightsChars' ] ) ) {
                            $rightsChars = $inputColumns[ $i ][ 'rightsChars' ];
                        } else
                            $rightsChars = 'XYZ';
                        $rightsString = '';
                        if ( isSet( $val ) && $val != '' && $val != '{}' ) {
                            $rights = $val;
                            $rightsa = explode( ',', substr( $rights, 1, strlen( $rights ) - 2 ) );
                            for ( $r = 0; $r < count( $rightsa ); $r++ ) {
                                $checked = ($rightsa[ $r ] == 't') ? 'checked' : '';
                                $rightsName = $columnNames[ $i ] . '_' . $rowNr . '_' . $rightsChars[ $r ];
                                $rightsString .= "\n\t\t\t{$rightsChars[ $r ]}<input type='checkbox' name='{$rightsName}' value='t' {$checked} />\n";
                            }
                        }
                        //      $rightsString=$val;
                        $rowText = "\t\t<td class='$tdclass'>$rightsString</td>\n";
                        break;
                    case 'D': // date with picker;
                        $pickerCount = count( $datePickers );
                        $pickerName = 'dp' . $pickerCount;
                        $rowText = "\t\t<td class='$tdclass'>\n\t\t\t<input type='text' placeholder='YYYY-MM-DD' " .
                                "name='$cName' id='$pickerName' style='text-align:right;width:12ex;' value='$val'/>\n\t\t</td>\n";
                        $datePickers[] = $pickerName;
                        break;
                    case 'U':
                        $rowText = "\t\t<td class='$tdclass'>\n\t\t\t<input type='url' placeholder='http://www.example.org' "
                                . "name='$cName' id='$cName' value='$val'/>\n\t\t</td>\n";
                        break;
                    default:
                        $inputType = 'text';
                        $rowText = "\t\t<td class='$tdclass'>\n\t\t\t<input type='$inputType' " .
                                "name='$cName' align='$cAlign' style='width:{$cSize}ex' value='$val'/>\n\t\t</td>\n";
                        break;
                }
            } else {
                $rowText = "\t\t<td class='$tdclass'>" . $val . "</td>\n";
            }
            $result .= "$rowText\n";
        }
        $result .= "\t</tr>\n";
//        $pstm->MoveNext();
        $rowNr++;
    }
    if ( $tally ) {
        $result .= "\t<tr>\n";
        if ( $numerate )
            $result .= "\t\t<th class='tabledata head'></th>\n";
        for ( $i = 0; $i < $colcount; $i++ ) {
            $val = '';
            $tdclass = 'tabledata head';
            switch ( $columntypes[ $i ] ) {
                case 'I':
                case 'N':
                    $val = $sums[ $i ];
                    $tdclass .= ' num';
                    break;
                default:
                    $val = '';
                    break;
            }
            if ( isSet( $inputColumns[ $i ] ) ) {
                $hide = $inputColumns[ $i ][ 'type' ] == 'H';
            } else {
                $hide = false;
            }
            if ( !$hide )
                $result .= "\t\t<th class='$tdclass'>" . $val . "</th>\n";
        }
        $result .= "\t</tr>\n";
    }
    $result .= "</table>\n";
    $result .= "<input type='hidden' name='rowcount' value='$rowNr' />";
//    $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
    return $result;
}

/* queryToTableChecked2 */
?>