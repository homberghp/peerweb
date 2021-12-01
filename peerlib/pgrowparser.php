<?php

//require_once("peerutils.php");

/**
 * Used in queryToXlsx.php.
 */
interface RowParser {

    /**
     * @param mixed $pgResultSet postgresql resultset with possibly arrays.
     * @return array of result or false at end
     */
    function parse( PDOStatement $pgResultSet ): mixed;

    /**
     * Produces an array of strings to be used as headers for a table.
     */
    function parseToTableHeader( PDOStatement $pstm, array $headers = null ): array;

    /**
     *  Retrieves the data types for the row columns, assuming them all the same.
     * Returns the Adodb type identifiers. see {http://phplens.com/lens/adodb/docs-adodb.htm#metatype} 
     */
    function parseTypes( PDOStatement $pstm ): array;

    function done( PDOStatement $pstm ): bool;
}

/**
 * for rows without arrays.
 */
class DefaultRowParser implements RowParser {

    private $completed = false;

    public function parse( PDOStatement $pgResultSet ): mixed {
        $result = $pgResultSet->fetch();
        $this->completed = ($result === false);
        return $result;
    }

    public function parseToTableHeader( PDOStatement $pstm, array $headers = null ): array {
        $result = array();
        $colcount = $pstm->columnCount();
        for ( $i = 0; $i < $colcount; $i++ ) {
            $fieldMeta = $pstm->getColumnMeta( $i );
            $name = $fieldMeta[ 'name' ];
            array_push( $result, $name );
        }
        return $result;
    }

    public function parseTypes( PDOStatement $pstm ): array {
        $result = array();
        $colcount = $pstm->columnCount();

        for ( $i = 0; $i < $colcount; $i++ ) {
            $type = $pstm->getColumnMeta( $i )[ 'native_type' ];
            array_push( $result, $type );
        }
        return $result;
    }

    public function done( PDOStatement $pstm ): bool {
        return $this->completed;
    }

}

class RowWithArraysParser implements RowParser {

    private $columnCellCount;
    private $completed = false;

    public function __construct() {
        $this->columnCellCount = array();
    }

    public function parse( PDOStatement $pstm ): mixed {
        $result = array();
        $colcount = $pstm->columnCount();
        $nr = $pstm->fetch();
        if ( $nr === $false ) {
            $this->completed = true;
            return $nr;
        }
        for ( $i = 0; $i < $colcount; $i++ ) {
            $field = $nr[ $i ];
            if ( '{' == substr( $field, 0, 1 ) && '}' == substr( $field, -1 ) ) {
                $s = str_replace( '{', '', $field );
                $s = str_replace( '}', '', $s );
                $helper = preg_split( '/\s*,\s*/', $s );
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

    public function parseToTableHeader( PDOStatement $pstm, array $headers = null ): array {
        $result = array();
        $colcount = $pstm->columnCount();
        for ( $i = 0; $i < $colcount; $i++ ) {
            $helper = array();
            $fieldName = niceName( $pstm->getColumnMeta( $i )[ 'name' ] );
            $field = $pstm->fields[ $i ];
            if ( '{' == substr( $field, 0, 1 ) && '}' == substr( $field, -1 ) ) {
                $s = str_replace( '{', '', $field );
                $s = str_replace( '}', '', $s );
                $helper = preg_split( '/\s*,\s*/', $s );

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

    public function parseTypes( PDOStatement $pstm ): array {
        $result = array();
        $colcount = $pstm->columnCount();
        for ( $i = 0; $i < $colcount; $i++ ) {
            //$fieldMeta = $pgResultSet->FetchField($i);
            //print_r($field);
            $type = $pstm->getColumnMeta( $i )[ 'natural_type' ];
            $field = $pstm->fields[ $i ];
            if ( '{' == substr( $field, 0, 1 ) && '}' == substr( $field, -1 ) ) {
                error_log( "composite type=" . $type, 0 );
                $s = str_replace( '{', '', $field );
                $s = str_replace( '}', '', $s );
                $helper = preg_split( '/\s*,\s*/', $s );
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

    public function done( PDOStatement $pstm ): bool {
        return $this->completed;
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
    public function parseToTableHeader( PDOStatement $pgResultSet, array $headers = null ): array {
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
