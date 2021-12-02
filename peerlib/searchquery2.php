<?php

//require_once 'peerpgdbconnection.php';

/**
 * Create a query for the database.
 *
 * Intended use: The user has entered a number of fields in a search form (with method=POST).
 * Of all the post variables those that are set are used in the query definition.
 * Wild card (* or ?) in the vars are converted in database wildcards '%' and '_'. If wildcard elemenst are used
 * the default relational operator '=' (equals) os changed to like. The query's result will present the data
 * in database column order, because a * is used as in 'select * from &lt;relname&gt;'.
 * To be able to pick the relevant elements from the $_POST array, the column names must be given in a list.
 * @package peerweb
 */
class SearchQuery {

    /**
     * string the relation to search in e.g. MEDEWERKERS
     */
    protected $relation;

    /**
     * array The key columns e.g. MEDEWERKER_CODE
     */
    protected $keyColumns;

    /**
     * nameExpression. The result of a search list is presented through a list of names that are built into a
     * &lt;A HREF... &gt; list. The name expression is returned in the column result_NAME
     * eg. rtrim(ACHTERNAAM,' ')||', '||rtrim(ROEPNAAM,' ')
     */
    protected $nameExpression;

    /**
     * Array, list of columnnames that participate in a match
     * e.g. array( 'ACHTERNAAM', 'ROEPNAAM'), but more typically all columns in the relation
     * which is the default any way. (By setting the relation name)
     * which is turned into where .... and achternaam like 'Hom%'
     */
    protected $matchColumnSet;

    /**
     * submitValueSet
     * array that holds the values entered by the user in the html form.
     * e.g. $_POST or $_GET
     */
    protected $submitValueSet;

    /**
     * It is nice to get the things ordered. This is the list that does it.
     *  array e.eg array('achternaam','roepnaam')
     */
    protected $orderList;

    /**
     * $dbConn
     */
    protected PDO $dbConn;
    protected $queryExtension;

    function setQueryExtension( $qe ) {
        $this->queryExtension = $qe;
        return $this;
    }

    function getQueryExtension() {
        return $this->queryExtension;
    }

    /**
     * constructor
     * @param $dbConn connection to use in queries
     * @param relname relation to select from
     */
    function __construct( PDO $dbConn, $relName ) {
        $this->dbConn = $dbConn;
        $this->setRelation( $relName );
    }

    private $log = '';

    function getLog() {
        return $this->log;
    }

    /* meuk */

    /**
     * cache: assoc to hold the names of the columns
     */
    protected $columnNames;
    protected $dataTypes;
    protected $relPrefix;

    public function getRelPrefix() {
        return $this->relPrefix;
    }

    /**
     * sets the relation for this search.
     * @param $relName string name of the relation (tabel or view)
     */
    function setRelation( $relName ) {
        $this->relation = $relName;
        $this->relPrefix = substr( $this->relation, 0, 2 ) . '_';
        $query = "select column_name,data_type "
                . "from information_schema.columns "
                . "where table_name=?";
        $dbMessage = '';
        $this->matchColumnSet = array();

        $this->columnNames = array();
        $this->dataTypes = array();
        $pstm = $this->dbConn->prepare( $query );
        $pstm->execute( [ $this->relation ] );
        while ( ($row = $pstm->fetch()) !== false ) {
            $name = trim( $row[ 'column_name' ] );
            $this->matchColumnSet[] = $name;
            $this->columnNames[ $name ] = $name;
            $this->dataTypes[ $name ] = $row[ 'data_type' ];
        }
        return $this;
    }

    protected $keyColumnNames;

    /**
     * Set the list of names that are key in this relation.
     * @param $kc array of columnNames
     */
    function setKeyColumns( $kc ) {
        $this->keyColumns = $kc;
        $this->keyColumnNames = array();
        for ( $i = 0; $i < count( $kc ); $i++ ) {
            $this->keyColumnNames[ $kc[ $i ] ] = $kc[ $i ];
        }
//        $this->log .= "<br/>key columns<pre>" . print_r($kc, true) . "</pre><br/>";
//        $this->log .= "<br/>key columns names<pre>" . print_r($this->keyColumnNames, true) . "</pre><br/>";
        return $this;
    }

