<?php
requireCap(CAP_SYSTEM);
require_once 'validators.php';

# $Id: dataRetriever.php 1723 2014-01-03 08:34:59Z hom $
# show uniform selector in all group def php pages
class DataRetriever {
    private $dbConn;
    private $selectorName='selector';
    private $query='';
    private $selectedElement=0;
    private $submitOnChange = false;
    private $fieldSetLegend = '';

    private $selectorHelp ='';
      
    function __construct( $conn ){
      global $_SESSION;
      global $_REQUEST;
      $this->dbConn = $conn;
    }

    public function setSelectorName( $n ) {
      $this->selectorName = $n;
    }

    function setQuery($q) {
      $this->query = $q;
    }

    function setSelectedElement( $e ) {
      $this->selectedElement = $e;
    }
    function getQuery(){
	return $this->query;
    }
    function getSelector(){
	$result="\t<select name='".$this->selectorName."' ".(($this->submitOnChange)?("onchange='submit()'"):("")).">\n".
	    getOptionListGrouped($this->dbConn,$this->getQuery(),
				 $this->selectedElement).
	    "\n\t</select>\n";
	return $result;
    }

    function getSelectedData() {
      if ($this->dataCache != null ) {
	return $this->dataCache;
      } 
      $sql = $this->query . " limit 1";
      
      $resultSet=$this->dbConn->Execute($sql);
      if ( $resultSet === false ) {
	echo( "<br>Cannot get project data with <pre>\"".$sql .'"</pre>, cause '.$this->dbConn->ErrorMsg()."<br>");
	stacktrace(1);
	die();
      }
      $this->dataCache= $resultSet->fields;
      return $this->dataCache;
    }

    function getWidget() {
      global $PHP_SELF;
      extract($this->getSelectedData());
      $result= "<fieldset><legend>".$this->fieldsetLegend."</legend><form method='get' action='$PHP_SELF'>\n"
	.$this->getSelector()
	."<input type='submit' name='s' value='Get'/>"
	."\n<br/>\n"
	.$this->getSelectionDetails()
	."\n</form>\n</fieldset>\n";
      return $result;
    }
    function getSimpleForm() {
      global $PHP_SELF;
      return "<form method='get' action='$PHP_SELF'>\n"
	.$this->getSelector()
	."<input type='submit' name='s' value='Get'/>"
	."\n<br/>\n"
	.$this->getSelectionDetails()
	."\n</form>\n";
    }
    function getSelectionDetails() {
      extract($this->getSelectedData());
      return "<table border='0'><tr><td>current selection</td><td style='font-size:160%'>".
	"<b>$afko.$course_short</b> $year<sub>($prj_id)</sub> milestone $milestone"
	." \"<i>$description</i>\" (prjm_id $prjm_id)</span></td></tr>"
	."<tr><td>Owning tutor</td><td ><strong>$tutor_owner</strong></td></tr></table> ";
    }
    public function __toString(){
	return 'dataReteriver for sql'.$this->getQuery();
    }
    public function setSubmitOnChange($b) {
	$this->submitOnChange=$b;
    }

    public function setFieldsetLegend( $l) {
      $this->fieldSetLegend=$l;
    }
    public function setSelectorHelp($h) {
      $this->selectorHelp=$h;
    }
}
?>