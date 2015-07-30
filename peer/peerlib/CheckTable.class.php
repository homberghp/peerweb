<?php
require_once('./peerlib/peerutils.inc');

/**
 * Build a row of a html table.
 */
interface RowHeaderBuilder {
  /**
   * @param $valueArray use to pick out the values used in the header to build.
   */
  public function build($valueArray);
  /** Build the table header row */
  public function buildHeader($valueArray);
}
/**
 * Build a cell from a row of input
 */
interface TableCellBuilder {
  /**
   * @param $valueArray use to pick out the values used in the cell(s) to build.
   */
  public function build($valueArray);
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

class CheckTable {
  private $dbConn;
  private $rowHeaderBuilder;
  private $tableCellBuilder;
  private $tabledef="<table summary='simple table' border='1' style='border-collapse:collapse'>\n";
  function __construct($dbConn,$rowHeaderBuilder,$tableCellBuilder){
    $this->dbConn=$dbConn;
    $this->rowHeaderBuilder = $rowHeaderBuilder;
    $this->tableCellBuilder = $tableCellBuilder;
  }
  function getTable($query,$triggerColumn){
    $result='';
    $resultSet= $this->dbConn->Execute($query);
    if ($resultSet === false) {
      $result.= "<pre>Cannot read table data with \n\t".$query." \n\treason \n\t".$this->dbConn->ErrorMsg()."at\n";
      ob_start();
      stacktrace(1);
      $result .= ob_get_contents();
      ob_clean();
      $result .="</pre>";
      return $result;
    }

    $tableHead=$this->tabledef."<tr>".$this->rowHeaderBuilder->buildHeader($resultSet->fields);
    $tableBody='';
    $tableRow='';
    $oldTrigger='';
    $rowCounter = -1;
    $columnCounter = 0;
    while(! $resultSet->EOF ) {
      if ($oldTrigger != $resultSet->fields[$triggerColumn]){
	if ($tableRow !='') $tableBody .= $tableRow."</tr>\n";
	
	$tableRow = "<tr>".$this->rowHeaderBuilder->build($resultSet->fields);
	$oldTrigger = $resultSet->fields[$triggerColumn];
	$columnCounter=0;
	$rowCounter++;
      } 

      $tableRow .= $this->tableCellBuilder->build($resultSet->fields);
      $columnCounter++;
      $padding=($columnCounter<10)?'&nbsp;':'';
      if ($rowCounter == 0) {
	$tableHead .="\t\t<th title='".$resultSet->fields['checktitle']."' class='hasnote noteblue'>".$padding.$columnCounter."</th>\n";
      }
      $resultSet->moveNext();
    }
    $tableBody .=$tableRow."</tr>\n</table>\n";
    return $tableHead."</tr>".$tableBody;
  }

}

?>