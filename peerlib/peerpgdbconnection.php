<?php

/**
 * Direct postgres connection instead  of using adodb for richer
 * database fucntionality and real prepared statement behavior.
 */
//$adodb_path = '/usr/share/php/adodb';
//require_once($adodb_path . "/adodb.inc.php");
//require_once($adodb_path . '/adodb-pager.inc.php');

class SQLPrepareException extends Exception {

    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

}

class SQLExecuteException extends Exception {

    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

}

class PeerPGDBConnection {

    /** a pd connection. */
    private $connection;
    private $log_text = '';
    private $newLog = '';
    private $db_name = '';
    private $sqlAutoLog = false;
    private $sqlAutoLogCounter = 0;
    private $sqlLogModifingQuery = false;
    private $transactionPending = false;
    private $aRowCount;
    private $logFilename = '';
    private $sqlLogging = false;
    private $lastResult = null;

    /**
     * Create connection with prototype. 
     * Constructor kept for compatibility.
     * @param type $proto db type
     * @deprecated since version 1.0
     */
    function __construct($proto) {
        $log_text = '';
        //$this->impl = NewADOConnection( $proto );
//    $this->impl->debug=true;
    }

    /**
     * Connect to backend using params.
     * Params are put into 'modern' connect string.
     * Saves and returns new connection.
     * @param type $host
     * @param type $user
     * @param type $pass
     * @param type $db_name
     * @return type
     */
    function Connect($host, $user, $pass, $db_name, $port = 5432) {

        $this->db_name = $db_name;
        $hostString = "host={$host} "; // not trailing space
        if ($host == '' || $host == 'localhost') {
            $hostString = '';
        } 
        $result = $this->connection = pg_connect("{$hostString}user={$user} password={$pass} dbname={$this->db_name} port={$port}");
        if ($result === false) {
            die("cannot establish connection\n" . pg_last_error());
        }
        return $this;
    }

    /**
     * Execute the given query on the database.
     * Only One query allowed. For a list of queries use
     * the provide other method executeQueryList.
     * 
     * @param type $statement a query
     * @return type
     */
    function Execute($statement, $params = array()) {
        $doFileLogging = false;
        $fileMsg = '';
        if ($this->sqlLogModifingQuery && preg_match("/^(begin|insert|update|delete)/i", $statement)) {
            $doFileLogging = true;
        }
        if ($this->sqlAutoLog) {
            $this->log("\n" . $this->sqlAutoLogCounter . ': ' . $statement);
        }
        //	$doFileLogging=true;

        if ($doFileLogging) {
            $fileMsg .= "\n" . $this->sqlAutoLogCounter . ': ' . $statement;
        }
        if (preg_match("/^begin/i", $statement)) {
            $this->lastResult = $result = pg_query($this->connection, $statement);
        } else {
            $this->lastResult = $result = pg_query_params($this->connection, $statement, $params);
        }
        if ($result === false) {
            $logmsg = "Error on database " . $this->db_name . "\r\n with query \r\n\"" .
                    "$statement\"\r\n" .
                    "cause =\"" . $this->ErrorMsg() . "\r\n" .
                    "occured at \r\n" .
                    stacktracestring(3);
            $this->logError($logmsg);
            if ($this->sqlAutoLog) {
                $this->log($logmsg);
            }
            if ($doFileLogging) {
                $fileMsg .= $logmsg;
            }
            return $result;
        }

        $this->aRowCount = pg_affected_rows($result);

        if ($this->sqlAutoLog) {
            $this->log("\n\t\taffected rows: $this->aRowCount");
        }
        if ($doFileLogging) {
            $this->logToFile($fileMsg . "\n\t\taffected rows: $this->aRowCount");
        }
        //	echo $this->getLogHtml()."\n".stacktracestring(0);
        // increment logcounter for this interaction 
        $this->sqlAutoLogCounter += 1;

        return new PeerResultSet($this->connection, $result, $this->aRowCount);
    }

