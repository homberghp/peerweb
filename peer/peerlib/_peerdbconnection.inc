<?php

$adodb_path = '/usr/share/php/adodb';
require_once($adodb_path . "/adodb.inc.php");
require_once($adodb_path . '/adodb-pager.inc.php');

class PeerDBConnection {

    private $impl;
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
    private $sandbox=false;

    public function __construct( $proto ) {
        $log_text = '';
        $this->impl = NewADOConnection( $proto );
//    $this->impl->debug=true;
    }

    public function Connect( $host, $user, $pass, $db_name ) {
        $this->db_name = $db_name;
        return $this->impl->Connect( $host, $user, $pass, $db_name );
    }

    public function PConnect( $host, $user, $pass, $db_name ) {
        //$this->db_name = $db_name;
        return $this->impl->PConnect( $host, $user, $pass, $db_name );
    }

    public function MetaType( $a1, $a2 = null, $a3 = null ) {

        if ( $a2 != null && $a3 != null ) {
            return $this->impl->MetaType( $a1, $a2, $a3 );
        } else if ( $a2 != null ) {
            return $this->impl->MetaType( $a1, $a2 );
        } else {
            return $this->impl->MetaType( $a1 );
        }
    }

    public function sandboxedExecute($statement) {
                $doFileLogging = false;
        $fileMsg = '';
        if ( $this->sqlLogModifingQuery && preg_match( "/^(begin|insert|update|delete)/i",
                        $statement ) ) {
            $doFileLogging = true;
        }
        if ( $this->sqlAutoLog ) {
            $this->log( "\n" . $this->sqlAutoLogCounter . ': ' . $statement );
        }
        //	$doFileLogging=true;

        if ( $doFileLogging ) {
            $fileMsg .="\n" . $this->sqlAutoLogCounter . ': ' . $statement;
        }

        $result = $this->impl->Execute( 'begin work;'.$statement );
        if ( $result === false ) {
            $logmsg = "Error on database " . $this->db_name . "\r\n with query \r\n\"" .
                    "$statement\"\r\n" .
                    "cause =\"" . $this->ErrorMsg() . "\r\n" .
                    "occured at \r\n" .
                    stacktracestring( 1 );
            if ( $this->sqlAutoLog )
                $this->log( $logmsg );
            if ( $doFileLogging ) {
                $fileMsg .= $logmsg;
            }
        }
        $this->aRowCount = $this->impl->Affected_Rows();

        if ( $this->sqlAutoLog ) {
            $this->log( "\n\t\taffected rows: $this->aRowCount" );
        }
        if ( $doFileLogging ) {
            $this->logToFile( $fileMsg . "\n\t\taffected rows: $this->aRowCount" );
        }
        //	echo $this->getLogHtml()."\n".stacktracestring(0);
        // increment logcounter fro this interaction 
        $this->sqlAutoLogCounter += 1;
        $this->sandbox = false;
        $this->impl->Execute("abort"); 
        return $result;
    }

    public function Execute( $statement ) {
        if ($this->sandbox) {
            return $this->sandboxedExecute( $statement );
        }
        $doFileLogging = false;
        $fileMsg = '';
        if ( $this->sqlLogModifingQuery && preg_match( "/^(begin|insert|update|delete)/i",
                        $statement ) ) {
            $doFileLogging = true;
        }
        if ( $this->sqlAutoLog ) {
            $this->log( "\n" . $this->sqlAutoLogCounter . ': ' . $statement );
        }
        //	$doFileLogging=true;

        if ( $doFileLogging ) {
            $fileMsg .="\n" . $this->sqlAutoLogCounter . ': ' . $statement;
        }

        $result = $this->impl->Execute( $statement );
        if ( $result === false ) {
            $logmsg = "Error on database " . $this->db_name . "\r\n with query \r\n\"" .
                    "$statement\"\r\n" .
                    "cause =\"" . $this->ErrorMsg() . "\r\n" .
                    "occured at \r\n" .
                    stacktracestring( 1 );
            $this->logError( $logmsg );
            if ($this->sqlAutoLog) {
                $this->log($logmsg);
            }
            if ( $doFileLogging ) {
                $fileMsg .= $logmsg;
            }
        }
        $this->aRowCount = $this->impl->Affected_Rows();

        if ( $this->sqlAutoLog ) {
            $this->log( "\n\t\taffected rows: $this->aRowCount" );
        }
        if ( $doFileLogging ) {
            $this->logToFile( $fileMsg . "\n\t\taffected rows: $this->aRowCount" );
        }
        //	echo $this->getLogHtml()."\n".stacktracestring(0);
        // increment logcounter fro this interaction 
        $this->sqlAutoLogCounter += 1;

        return $result;
    }

    /**
     * forwarding method
     */
    public function Affected_Rows() {
        return $this->aRowCount;
    }

    public function doOrDie( $statement ) {
        $result = $this->Execute( $statement );
        if ( $result === false ) {
            if ( $this->transactionPending ) {
                $this->Execute( "ROLLBACK" );
            }
            echo $this->getLogHtml();
            stacktrace( 2 );
            // dump out all we have so far.
            ob_end_flush();
            die();
        }
        return $result;
    }

