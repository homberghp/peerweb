<?php

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
     * Where join
     */
    protected $whereJoin = ' and ';

    /**
     * $dbConn
     */
    protected $dbConn;
    protected $queryExtension;

    function setQueryExtension($qe) {
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
    function __construct(&$dbConn, $relName) {
        $this->dbConn = $dbConn;
        $this->setRelation($relName);
    }

    private $log = '';

    function getLog() {
        return $this->log;
    }

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
    function setRelation($relName) {
        //    global $dbConn;
        $this->relation = $relName;
        $this->relPrefix = substr($this->relation, 0, 2) . '_';
        $query = " select column_name,data_type from information_schema.columns where table_name='$this->relation'";
        $dbMessage = '';
        $this->matchColumnSet = array();

        $count = 0;
        $this->columnNames = array();
        $this->dataTypes = array();
        $rs = $this->dbConn->Execute($query);
        while (!$rs->EOF) {
            $name = trim(stripslashes($rs->fields['column_name']));
            $this->matchColumnSet[$count++] = $name;
            $this->columnNames[$name] = $name;
            $this->dataTypes[$name] = $rs->fields['data_type'];

            $rs->moveNext();
        }
        return $this;
    }

    protected $keyColumnNames;

    /**
     * Set the list of names that are key in this relation.
     * @param $kc array of columnNames
     */
    function setKeyColumns($kc) {
        $this->keyColumns = $kc;
        $this->keyColumnNames = array();
        for ($i = 0; $i < count($kc); $i++) {
            $this->keyColumnNames[$kc[$i]] = $kc[$i];
        }
        $this->log .="<br/>key columns<pre>" . print_r($kc, true) . "</pre><br/>";
        $this->log .="<br/>key columns names<pre>" . print_r($this->keyColumnNames, true) . "</pre><br/>";
        return $this;
    }

    /**
     * Test if all keyColumns have a value
     * @return boolean true if all key Columns have a value
     */
    function areKeyColumnsSet() {
        $result = true;
        for ($i = 0; $i < count($this->keyColumns); $i++) {
            if (!isSet($this->submitValueSet[$this->keyColumns[$i]]) || $this->submitValueSet[$this->keyColumns[$i]] == '') {
                return false;
            }
        }
        return $result;
    }

    /**
     * The nameExpression is the sql expression that is returned by executing the query (on the database)
     * name NAME_RESULT. It's purpose is to create a name for the <a href...></a> link.
     */
    function setNameExpression($expr) {
        $this->nameExpression = $expr;
        return $this;
    }

    /**
     * set the values submitted by the client
     * @param $vs valueset: array of key-values pairs
     * This function copies the data and constructs a hash map of the key value pairs.
     */
    function setSubmitValueSet($vs) {
        $this->submitValueSet = array();
        foreach ($vs as $key => $value) {
            $skey = trim(naddslashes($key));
            $sval = trim(naddslashes($value));
            $this->submitValueSet[$skey] = $sval;
        }
        if (isSet($vs['where_join'])) {
            if ($vs['where_join'] == 'Any') {
                $this->whereJoin = ' or ';
            }
        }
        return $this;
    }

    /**
     * In a supporting join query the concept aux col names is used.
     * The term denotes the columns in the supporting relation
     */
    protected $auxColNames;

    /**
     * additional columns for result list
     */
    function setAuxColNames($acn) {
        $this->auxColNames = $acn;
        return $this;
    }

    /**
     * The order list determines the way a search list is sorted
     * @param $ol array of column names.
     */
    function setOrderList($ol) {
        $this->orderList = $ol;
        return $this;
    }

    /**
     * To retreive data form the database a database connenction is needed
     * This function sets it.
     * @param $dbc the database connection to use for alle the database actions
     */
    function setDBConn($dbc) {
        $this->dbConn = $dbc;
        return $this;
    }

    /**
     * Test if a string could pass as a regex expression.
     * Implementation only checks if there is a star (*) somewhere.
     * @return true if regex
     */
    function isRegex($str) {
        // match if a character is followed by a regex multiplier.
        $r = array();
        if (preg_match('/\.[+*?]?/', $str, $r, 0, 1)) {
            return true;
        }
        return false;
    }

    /**
     * gets the where ... part without the where.
     * @return a string containing the where clause without the word 'where'
     */
    function getWhereList() {
        $whereClause = '';
        $continuation = '';
        $rp = $this->relPrefix;
        for ($i = 0; $i < count($this->matchColumnSet); $i++) {
            $name = $this->matchColumnSet[$i];
            $value = '';
            if (isSet($this->submitValueSet[$name])) {
                $value = $this->submitValueSet[$name];
                $valueIsRegex = $this->isRegex($value);
                if ($value != '') {
                    $type = $this->dataTypes[$name];
                    switch ($type) {
                        case 'bool':
                            $nvalue = (isSet($value) && ($value != 'false')) ? 'true' : 'false';
                            $whereClause .= $continuation . $rp . '.' . $name . ' = ' . $nvalue . ' ';
                            break;
                        case 'int2':
                        case 'int4':
                        case 'int8':
                            if ($valueIsRegex) {
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
                            if ($valueIsRegex) {
                                $whereClause .= $continuation . $rp . '.' . $name . '::text ~* E\'^' . $value . '$\' ';
                            } else {
                                $whereClause .= $continuation . $rp . '.' . $name . '::text ilike \'' . $value . '\' ';
                            }
                            break;
                            break;
                    }
                    $continuation = $this->whereJoin . "\n";
                }
            }
        }
        //echo "<pre style='color:#080'>{$whereClause}</pre>";
        return $whereClause;
    }

    /* getWhereList() */

    /**
     * gets the query part starting with from
     * @return string 'form ......'
     */
    function getQueryTail() {
        $result = '';
        $whereClause = $this->getWhereList();
        if (strlen($whereClause) > 0) {
            $result .= "\n where " . $whereClause;
        }
        $continuation = ' ';
        if (isSet($this->orderList)) {
            $result .="\n order by ";
            for ($i = 0; $i < count($this->orderList); $i++) {
                $result .= $continuation . $this->orderList[$i];
                $continuation = ', ';
            }
        }
        return $result;
    }

    function getQueryHead() {
        $result = 'select ' . $this->nameExpression . ' as RESULT_NAME ';
        $continuation = ', ';
        for ($i = 0; $i < count($this->keyColumns); $i++) {
            $result .= $continuation . $this->relPrefix . '.' . $this->keyColumns[$i];
        }
        if (isSet($this->auxColNames)) {
            foreach ($this->auxColNames as $expr => $auxColName) {
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
    function getQuery() {
        $result = $this->getQueryHead()
                . ' from '
                . $this->relation . ' ' . $this->relPrefix . ' '
                . $this->getQueryTail();
        return $result;
    }

    public function __toString() {
        return $this->getQuery();
    }

    private $subRel = null;
    private $subRelJoinColumns = null;

    /**
     * An expression A that can serve part in a join (A) sub_rel on (...) subquery.
     * @param type $s
     * @return this searchquery
     */
    public function setSubRel($s) {
        if ($s !== '') {
            $this->subRel = $s;
        }
        return $this;
    }

    /**
     * Set array that maps left part of join to right part.
     * @param array. Keys are left hand, values right hand column names $a
     * @return this \SearchQuery
     */
    public function setSubRelJoinColumns($a) {
        if (is_array($a)) {
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

        return $this->getQueryHead()
                . ' from '
                . $this->getExtendedQueryTail();
    }

    public function getAllQuery() {
        return "select * from " . $this->getExtendedQueryTail();
    }

    public function getSubRelQuery() {
        return 'select sub_rel.* '
                . ' from '
                . $this->getExtendedQueryTail();
    }

    public function subRelExpression() {

        $subRelExpr = '';
        $rpf = $this->relPrefix;
        if (isSet($this->subRel) && isSet($this->subRelJoinColumns)) {
            $joinOn = "";
            $joinGlue = '';
            foreach ($this->subRelJoinColumns as $left => $right) {
                $joinOn .= $joinGlue . " {$rpf}.{$left}=sub_rel.{$right}";
                $joinGlue = " and \n";
            }
            $subRelExpr = " left join (select * from $this->subRel) sub_rel on ($joinOn) ";
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
    function setUpdateSet($us) {
        $this->updateSet = array();
        while (list($key, $value) = each($us)) {
            $key = trim(naddslashes($key));
            $value = trim(naddslashes($value));
            if (isSet($this->columnNames[$key]) && !isSet($this->keyColumnNames[$key])) {
                $this->updateSet[$key] = $value;
            }
        }
        reset($us);
    }

    /**
     * returns the key column value set, since that identifies the record.
     * @return string the where-list
     */
    function getWhereList() {
        $whereClause = '';
        $continuation = '';
        for ($i = 0; $i < count($this->keyColumns); $i++) {
            $name = $this->keyColumns[$i];
            $value = $this->submitValueSet[$name];
            if ($value != '') {
                $value = "'" . $value . "'";
                $whereClause .= $continuation . $name . '=' . $value . ' ';
                $continuation = $this->whereJoin;
            }
        }
        return $whereClause;
    }

    /**
     * Gets the query.
     * @return string: the query prepared to be submitted to the database.
     */
    function getQuery() {
        $result = 'update ' . $this->relation . ' set ';
        $continuation = '';
        while (list($key, $value) = each($this->updateSet)) {
            if ($this->dataTypes[$key] == 'bool' && isSet($value) && ($vale === 'true' || $value == 'false')) {
                $result .= $continuation . $key . '=' . $value;
                $continuation = ',';
            } else {
                $nvalue = ( $value != '') ? ('\'' . $value . '\'') : ('default');
                $result .= $continuation . $key . '=' . $nvalue;
                $continuation = ',';
            }
        }
        $result .= ' where ' . $this->getWhereList();
        return $result;
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
    function setUpdateSet($us) {
        reset($us);
        $this->updateSet = array();
        while (list($key, $value) = each($us)) {
            $key = trim(naddslashes($key));
            $value = trim(naddslashes($value));
            if (isSet($this->columnNames[$key])) {
                $this->updateSet[$key] = $value;
            }
        }
        reset($us);
    }

    /**
     * test if all keyColumns have a value
     * @return boolean true if all keycolumns are set.
     */
    function areKeyColumnsSet() {
        $result = true;
        for ($i = 0; $i < count($this->keyColumns); $i++) {
            if (!isSet($this->updateSet[$this->keyColumns[$i]]) || $this->updateSet[$this->keyColumns[$i]] == '') {
                error_log("key columns not all set");
                return false;
            }
        }
        return $result;
    }

    /**
     * produce the query assembled by this class
     * @return string te query to be submitted to the database
     */
    function getQuery() {
        $result = 'insert into ' . $this->relation . ' (';
        $continuation1 = '';
        $continuation2 = '';
        $cols = '';
        $vals = '';
        while (list($key, $value) = each($this->updateSet)) {
            // the test ensures that non set values take their default or null.
            if ($key != '' && $value != '') {
                $cols .= $continuation1 . $key;
                if ($this->dataTypes[$key] == 'bool') {
                    $value = (isSet($value) && ($value != '')) ? 'true' : 'false';
                    $vals .= $continuation2 . '\'' . $value . '\'::bool';
                } else {
                    $vals .= $continuation2 . '\'' . $value . '\'';
                }
                $continuation1 = ', ';
                $continuation2 = ', ';
            }
        }
        $result .= $cols . ') values (' . $vals . ')';
        return $result;
    }

}

/**
 * To delete no more than intended mak sure taht all key columns are set so that only
 * one record is deleted
 */
class DeleteQuery extends UpdateQuery {

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
    function setUpdateSet($us) {
        $this->updateSet = array();
        while (list($key, $value) = each($us)) {
            $key = trim(naddslashes($key));
            $value = trim(naddslashes($value));
            if (isSet($this->keyColumnNames[$key])) {
                $this->updateSet[$key] = $value;
            }
        }
        reset($us);
    }

    /**
     * Gets the query composed  based upon the attributes of the delete query.
     * @return string the query for the database.
     */
    function getQuery() {
        $result = 'delete from ' . $this->relation . ' where ';
        $result .= $this->getWhereList();
        return $result;
    }

}

/**
 * Compose a query that maps (foreign) keys of one table
 * to keys of another. The join is normally a left join. See the relevant database literature for
 * an exposee on left joins.
 */
class SupportingJoinQuery {

    protected $relation;

    function setRelation($rel) {
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
    function setKeyMap($map) {
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
    function setSubmitValueSet($vs) {
        //    stacktrace();
        $this->submitValueSet = array();
        reset($vs);
        foreach ($vs as $key => $value) {
            $skey = trim(naddslashes($key));
            $sval = trim(naddslashes($value));
            $this->submitValueSet[$skey] = $sval;
        }
        reset($vs);
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
        while (list($fkey, $pkey) = each($this->keyMap)) {
            if ($fkey != '' && $pkey != '') {
                $val = $this->submitValueSet[$fkey];
                if (isSet($val) && $val != '') {
                    $tail .= $continuation . $pkey . '=\'' . $val . '\'';
                } else {
                    $tail .= $continuation . $pkey . ' isnull';
                }
                $continuation = ' and ';
            }
        }
        if ($tail == '') {
            return $tail;
        } else {
            return $result . $tail;
        }
    }

}

/* $Id: searchquery2.php 1860 2015-07-27 08:18:07Z hom $ */

