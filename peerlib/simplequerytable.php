<?php

function simpleTableString( PDO $dbConn, $query, $tabledef = "<table summary='simple table'>" ) {
    global $ADODB_FETCH_MODE;
    $result = '';
    $rowC = 1;
    $coltypes = array();
    $columnNames = array();
    $sth = $dbConn->query( $query );
//    $resultSet = $sth->fetchAll();

    if ( $sth === false ) {
        $result .= "Cannot read table data with \n\t<pre style='color:#800;'>" . $query . " </pre>\n\treason \n\t" . $dbConn->errorInfo() . "at\n";
        stacktrace( 1 );
        $result .= "</pre>";
        return $result;
    }
    $colcount = $sth->columnCount();
    $result .= "$tabledef\n";
    $result .= "<thead>\n<tr>\n";
    for ( $i = 0; $i < $colcount; $i++ ) {
        $fieldMeta = $sth->getColumnMeta( $i );
        $columnNames[ $i ] = $fieldMeta[ 'name' ];
        $result .= "\t\t<th class='tabledata head' style='text-algin:left;'>{$fieldMeta[ 'name' ]}</th>\n";
        $columntypes[ $i ] = $fieldMeta[ 'native_type' ];
        $sums[ $i ] = 0;
    }
    $result .= "</tr>\n</thead>\n<tbody>\n";
    while ( ($row = $sth->fetch()) !== false ) {
        $result .= "\t<tr>\n";
        for ( $i = 0; $i < $colcount; $i++ ) {
            $val=$row[ $columnNames[$i] ];
            $val = isSet( $val) ? trim( $val ) : '';
            if ( substr( $val, 0, 1 ) != '<' ) {
                $val = $val;
            }
            if ( (substr( $val, 0, 1 ) == '{') && (substr( $val, -1 ) == '}') ) {
                $val = substr( $val, 1, strlen( $val ) - 2 );
                $val = substr( $val, 0, strlen( $val ) - 2 );
                $a = explode( ',', $val );
                $val = '<td>' . implode( '</td><td>', $a ) . '</td>';
            }
            $tdclass = 'tabledata';
            //echo "columntype={$columntypes[$i]}<br/>";
            switch ( $columntypes[ $i ] ) {
                case 'int2':
                case 'int4':
                case 'integer':
                case 'numeric':
                case 'float':
                case 'real';
                case 'N':
                    $tdclass .= ' num';
                    if ( $val !== '' ) {
                        $sums[ $i ] += $val;
                    }
                    break;
                default:
                    break;
            }
            $result .= "\t\t<td class='$tdclass'>" . $val . "</td>\n";
        }
        $result .= "\t</tr>\n";
        $rowC++;
//        $resultSet->MoveNext();
    }
    $result .= "</tbody>\n</table>\n";
//    $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
    return $result;
}

/* simpletablestring */

function simpletable( $dbConn, $query, $tabledef = "<table summary='simple table'>" ) {
    echo simpleTableString( $dbConn, $query, $tabledef );
}