    /**
     * create a prepared statement
     * @param type $query
     * @param type $stmName
     * @return PreparedStatement success full
     */
    public function Prepare($query, $stName = '') {
        //var_dump($this->connection);
        $resource = pg_prepare($this->connection, $stName, $query);
//        echo " prepared ";
//        var_dump($resource);
        if (false !== $resource) {
            return new PreparedStatement($this, $stName);
        } else {
            $pg_errormessage = pg_errormessage($this->connection);
            throw new SQLPrepareException($pg_errormessage);
//            return false;
        }
    }

    public function unWrap() {
        return $this->connection;
    }

    /**
     * execute prepared applying params
     * @param type $preparedName
     * @param type $params
     * @return \PeerResultSet
     */
    public function ExecutePrepared($preparedName, $params = array()) {
//        if (! is_array($params)){
//            echo "Not an array:";
//            print_r($params);
//            stacktrace();
//            exit(1);
//        }
        $result = pg_execute($this->connection, $preparedName, $params);
        return new PeerResultSet($this->connection, $result);
    }

    /**
     * Execute a compound statement
     * @param type $stmts
     */
    public function executeCompound($stmts) {
        $result = pg_query($this->connection, $stmts);
        return new PeerResultSet($this->connection, $result);
    }

    /**
     * Execute a list of commands in a transaction.
     * The argument is a list query, params pairs. Each query is executed 
     * with its parameters in the order given until the list is exhausted. 
     * Depending on the command status, the transaction is closed with a commit or rollback. 
     * If the params is not given for a list element, the query is executed without parameters.
     * 
     * @param type $query
     * @param params, array of parameters in query
     * @return the total number of affected rows.
     */
    function executeQueryParamList($query, $paramsArray) {
        $aRows = 0;
        if (!$this->transactionPending) {
            pg_query($this->connection, "BEGIN WORK");
        }
        $success = false;
        try {
            foreach ($paramsArray as $params) {
                if (defined($qpair['query'])) {
                    $result = pg_query_params($this->connection, $query, $params);
                    if (false === $result) {
                        throw new Exception("execute failure " + pg_errormessage($this->connection));
                    }
                    $aRows += pg_affected_rows($result);
                }
            }
            $success = true;
        } catch (Exception $ex) {
            // roll back and rethrow.
            pg_query($this->connection, "ROLLBACK");
            $success = false;
            throw $ex;
        }
        if ($success && !$this->transactionPending) {
            // if someone else started, 
            // let it stop too!
            pg_query($this->connection, "COMMIT");
        }
        return $aRows;
    }

    /**
     * Do an array of simple queries as one transaction.
     * @param type $queryList
     * @return type number of affected rows
     * @throws Exception 
     */
    function executeQueryList($queryList) {
        $aRows = 0;
        if (!$this->transactionPending) {
            pg_query($this->connection, "BEGIN WORK");
        }
        $qCount = count($queryList);
        try {
            for ($c = 0; $c < $qCount; $c++) {
                $result = pg_query($this->connection, $queryList[$c]);
                if (false === $result) {
                    throw new Exception("execute failure " + pg_errormessage($this->connection));
                } else {

                    $aRows += pg_affected_rows($result);
                }
            }
            $success = true;
        } catch (Exception $ex) {
            pg_query($this->connection, "ROLLBACK");
            $success = false;
            throw $ex;
        }
        if ($success) {
            pg_query($this->connection, "COMMIT");
        }
        return $aRows;
    }

    /**
     * forwarding method
     */
    function Affected_Rows() {
        return pg_affected_rows($this->lastResult);
    }

    /**
     * Try to execute statement and stop php processing 
     * on error.
     * @param type $statement
     * @param type $params
     * @return type
     */
    function doOrDie($statement, $params = array()) {
        $result = $this->Execute($statement, $params);
        if ($result === false) {
            if ($this->transactionPending) {
                $this->Execute("ROLLBACK");
            }
            echo $this->getLogHtml();
            stacktrace(2);
            // dump out all we have so far.
            ob_end_flush();
            die();
        }
        return $result;
    }