    /**
     * Test if all keyColumns have a value
     * @return boolean true if all key Columns have a value
     */
    function areKeyColumnsSet() {
        $result = true;
        for ( $i = 0; $i < count( $this->keyColumns ); $i++ ) {
            if ( !isSet( $this->submitValueSet[ $this->keyColumns[ $i ] ] ) || $this->submitValueSet[ $this->keyColumns[ $i ] ] == '' ) {
                return false;
            }
        }
        return $result;
    }

    /**
     * The nameExpression is the sql expression that is returned by executing the query (on the database)
     * name NAME_RESULT. It's purpose is to create a name for the <a href...></a> link.
     */
    function setNameExpression( $expr ) {
        $this->nameExpression = $expr;
        return $this;
    }

    /**
     * set the values submitted by the client
     * @param $vs valueset: array of key-values pairs
     * This function copies the data and constructs a hash map of the key value pairs.
     */
    function setSubmitValueSet( $vs ) {
        if ( !isSet( $vs ) || $vs === null )
            return;
        $this->submitValueSet = array();
        foreach ( $vs as $key => $value ) {
            $skey = trim( $key );
            $sval = trim( $value );
            if ( $sval != '' ) {
                $this->submitValueSet[ $skey ] = $sval;
            }
        }
        // recompute
        $this->queryTailText = null;
        return $this;
    }

    function getSubmitValueSet() {
        return $this->submitValueSet;
    }

    /**
     * In a supporting join query the concept aux col names is used.
     * The term denotes the columns in the supporting relation
     */
    protected $auxColNames;

    /**
     * additional columns for result list
     */
    function setAuxColNames( $acn ) {
        $this->auxColNames = $acn;
        return $this;
    }

    /**
     * The order list determines the way a search list is sorted
     * @param $ol array of column names.
     */
    function setOrderList( $ol ) {
        $this->orderList = $ol;
        return $this;
    }

    /**
     * Test if a string could pass as a regex expression.
     * Implementation only checks if there is a regex dot followed by a multiplier  (*,+, or ?) somewhere.
     * @return true if regex
     */
    function isRegex( $str ) {
        // match if a character is followed by a regex multiplier.
        $r = array();
        if ( preg_match( '/[.+*?]/', $str, $r, 0, 1 ) ) {
            return true;
        }
        return false;
    }

    /**
     * gets the where ... part without the where.
     * @return a string containing the where clause without the word 'where'
     */
    private function getWhereList() {
        $whereClause = '';
        $continuation = '';
        $rp = $this->relPrefix;
        for ( $i = 0; $i < count( $this->matchColumnSet ); $i++ ) {
            $name = $this->matchColumnSet[ $i ];
            $value = '';
            if ( isSet( $this->submitValueSet[ $name ] ) ) {
                $value = $this->submitValueSet[ $name ];
                $valueIsRegex = $this->isRegex( $value );
                if ( $value != '' ) {
                    $type = $this->dataTypes[ $name ];
                    switch ( $type ) {
                        case 'bool':
                            $nvalue = (isSet( $value ) && ($value != 'false')) ? 'true' : 'false';
                            $whereClause .= $continuation . $rp . '.' . $name . ' = ' . $nvalue . ' ';
                            break;
                        case 'int2':
                        case 'int4':
                        case 'int8':
                            if ( $valueIsRegex ) {
                                $whereClause .= $continuation . $rp . '.' . $name . '::text ~* E\'^' . $value . '$\' ';
                            } else {
                                $whereClause .= $continuation . $rp . '.' . $name . ' =  ' . $value . ' ';
                            }
                            break;
                        case 'bpchar':
                        case 'varchar':
                        case 'date':
                        case 'text':
                        default:
                            if ( $valueIsRegex ) {
                                $whereClause .= $continuation . $rp . '.' . $name . '::text ~* E\'^' . $value . '$\' ';
                            } else {
                                $whereClause .= $continuation . $rp . '.' . $name . '::text ilike \'' . $value . '\' ';
                            }
                            break;
                            break;
                    }
                    $continuation = ' and ' . "\n";
                }
            }
        }
        return $whereClause;
    }