    private $transactionLog;

    public function ExecuteDirect( $statement ) {
        $this->impl->Execute( $statement );
        $this->aRowCount == $this->impl->Affected_Rows();
    }

    public function doSilent( $statement ) {
        if ( $this->transactionPending ) {
            $this->transactionLog .= $statement . ";\n";
        }
        $result = $this->impl->Execute( $statement );
        if ( $result === false ) {
            if ( $this->transactionPending ) {
                $this->transactionLog .= "ROLLBACK;\n" .
                        "Error on database " . $this->db_name .
                        "with query $statement\n" .
                        " cause " . $this->impl->ErrorMsg() . "\n";
                $this->logError( $this->transactionLog );
                $this->LogToFile( $this->transactionLog );
                $this->impl->Execute( "ROLLBACK" );
                $this->transactionPending = false;
                $this->transactionLog = '';
            } else {
                $logmsg = "Error on database " . $this->db_name . "\r\n with query \r\n\"" .
                        "$statement\"\r\n" .
                        "cause =\"" . $this->impl->ErrorMsg() . "\r\n" .
                        "occured at \r\n" .
                        stacktracestring( 1 );
                $this->logError( $logmsg );
                $this->LogToFile( $this->transactionLog );
            }
        } else {
            if ( $this->sqlLogModifingQuery && preg_match( "/^(begin|insert|update|delete)/i",
                            $statement ) ) {
                $this->logToFile( $statement . "\n\t\taffected rows: $this->aRowCount" );
            }
        }
        return $result;
    }

    private $latestTransaction = 0;

    /**
     * start a sql transaction and return a transaction id
     */
    public function transactionStart( $text ) {
        $this->transactionPending = true;
        $this->transactionLog = "$text;\n";
        $t = $this->createTransactionId();
        return $t;
    }

    public function createTransactionId() {
        global $peer_id;
        $this->impl->Execute( "BEGIN WORK;" );
        $rs = $this->impl->Execute( "select nextval('transaction_trans_id_seq');" );
        $t = $this->latestTransaction = $rs->fields['nextval'];
        $from_ip = $_SERVER['REMOTE_ADDR'];
        $rs2 = $this->impl->Execute( "insert into transaction (trans_id,operator,from_ip)\n"
                . "values($t,$peer_id,'$from_ip');" );
        return $t;
    }

    public function transactionEnd( $text = 'COMMIT' ) {
        $this->impl->Execute( "COMMIT" );
        $this->transactionLog .="\nCOMMIT\n";
        $this->transactionPending = false;
        $this->logToFile( $this->transactionLog );
        $this->transActionLog = '';
    }

    /**
     * print stacktrace on $db Error
     * @param $dbErr int database error
     * @param $query query string cause? the error
     */
    public function dbStackTrace( $dbErr, $query ) {
        if ( $dbErr ) {
            //??    $dbg = debug_backtrace();
            echo '<span style="color:#FF0000;font-weight:bold;">' .
            '<br>Problems with database query ' . $query . ' or fetch:';
            stacktrace( 2 );
            echo '</span>';
        }
    }

    public function logError( $msg ) {
        $this->newLog = '[' . date( 'Y-m-d H:i:s' ) . ']' . $msg;
        $this->log( "\n" . $msg );
        if ( $this->db_name == 'peer' ) {
            mail( ADMIN_EMAILADDRESS,
                    "database error occured on " .
                    $this->db_name . " host " . gethostname(),
                    $this->newLog . $this->getContext(),
                    "From: webmaster@{$_SERVER['SERVER_NAME']}\r\n" .
                    "Reply-To: hom@{$_SERVER['SERVER_NAME']}\r\n" .
                    "X-Mailer: PHP/" . phpversion() );
        }
    }

    function logContext() {
        $this->log( $this->getContext() );
    }

    function getContext() {
        global $peer_id;
        ob_start();
        echo "_REQUEST ";
        print_r( $_REQUEST );
        /* echo "_SESSION "; */
        /* print_r( $_SESSION ); */
        //	echo "_SERVER ";
        // print_r($_SERVER);
        $msg = "server context:\npeer_id=$peer_id\n" . ob_get_contents();
        ob_end_clean();
        return $msg;
    }

    function log( $msg ) {
        $this->newLog = '[' . date( 'Y-m-d H:i:s' ) . ']' . $msg;
        if ($this->log_text === false) {
            $this->log_text = '';
        }
        $this->log_text .= "\n" . $msg;
    }

    function logToFile( $msg ) {
        global $site_home;
        if (!$this->sqlLogging) {
            return;
        }
        if ( $this->logFilename == '' ) {
            $this->logFilename = $site_home . '/log/log' . date( 'YmdHis' ) . '.txt';
        }
        error_log( "\n---------" . date( 'Y-m-d H:i:s' ) . "---------\n" .
                $msg . "\n" .
                stacktracestring( 2 ) . "\n" .
                $this->getContext() . "\n", 3, $this->logFilename );
    }

    function getLastMsg() {
        return $this->newLog;
    }

    function getLog() {
        return $this->log_text;
    }