    private $transactionLog;

    /**
     * For backward compatibilty.
     * @param type $statement to execute
     * @param type $params to query
     * @return type result of query.
     */
    function doSilent($statement, $params = array()) {
        return $this->Execute($statement, $params);
    }

    private $latestTransaction = 0;

    /**
     * start a sql transaction and return a transaction id.
     */
    function transactionStart($text) {
        $this->transactionPending = true;
        $this->transactionLog = "$text;\n";
        $t = $this->createTransactionId();
        return $t;
    }

    /**
     * Create a transaction id and persist connection data.
     * @global type $peer_id the user or operator using this session.
     * @return long transaction id.
     */
    function createTransactionId() {
        global $peer_id;
        pg_query($this->connection, "BEGIN WORK");
        $rs = $this->Execute("select nextval('transaction_trans_id_seq')");
        $t = $this->latestTransaction = $rs->fields['nextval'];
        $from_ip = $_SERVER['REMOTE_ADDR'];
        $params = array($t, $peer_id, $from_ip);
        $rs2 = $this->Execute("insert into transaction (trans_id,operator,from_ip)\n"
                . "values( $1, $2, $3)", $params);
        echo "{$t}, {$from_ip} {$peer_id}<br/>";
        return $t;
    }

    function transactionEnd($text = 'COMMIT') {
        if ($this->transactionPending) {
            $result = pg_query($this->connection, "COMMIT");
            $this->transactionLog .= "\n{$text}\n";
            $this->transactionPending = false;
            $this->logToFile($this->transactionLog);
            $this->transActionLog = '';
            return $result;
        }
        // assume this signals success if there was no transaction.
        return true;
    }

    /**
     * Print stacktrace on $db Error.
     * @param $dbErr int database error
     * @param $query query string cause? the error
     */
    function dbStackTrace($dbErr, $query) {
        if ($dbErr) {
            //??    $dbg = debug_backtrace();
            echo '<span style="color:#FF0000;font-weight:bold;">' .
            '<br>Problems with database query ' . $query . ' or fetch:';
            stacktrace(2);
            echo '</span>';
        }
    }

    function logError($msg) {
        $this->newLog = '[' . date('Y-m-d H:i:s') . ']' . $msg;
        $this->log("\n" . $msg);
        if ($this->db_name == 'peer') {
            mail(ADMIN_EMAILADDRESS, "database error occured on " .
                    $this->db_name . " host " . gethostname(), $this->newLog . $this->getContext(), "From: webmaster@{$_SERVER['SERVER_NAME']}\r\n" .
                    "Reply-To: hom@{$_SERVER['SERVER_NAME']}\r\n" .
                    "X-Mailer: PHP/" . phpversion());
        }
    }

    function logContext() {
        $this->log($this->getContext());
    }

    function getContext() {
        global $peer_id;
        ob_start();
        echo "_REQUEST ";
        print_r($_REQUEST);
        /* echo "_SESSION "; */
        /* print_r( $_SESSION ); */
        //	echo "_SERVER ";
        // print_r($_SERVER);
        $msg = "server context:\npeer_id=$peer_id\n" . ob_get_contents();
        ob_end_clean();
        return $msg;
    }

    function log($msg) {
        $this->newLog = '[' . date('Y-m-d H:i:s') . ']' . $msg;
        if ($this->log_text === false) {
            $this->log_text = '';
        }
        $this->log_text .= "\n" . $msg;
    }

    function logToFile($msg) {
        global $site_home;
        if (!$this->sqlLogging) {
            return;
        }
        if ($this->logFilename == '') {
            $this->logFilename = $site_home . '/log/log' . date('YmdHis') . '.txt';
        }
        error_log("\n---------" . date('Y-m-d H:i:s') . "---------\n" .
                $msg . "\n" .
                stacktracestring(2) . "\n" .
                $this->getContext() . "\n", 3, $this->logFilename);
    }