    /* getWhereList() */

    /**
     * gets the query part starting with from
     * @return string 'from ......'
     */
    private function getQueryTail() {
        $result = '';
        $whereClause = $this->getWhereList();
        if ( strlen( $whereClause ) > 0 ) {
            $result .= "\n where " . $whereClause;
        }
        $continuation = ' ';
        if ( isSet( $this->orderList ) ) {
            $result .= "\n order by ";
            for ( $i = 0; $i < count( $this->orderList ); $i++ ) {
                $result .= $continuation . $this->orderList[ $i ];
                $continuation = ', ';
            }
        }
        return $result;
    }

    private function getQueryHead() {
        $result = 'select ' . $this->nameExpression . ' as RESULT_NAME ';
        $continuation = ",\n   ";
        for ( $i = 0; $i < count( $this->keyColumns ); $i++ ) {
            $result .= $continuation . $this->relPrefix . '.' . $this->keyColumns[ $i ];
        }
        if ( isSet( $this->auxColNames ) ) {
            foreach ( $this->auxColNames as $expr => $auxColName ) {
                /* drop name if already added via keyColumnNames */
                //if (!isSet($this->keyColumnNames[$auxColName])) {
                $result .= $continuation . $auxColName;
                //}
            }
        }
        //$this->log .="query head={$result}";
        return $result;
    }

    /**
     * Gets the query.
     * drop doubles in result columnNames
     * @return string 'select .....' i.e. the query
     */
    private function getQuery() {
        $result = $this->getQueryHead()
                . '\n from \n'
                . $this->relation . '\n ' . $this->relPrefix . '\n '
                . $this->getQueryTail();
        return $result;
    }

    public function __toString() {
        return $this->getQueryTailText();
    }

    private $subRel = null;
    private $subRelJoinColumns = null;

    /**
     * An expression A that can serve part in a join (A) sub_rel on (...) subquery.
     * @param type $s
     * @return this searchquery
     */
    public function setSubRel( $s ) {
        if ( $s !== '' ) {
            $this->subRel = $s;
        }
        return $this;
    }

    /**
     * Set array that maps left part of join to right part.
     * @param array. Keys are left hand, values right hand column names $a
     * @return this \SearchQuery
     */
    public function setSubRelJoinColumns( $a ) {
        if ( is_array( $a ) ) {
            $this->subRelJoinColumns = $a;
        }
        return $this;
    }

    private function getExtendedQueryTail() {
        return $this->relation . ' ' . $this->relPrefix
                . ' ' . $this->subRelExpression() . ' '
                . $this->getQueryExtension() . ' '
                . $this->getQueryTail();
    }

    public function getExtendedQuery() {

        return $this->getQueryHead() . ' from '
                . $this->getQueryTailText();
    }

    /**
     * Execute the extended query. An extended query combines primary tables with details from other tables or views.
     * @return result set (resource)
     * @throws SQLPrepareException on prepared text error
     * @throws SQLExevuteException on execution error
     */
    public function executeExtendedQuery(): PDOStatement {
        $qt = $this->getExtendedQuery();
        $rs = $this->dbConn->prepare( $qt );
        $rs->execute( $this->values );
        return $rs;
    }

    public function getAllQuery() {
        return "select * from \n" . $this->getExtendedQueryTail();
    }

    /**
     * Execute the query and return a result set.
     * @return \PeerResultSet of this query 
     */
    public function executeAllQuery() {
        return $this->dbConn->Execute( $this->getAllQuery() );
    }

    private $queryTailText = null;
    private $values = null;