    function getLogHtml() {
        if ( $this->log_text == '' )
            return '';
        else
            return "<pre style='color:#FF0000;font-weight:bold;'>\n" .
                    $this->log_text .
                    "</pre>\n";
    }

    function ErrorMsg() {
        return $this->impl->errormsg();
    }

    function setSqlAutoLog( $b ) {
        $this->sqlAutoLog = $b;
    }

    function setSqlLogModifyingQuery( $b ) {
        $this->sqlLogModifingQuery = $b;
    }

    function setLogFilename( $fn ) {
        $this->logFilename = $fn;
    }

    function __toString() {
        return "peerdb connection";
    }

    function setSqlLogging( $b ) {
        $this->sqlLogging = $b;
    }
    function sandbox() {
        $this->sandbox= true;
        return $this;
    }

    /**
     * Get one (first) record.
     * This method executes the query, returns the resultset pointing to the first record.
     * 
     * @param $dbConn database connection to use
     * @param $sql query string for this datum
     * @return $resultSet: resultSet to use for any next items.
     */
    function getFirstRecordSetFields( $sql ) {
        $resultSet = $this->impl->Execute( $sql );
        if ( $resultSet === false ) {
            $msg = $this->impl->ErrorMsg();
            echo "Cannot execute select statement \"" . $sql . "\", cause=" . $msg . "\n";
            stacktrace( 2 );
            exit;
        }
        if ( $resultSet->EOF ) {
            die( "cannot get data with query $sql because the set is empty" );
        } else {
            // copy to arr
        }
        return $resultSet;
    }

    /**
     * queryToCSV creates a csv file that is presented as application/msexcel
     * @param $query the query send to the db
     * @param $filename the name for the presented file
     * @param $separator separates the fields in the records, the 'comma' in csv (Comma Separated Values)
     */
    function queryToCSV( $query, $filename, $separator = ';',
            $quoteText = false,
            $content_header = 'Content-type: text/x-comma-separated-values; charset: UTF-8;',
            $niceName = true ) {
        global $ADODB_FETCH_MODE;
        $ADODB_FETCH_MODE = ADODB_FETCH_NUM;
        header( "Pragma: public" );
        header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
        header( $content_header );
        header( 'Content-disposition: attachment; filename=' . $filename );
        $resultSet = $this->getFirstRecordSetFields( $query );
        $colcount = $resultSet->FieldCount();
        $columntypes = array( );
	$tmpSep = '';
        for ( $i = 0; $i < $colcount; $i++ ) {
            $field = $resultSet->FetchField( $i );
            if ( $niceName ) {
                $name = $this->niceName( $field->name );
            } else {
                $name = $field->name;
            }
            if ( $quoteText ) {
                $name = '"' . $name . '"';
            }
            echo $tmpSep.$name;
            $columntypes[$i] = $resultSet->MetaType( $field->type,
                    $field->max_length );
	    if ($tmpSep ==''){
	      $tmpSep=$separator;
	    }
        }
        while ( !$resultSet->EOF ) {
            echo "\n";
	    $tmpSep = '';
            for ( $i = 0, $max = $resultSet->FieldCount(); $i < $max; $i++ ) {
                $field = trim( $resultSet->fields[$i] );
                switch ( $columntypes[$i] ) {
                    case 'I':
                    case 'N': print $tmpSep.$field;
                        break;
                    default:
                        if ( $quoteText && $field ) {
                            $field = '"' . $field . '"';
                        }
                        echo $tmpSep.$field;
                        break;
                }
		if ($tmpSep ==''){
		  $tmpSep=$separator;
		}
            }
            $resultSet->MoveNext();
        }
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
    }

    /**
     * replace '_', by ' ' and Capitalize.
     */
    function niceName( $s ) {
        $result = str_replace( '_', ' ', $s );
        $result = ucfirst( strtolower( $result ) ); //Capitalize
        return $result;
    }

}

// class PeerDBConnection

/**
 * print a stracttrace deep level
 */
function stacktrace( $level = 1 ) {
    echo '<pre>' . stacktracestring( $level ) . "</pre>\n";
}

/**
 * Dump the stack into a <pre></pre> field
 * @param $level to start with, defaults to 1, calling function.
 */
function stacktracestring( $level = 1 ) {
    $dbg = debug_backtrace();
    $result = '';
    for ( $j = $level; $j < count( $dbg ); $j++ ) {
        $line = 0;
        $arglist = '';
        $function = '';
        $class = '';
        $object = '';
        foreach ( $dbg[$j] as $key => $val ) {
            //echo $key.' => '.$val;
            if ( $key == 'file' )
                $file = ($val);
            else if ( $key == 'line' )
                $line = $val;
            else if ( $key == 'function' )
                $function = $val;
            else if ( $key == 'class' )
                $class = $val;
            else if ( $key == 'object' )
                $object = '$object';
            else if ( $key == 'args' ) {
                $arglist = '(';
                $continuation = '';
                for ( $i = 0; $i < count( $dbg[$j][$key] ); $i++ ) {
                    $arglist .= $continuation . $dbg[$j][$key][$i];
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