    function getLastMsg() {
        return $this->newLog;
    }

    function getLog() {
        return $this->log_text;
    }

    function getLogHtml() {
        if ($this->log_text == '')
            return '';
        else
            return "<pre style='color:#800;font-weight:bold;'>\n" .
                    $this->log_text .
                    "</pre>\n";
    }

    function ErrorMsg() {
        return pg_last_error($this->connection);
    }

    function setSqlAutoLog($b) {
        $this->sqlAutoLog = $b;
    }

    function setSqlLogModifyingQuery($b) {
        $this->sqlLogModifingQuery = $b;
    }

    function setLogFilename($fn) {
        $this->logFilename = $fn;
    }

    function __toString() {
        return "peerpgdb connection to database " . $this->db_name;
    }

    function setSqlLogging($b) {
        $this->sqlLogging = $b;
    }

}

// class PeerDBConnection

define('ADODB_FETCH_NUM', PGSQL_NUM);
define('ADODB_FETCH_ASSOC', PGSQL_ASSOC);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

class PreparedStatement {

    private $dbConn;
    private $stmntName;

    /**
     * 
     * @param type $dbConn
     * @param type $stmntName
     */
    public function __construct(PeerPGDBConnection $dbConn, String $stmntName) {
        $this->dbConn = $dbConn;
        $this->stmntName = $stmntName;
    }

    /**
     * 
     * @param array $params
     * @return \PeerResultSet
     */
    public function execute($params = array()) {
        $resource = pg_execute($this->dbConn->unWrap(), $this->stmntName, $params);
        if ($resource === FALSE) {
            throw new SQLExecuteException($this->dbConn->ErrorMsg());
        }
        $affectedRows = pg_affected_rows($resource);
        return new PeerResultSet($this->dbConn, $resource, $affectedRows);
    }

    public function close() {
        if ($this->name != '') {
            $this->dbConn->doSilent('DEALLLOCATE ' . $this->stmntName);
        }
    }

}

/**
 * A resultset which behaves like the adodb result set.
 * This resultset always fetched into an associative array.
 * The resultSet is pointing at the first row, if any.
 */
class PeerResultSet {

    private $dbConn;
    private $resource;
    public $fields;
    public $EOF = false;
    private $keys = null;
    private $rowNr = -1;
    private $size;
    private $affected_rows = 0;

    /**
     * Constructor.
     * @param type $con connection
     * @param type $res resource (handl to data)
     */
    public function __construct($con, $res, $affect_rows = 0) {
        if ($con === NULL) {
            stacktrace(3);
        }
        if ($res === NULL) {
            stacktrace(3);
        }
        $this->dbConn = $con;
        $this->resource = $res;
        $this->affected_rows = $affect_rows;
        //$this->MoveFirst();
        $this->size = pg_num_rows($this->resource);
        if ($this->size <= 0) {
            $this->EOF = TRUE;
        } else {
            $this->rowNr = 0;
            $this->fields = pg_fetch_array($this->resource, $this->rowNr, PGSQL_BOTH); // $ADODB_FETCH_MODE);
            $this->EOF = ($this->fields === FALSE);
        }
    }

    public function __toString() {
        return print_r($this->resource, true);
    }

    /**
     * Moves the cursor for this result set and returns the row.
     * @return next record (row) or end of file (EOF) when pas last.
     */
    function moveNext() {

        if ($this->EOF) {
            return false;
        }
        $this->rowNr++;
        if ($this->rowNr >= $this->size) {
            $this->EOF = true;
            return false;
        }
        $this->fields = pg_fetch_array($this->resource, $this->rowNr, PGSQL_BOTH); // $ADODB_FETCH_MODE);
        if ($this->fields === false) {
            $this->EOF = true;
        }
        return $this->fields;
    }