    private function prepareQueryTailText() {
        $values = array();
        $whereTerms = array();
        $valueCtr = 1;
        $rp = $this->relPrefix;
        for ( $i = 0; $i < count( $this->matchColumnSet ); $i++ ) {
            $name = $this->matchColumnSet[ $i ];
            $value = '';
            if ( isSet( $this->submitValueSet[ $name ] ) ) {
                $value = $this->submitValueSet[ $name ];
                $valueIsRegex = $this->isRegex( $value );
                if ( $value != '' ) {
                    $type = $this->dataTypes[ $name ];
                    switch ( $type ) {
                        case 'bool':
                            $nvalue = (isSet( $value ) && ($value != 'false')) ? 'true' : 'false';
                            $whereTerms[] = "{$rp}.{$name} = $" . $valueCtr++;
                            $values[] = $nvalue;
                            break;
                        case 'int2':
                        case 'int4':
                        case 'int8':
                            if ( $valueIsRegex ) {
                                $whereTerms[] = "{$rp}.{$name}::text ~* $" . $valueCtr++;
                                $values[] = "^" . $value . "$";
                            } else {
                                $whereTerms[] .= "{$rp}.{$name} =  $" . $valueCtr++;
                                $values[] = $value;
                            }
                            break;
                        case 'bpchar':
                        case 'varchar':
                        case 'date':
                        case 'text':
                        default:
                            if ( $valueIsRegex ) {
                                $whereTerms[] = "{$rp}.{$name}::text ~* $" . $valueCtr++;
                                $values[] = "^" . $value . "$";
                            } else {
                                $whereTerms[] .= "{$rp}.{$name}::text ilike $" . $valueCtr++;
                                $values[] = $value;
                            }
                            break;
                    }
                }
            }
        }
        $this->values = $values;
        $whereClause = join( "\n and ", $whereTerms );
        $orderBy = isSet( $this->orderList ) ? ' order by ' . join( ',', $this->orderList ) : '';

        $q = $this->relation . ' ' . $this->relPrefix
                . ' ' . $this->subRelExpression() . ' '
                . $this->getQueryExtension();
        if ( $whereClause != '' ) {
            $q .= " \n where " . $whereClause;
        }
        $q .= $orderBy;
        return $q;
    }

    function getQueryTailText() {
        if ( $this->queryTailText === null ) {
            $this->queryTailText = $this->prepareQueryTailText();
        }
        return $this->queryTailText;
    }

    function setQueryTailText( $tt ) {
        $this->queryTailText = $tt;
        return $this;
    }

    function getPreparedValues() {
        return $this->values;
    }

    function setPreparedValues( $nv ) {
        if ( is_array( $nv ) ) {
            $this->values = $nv;
        } else {
            throw new Exception( "{$nv} is not an array" );
        }
        return $this;
    }

    public function executeAllQuery2(): PDOStatement {
        $q = "select * from " . $this->getQueryTailText(); //.' limit 1';
        $pstm = $this->dbConn->prepare( $q );
        $pstm->execute( $this->values );
        return $pstm;
    }

    public function getSubRelQuery(): string {
        return 'select sub_rel.* '
                . " \n from \n"
                . $this->getExtendedQueryTail();
    }

    public function subRelExpression(): string {

        $subRelExpr = '';
        $rpf = $this->relPrefix;
        if ( isSet( $this->subRel ) && isSet( $this->subRelJoinColumns ) ) {
            $joinOn = "";
            $joinGlue = '';
            foreach ( $this->subRelJoinColumns as $left => $right ) {
                $joinOn .= $joinGlue . " {$rpf}.{$left}=sub_rel.{$right}";
                $joinGlue = " and \n";
            }
            $subRelExpr = " \nleft join $this->subRel sub_rel on ($joinOn) ";
        }
        return $subRelExpr;
    }

}

/* end of class SearchQuery */

/**
 * compose and update query
 *
 * An update query is used to update a record  in the database.
 */
class UpdateQuery extends SearchQuery {

    /**
     * The update set is the set of column-names,columnvalues that have to be updated
     * Test if the columnNames exist, but throw out keyColumns, since they should
     * not change through an update.
     */
    protected $updateSet;

