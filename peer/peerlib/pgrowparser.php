<?php

//require_once("peerutils.php");

/**
 * Used in queryToXlsx.php.
 */
interface RowParser {

    /**
     * @param mixed $pgResultSet postgresql resultset with possibly arrays.
     * @return array of result
     */
    function parse( $pgResultSet );

    /**
     * Produces an array of strings to be used as headers for a table.
     */
    function parseToTableHeader( $pgResultSet, $headers = null );

    /**
     *  Retreives the data types for the row columns, assuming them all the same.
     * Returns the Adodb type identifiers. see {http://phplens.com/lens/adodb/docs-adodb.htm#metatype} 
     */
    function parseTypes( $pgResultSet );
}

/**
 * for rows without arrays.
 */
class DefaultRowParser implements RowParser {

    public function parse( $pgResultSet ) {
        return $pgResultSet->fields;
    }

    public function parseToTableHeader( $pgResultSet, $headers = null ) {
        $result = array();
        $colcount = $pgResultSet->FieldCount();
        for ( $i = 0; $i < $colcount; $i++ ) {
            $name = $pgResultSet->FieldName( $i );
            array_push( $result, $name );
        }
        return $result;
    }

    public function parseTypes( $pgResultSet ) {
        global $dbConn;
        $result = array();
        $colcount = $pgResultSet->FieldCount();

        for ( $i = 0; $i < $colcount; $i++ ) {
            $type = $pgResultSet->MetaType( $i );
            array_push( $result, $type );
        }
        return $result;
    }

}

class RowWithArraysParser implements RowParser {

    private $columnCellCount;

    public function __construct() {
        $this->columnCellCount = array();
    }

    public function parse( $pgResultSet ) {
        $result = array();
        $colcount = $pgResultSet->FieldCount();
        for ( $i = 0; $i < $colcount; $i++ ) {
            $field = $pgResultSet->fields[ $i ];
            if ( '{' == substr( $field, 0, 1 ) && '}' == substr( $field, -1 ) ) {
                $s = str_replace( '{', 'array(', $field );
                $s = str_replace( '}', ')', $s );
                $s = eval( "\$helper=$s;" );
                $count = count( $helper );
                for ( $j = 0; $j < $count; $j++ ) {
                    $result[] = $helper[ $j ];
                }
            } else if ( !\is_null( $field ) ) {
                $result[] = $field;
            } else {
                for ( $c = 0; $c < $this->columnCellCount[ $i ]; $c++ ) {
                    $result[] = '';
                }
            }
        }
        return $result;
    }

    public function parseToTableHeader( $pgResultSet, $headers = null ) {
        $result = array();
        $colcount = $pgResultSet->FieldCount();
        for ( $i = 0; $i < $colcount; $i++ ) {
            $helper = array();
            $fieldName = niceName( $pgResultSet->FieldName( $i ) );
            $field = $pgResultSet->fields[ $i ];
            if ( '{' == substr( $field, 0, 1 ) && '}' == substr( $field, -1 ) ) {
                $s = str_replace( '{', 'array(', $field );
                $s = str_replace( '}', ')', $s );
                //echo $s;
                $s = eval( "\$helper=$s;" );
                $count = count( $helper );
                $this->columnCellCount[ $i ] = $count;
                for ( $j = 1; $j <= $count; $j++ ) {
                    array_push( $result, $fieldName . $j );
                }
            } else {
                array_push( $result, $fieldName );
                $this->columnCellCount[ $i ] = 1;
            }
        }
        return $result;
    }

    public function parseTypes( $pgResultSet ) {
        global $dbConn;
        $result = array();
        $colcount = $pgResultSet->FieldCount();
        for ( $i = 0; $i < $colcount; $i++ ) {
            //$fieldMeta = $pgResultSet->FetchField($i);
            //print_r($field);
            $type = $pgResultSet->MetaType( $i );
            $field = $pgResultSet->fields[ $i ];
            if ( '{' == substr( $field, 0, 1 ) && '}' == substr( $field, -1 ) ) {
                error_log("composite type=".$type,0);
                $s = str_replace( '{', 'array(', $field );
                $s = str_replace( '}', ')', $s );
                //echo $s;
                $s = eval( "\$helper=$s;" );
                $count = count( $helper );

                for ( $j = 1; $j <= $count; $j++ ) {
                    $result[] = $type;
                }
            } else {
                $result[] = $type;
            }
        }
        return $result;
    }

}

class RowWithArraysPreHeadersParser extends RowWithArraysParser {

    var $headers;

    /**
     * Keep headers for parse headers.
     * @param type $h array of column headers, including arrays for headers.
     */
    public function __construct( $h ) {
        $this->headers = $h;
    }

    /**
     * This assumes headers to be filled with strings and array at 
     * column where array is expected. 
     * @param type $pgResultSet
     * @param type $headers
     * @return array 
     */
    public function parseToTableHeader( $pgResultSet, $headers = null ) {
        $result = array();
        $headers = $this->headers;
        $colcount = count( $headers );
        for ( $i = 0; $i < $colcount; $i++ ) {
            if ( is_array( $headers[ $i ] ) ) {
                for ( $j = 0; $j < count( $headers[ $i ] ); $j++ )
                    $result[] = $headers[ $i ][ $j ];
            } else {
                $result[] = $headers[ $i ];
            }
        }
        return $result;
    }

}

?>