    private $callCtr = 0;

    /**
     * Moves cursor to first row.
     * @return the row or false  on failure.
     */
    function MoveFirst() {
        if (pg_result_seek($this->resource, 0)) {
            $this->fields = pg_fetch_array($this->resource, $this->rowNr = 0, PGSQL_BOTH); // $ADODB_FETCH_MODE);
            if ($this->fields === false) {
                $this->EOF = true;
            } else {
                $this->EOF = false;
            }
            //echo "<pre>" . ($this->callCtr++) . " " . print_r($this->fields, true) . "</pre>";
        } else {
            $this->fields = false;
            $this->EOF = true; //optimists assume there is something
        }
        return $this->fields;
    }

    /**
     * Count the number of fields.
     * @return type int field count.
     */
    function FieldCount() {
        return pg_num_fields($this->resource);
    }

    /**
     * Fetch a field value by index.
     * @param type $i the index.
     * @return type the field value.
     */
    function FetchField($i) {
        $name = pg_field_name($this->resource, $i);
        
        $value = $this->fields!=null?$this->fields[$i]:null;

        return new RowField($name, $value, $i);
    }

    /**
     *  Helper Revised. Takes column number
     * @param type $field_number column number
     * @return type the postgresql column type.
     */
    function MetaType($field_number) {
        return pg_field_type($this->resource, $field_number);
    }

    /**
     * Get name of field.
     * @param type $fieldnumer
     * @return name string
     */
    function FieldName($field_number) {
        return pg_field_name($this->resource, $field_number);
    }

    /**
     * The number of rows in this result.
     * Updates size field.
     * @return the number of rows in this resultSet.
     */
    function rowCount() {
        return $this->size = pg_num_rows($this->resource);
    }

    /**
     * Returns the current row position of this resultSet.
     * @return row/cursor position
     */
    function atRow() {
        return $this->rowNr;
    }

    public function affected_rows() {
        return $this->affected_rows;
    }

}

/**
 * Giv a handle woth a column name
 */
class RowField {

    public $name;
    public $value;
    public $col;

    /**
     * Save field stuf
     * @param type $n name
     * @param type $v value
     * @param type $c columnNumber
     */
    public function __construct($n, $v, $c) {
        $this->name = $n;
        $this->value = $v;
        $this->col = $c;
    }

}

/**
 * print a stack trace deep level
 */
function stacktrace($level = 1) {
    echo '<pre>' . stacktracestring($level) . "</pre>\n";
}

/**
 * Dump the stack into a <pre></pre> field
 * @param $level to start with, defaults to 1, calling function.
 */
function stacktracestring($level = 1) {
    $dbg = debug_backtrace();
    $result = '';
    for ($j = $level; $j < count($dbg); $j++) {
        $line = 0;
        $arglist = '';
        $function = '';
        $class = '';
        $object = '';
        foreach ($dbg[$j] as $key => $val) {
            //echo $key.' => '.$val;
            if ($key === 'file') {
                $file = ($val);
            } else if ($key === 'line') {
                $line = $val;
            } else if ($key === 'function') {
                $function = $val;
            } else if ($key === 'class') {
                $class = $val;
            } else if ($key === 'object') {
                $object = '$object';
            } else if ($key === 'args') {
                $arglist = '(';
                $continuation = '';
                $detail = $dbg[$j][$key];
                for ($i = 0; $i < count($detail); $i++) {
                    $arglist .= $continuation . is_array($detail[$i]) ? ',array' : $detail[$i];
                    $continuation = ',';
                }
                $arglist .= ')';
            }
        }
        $result .= '[' . $file . ' line ' . $line . "]\n\r" . $class . '->' . $function . $arglist . "\r\n";
    }
    //  var_dump($dbg);
    //  echo $result;
    return $result;
}

/* stacktracestring() */