    /**
     * Set the update set.
     * @param $us the update set (hashmap key=> value)
     */
    function setUpdateSet( $us ) {
        $this->updateSet = array();
        foreach ( $us as $key => $value ) {
            $key = trim( $key );
            $value = trim( $value );
            if ( isSet( $this->columnNames[ $key ] ) && !isSet( $this->keyColumnNames[ $key ] ) ) {
                $this->updateSet[ $key ] = $value;
            }
        }
    }

    /**
     * Execute the query using prepared statement style.
     * @return PeerResultSet type resultset of excute.
     */
    private function prepareAndExecute() {
        $parmCtr = 1;
        $values = array();
        $columnExpr = array();
        $query = "update {$this->relation} set \n";
        foreach ( $this->updateSet as $key => $value ) {
            $columnExpr[] = "{$key} = $" . $parmCtr++;
            $values[] = $value !== '' ? $value : NULL;
        }
        $whereClause = " where ";
        $whereExpr = array();
        for ( $i = 0; $i < count( $this->keyColumns ); $i++ ) {
            $name = $this->keyColumns[ $i ];
            $value = $this->submitValueSet[ $name ];
            if ( $value != '' ) {
                $whereExpr[] = "{$name}=$" . $parmCtr++;
                $values[] = $value;
            }
        }
        $whereClause .= join( ' and ', $whereExpr );
        $query .= join( ', ', $columnExpr ) . "\n"
                . $whereClause;

        $stmnt = $this->dbConn->prepare( $query );
        return $stmnt->execute( $values );
    }

    function __toString() {
        return getQuery();
    }

    /**
     * Execute the query and returns a resultset.
     * @return mixed resultSet of the query.
     */
    function execute() {
        return $this->prepareAndExecute(); // $this->dbConn->Execute( $this->getQuery() );
    }

}

/**
 * Insert queries are special in that you have to verify that all key columns are set.
 */
class InsertQuery extends SearchQuery {

    /**
     * build an array of the requested updates.
     * Test if the columnNames in update set exist.
     */
    protected $updateSet;

    /**
     * set the values submitted by the client
     * @param $vs valueset: array of key-values pairs
     * This function copies the data and constructs a hash map of the key value pairs.
     */
    function setUpdateSet( $us ) {
        reset( $us );
        $this->updateSet = array();
        foreach ( $us as $key => $value ) {
            $key = trim( $key );
            $value = trim( $value );
            if ( isSet( $this->columnNames[ $key ] ) ) {
                $this->updateSet[ $key ] = $value;
            }
        }
    }

    /**
     * test if all keyColumns have a value
     * @return boolean true if all keycolumns are set.
     */
    function areKeyColumnsSet() {
        $result = true;
        for ( $i = 0; $i < count( $this->keyColumns ); $i++ ) {
            if ( !isSet( $this->updateSet[ $this->keyColumns[ $i ] ] ) || $this->updateSet[ $this->keyColumns[ $i ] ] == '' ) {
                error_log( "key columns not all set" );
                return false;
            }
        }
        return $result;
    }

    private $values = null;

    public function getValues() {

        return $this->values;
    }

    private $queryText = null;

    /**
     * Get the query based on the relation information and the submitted values.
     * The submitted values are collected in the values array.
     * @return the text.
     */
    private function getQueryText() {
        if ( $this->queryText == null ) {
            $query = "insert into {$this->relation} (";
            $columns = array();
            $this->values = array();
            $params = array(); // the $1... params  in the query text
            $paramCtr = 1;
            foreach ( $this->updateSet as $key => $value ) {
                // the test ensures that non set values take their default or null.
                if ( $key != '' && $value != null ) {
                    $columns[] = $key;
                    $this->values[] = $value;
                    $params[] = '$' . $paramCtr++;
                }
            }
            $query .= join( ',', $columns ) . ") \n values(" . join( ',', $params ) . ')';
            $this->queryText = $query;
        }
        return $this->queryText;
    }

    /**
     * 
     * @return PeerResultSet when successful
     */
    private function prepareAndExecute() {
        if ( !$this->areKeyColumnsSet() ) {
            throw new SQLExecuteException( "not all key columns have been set" );
        }
        $query = $this->getQueryText();
        $stmnt = $this->dbConn->Prepare( $query, '' );
        return $stmnt->execute( $this->values );
    }

