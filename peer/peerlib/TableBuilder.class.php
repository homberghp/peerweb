<?php

require_once('./peerlib/peerutils.inc');

/**
 * Build a row of a html table.
 */
interface RowFactory {

  /**
   * @param $valueArray use to pick out the values used in the header to build.
   */
  public function startRow( $valueArray );

  /** Build the table header row */
  public function buildHeader( $valueArray );

  /**
   * @param $valueArray use to pick out the values used in the cell(s) to build.
   */
  public function buildCell( $valueArray );

  /**
   * additional builder for headers of folding tables;
   */
  public function buildHeaderCell( $valueArray );
}

/**
 * A checktable is a html table that folds a query resultset into a shorter table.
 * The  associated query result table has a check an optional note column.
 * The other values are assumed to go into the header or be ignored. The check values are concatenated into one
 * row and displayed after one header.
 * The query result (resulSet) has the following mandatory or optional columns:
 * + check MAN The column that is accumulated
 * + note   Optional value used as title in check field
 * + checkTitle MAN header title for the check columns 
 */
class TableBuilder {

  private $dbConn;
  private $rowFactory;
  private $tabledef = "<table summary='simple table' border='1' style='border-collapse:collapse'>\n";

  function __construct( $dbConn, $rowFactory ) {
    $this->dbConn = $dbConn;
    $this->rowFactory = $rowFactory;
  }

  function getTable( $query, $triggerColumn ) {
    $result = '';
    $resultSet = $this->dbConn->Execute( $query );
    if ( $resultSet === false ) {
      $result.= "<pre>Cannot read table data with \n\t" . $query . " \n\treason \n\t" . $this->dbConn->ErrorMsg() . "at\n";
      ob_start();
      stacktrace( 1 );
      $result .= ob_get_contents();
      ob_clean();
      $result .="</pre>";
      return $result;
    }

    $tableHead = $this->tabledef . "\n<tr>" . $this->rowFactory->buildHeader( $resultSet->fields );
    $tableBody = '';
    $tableRow = '';
    $oldTrigger = '';
    $rowCounter = -1;
    $columnCounter = 0;
    while ( !$resultSet->EOF ) {
      if ( $oldTrigger != $resultSet->fields[$triggerColumn] ) {
        if ( $tableRow != '' ) {
          $tableBody .= $tableRow . "\n</tr>\n";
        }
        $tableRow = $this->rowFactory->startRow( $resultSet->fields );
        $oldTrigger = $resultSet->fields[$triggerColumn];
        $columnCounter = 0;
        $rowCounter++;
      }
      if ( $rowCounter == 0 && ($oldTrigger == $resultSet->fields[$triggerColumn]) ) {
        $tableHead .= $this->rowFactory->buildHeaderCell( $resultSet->fields );
      }
      $tableRow .= $this->rowFactory->buildCell( $resultSet->fields );
      $columnCounter++;
      $padding = ($columnCounter < 10) ? '&nbsp;' : '';
      $resultSet->moveNext();
    }
    $tableBody .=$tableRow;
    return $tableHead . "\n</tr>\n" . $tableBody . "\n</tr>\n</table>\n";
  }

}

?>