    function __toString() {
        return $this->getQueryText() . " with values:<pre>" . print_r( $this->columnNames, true ) . "</pre>";
    }

    /**
     * Execute the insert query and return a resultSet.
     * @return PeerResultSet resultSet
     */
    public function execute() {
        return $this->prepareAndExecute();
    }

}

/**
 * To delete no more than intended mak sure taht all key columns are set so that only
 * one record is deleted
 */
class DeleteQuery extends UpdateQuery {

    private $complete = true;
    private $keysList = array();
    private $values = array();
    private $requestValues = array();

    function getQueryText() {
        $queryText = "delete from {$this->relation} where ";
        $this->complete = true;
        $this->keysList = array();
        $this->values = array();
        $this->requestValues = array();
        $kctr = 1;
        $this->requestValues = array_merge( $this->requestValues, $_POST );
        $this->requestValues = array_merge( $this->requestValues, $_GET );
        foreach ( $this->keyColumns as $key ) {
            if ( isSet( $this->requestValues[ $key ] ) ) {
                $this->keysList[] = "{$key}=\$" . $kctr;
                $kctr++;
                $this->values[] = $this->requestValues[ $key ];
            } else {
                $this->complete = false;
                break;
            }
        }
        $queryText .= join( ' and ', $this->keysList );
        return $queryText;
    }

    function execute() {
        if ( !$this->complete ) {
            throw new SQLExecuteException( 'DB ERROR: Delete failed. Not all keyColumns have been set' );
        } else {
            $res = $this->dbConn->Prepare( $this->getQueryText() )->execute( $this->values );
        }
        return $res;
    }

}

/**
 * Compose a query that maps (foreign) keys of one table
 * to keys of another. The join is normally a left join. See the relevant database literature for
 * an exposee on left joins.
 */
class SupportingJoinQuery {

    protected $relation;

    function setRelation( $rel ) {
        $this->relation = $rel;
    }

    /**
     * In a left join column values of the 'left' table should be equal to colum values of the 'right'
     * table. However the columns in the different tables could be named differently. Example manager,
     * who is employee in the employee table but mentioned in the column manager in the same employee table.
     * In that case to get the full manager data with an employee, the left table would be employees,
     * the right table would be employees as wel, but the left column would be manager, the right
     * the employee id of the manager.
     */
    protected $keyMap;

    /**
     * set keyMap
     */
    function setKeyMap( $map ) {
        //    stacktrace();
        $this->keyMap = $map;
    }

    /**
     * set values
     */
    protected $submitValueSet;

    /**
     * set the values submitted by the client
     * @param $vs valueset: array of key-values pairs
     * This function copies the data and constructs a hash map of the key value pairs.
     */
    function setSubmitValueSet( $vs ) {
        //    stacktrace();
        $this->submitValueSet = array();
        foreach ( $vs as $key => $value ) {
            $skey = trim( $key );
            $sval = trim( $value );
            $this->submitValueSet[ $skey ] = $sval;
        }
    }

    /**
     * compose the query
     * map fk to pk, and get fk-val
     * @return string the query to submit to the database.
     */
    function getQuery() {
        $result = 'select * from ' . $this->relation . ' ' . $this->relPrefix . ' ' . ' where ';
        $tail = '';
        $continuation = '';
        foreach ( $this->keyMap as $fkey => $pkey ) {
            if ( $fkey != '' && $pkey != '' ) {
                $val = $this->submitValueSet[ $fkey ];
                if ( isSet( $val ) && $val != '' ) {
                    $tail .= $continuation . $pkey . '=\'' . $val . '\'';
                } else {
                    $tail .= $continuation . $pkey . ' isnull';
                }
                $continuation = ' and ';
            }
        }
        if ( $tail == '' ) {
            return $tail;
        } else {
            return $result . $tail;
        }
    }

}

/* $Id: searchquery2.php 1860 2015-07-27 08:18:07Z hom $ */

