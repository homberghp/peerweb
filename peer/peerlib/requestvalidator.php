<?php

/**
 * cleans up $_GET, $_POST and $_REQUEST
 * takes a hashmap of names => regexp to validate inputs.
 * Protects against input hacks
 */

class RequestValidator{
  /** a hashmap of regexes */
  private $regexMap;
  private $fp=0;
  private $logison=false;
  private $log_unknown_names;
  private $log_validation_failures;
  private $collect_unknown_names=true;
  private $page;
  private $errorcount;
  private $validMap;
  /** ctor , takes the map */
  public function __construct($map) {
    global $PHP_SELF;
    $this->log_unknown_names=false;
    $this->log_validation_failures=false;
    $this->page = substr($PHP_SELF,strlen(SITEROOT));
    $this->page = substr($this->page,1,strpos($this->page,'.php')-1);
    $this->errorcount = 0;
    $this->regexMap = $map;
    //    if ($this->log_unknown_names) {
      $this->logopen();
      //}
      $validMap = array();
  }
  
  /** work horse */
  function clean($name,$var,&$result,$pattern='/.*/'){
    global $dbConn;
    global $PHP_SELF;
    $errors=0;
    if (is_array($var)) {
      foreach( $var as $key => $val) {
	$starred='N';
	// if key is integer, assume we must take passed pattern 
	if (is_int($key)) {
	  $regex=$pattern;
	} else {
	  $ekey=$this->page.'.'.$key;
	  if (isSet($this->regexMap[$ekey]['regex'])) {
	    $regex=$this->regexMap[$ekey]['regex'];
	    $regexname=$this->regexMap[$ekey]['regex_name'];;
	    
	    $starred = $this->regexMap[$ekey]['starred'];
	  } else if (isSet($this->regexMap[$key]['regex']) ){
	    $regex=$this->regexMap[$key]['regex'];
	    $regexname=$this->regexMap[$key]['regex_name'];;
	    $starred = $this->regexMap[$key]['starred'];
	  } else {
	    $this->validMap[$key]='?';
	    $regex='/.*/'; // anything goes
	    $regexname='none';
            $val_msg=$val;
            if (is_array($val)) {
                $val_msg= implode($val);
                
            }
	    $this->logMissingName("missing pattern in page "
                    ."$this->page for "
                        . "$val_msg"
                        ."  $key or "
                    . "$ekey => $val_msg\n");
	    $this->addValidator($key);
	  }
	}
	if (!is_array($var[$key])) {
	  $nval = trim($val);
	  if ($this->isClean($key,$nval,$regex,$starred)) {
	    // set result
	    $result[$key] = $nval;
	    $this->validMap[$key]='V';
	  } else {
	    $this->validMap[$key]='I';
	    $msg="FAIL on page $PHP_SELF for '$key' => '$nval' against regex '$regexname' => '$regex'\n";

	    if ($this->log_validation_failures){
	      $dbConn->log($msg);
	      $this->logMissingName($msg);
	    }
	    $errors++;
	  }
	} else {
	  // array, recurse
	  $sub=array();
	  if ($this->clean($key,$var[$key],$sub,$regex)){
	    // set result
 	    $result[$key]=$sub;
	  } else {
	    $msg=" REJECTED ARRAY $key\n";
	    if ($this->log_validation_failures) {
	      $dbConn->log($msg);
	      $this->logMissingName($msg);
	    }
	  }
	}
      }
    } else {
      if (isSet($this->regexMap[$name])) {
	$regex=$this->regexMap[$name];
      } else {
	$regex='/.*/';
      }
      if ($this->isClean($name,$var,$regex))
	$result = $var;
      else { 
	$errors++;
      }
    }
    return $errors==0;
  }
  
  /**
   * check against pattern.
   */
  function isClean( $name, $s,$regex, $starred='N') {
    if ($starred =='Y') {
      // if wildcards or empty, pass
      if (strpos($s,'*')) return true;
      if (strpos($s,'?')) return true;
      if ($s =='') return true;
      $result = preg_match($regex,$s);
      //       $msg="$name $s => $st $pattern, $starred ".($result?'matched':'failed')."\n";
      //       $this->logMissingName($msg);
      if ( !$result ) { 
	$this->errorcount++;
      }
      
      return $result;
    } else {
      $result = preg_match($regex,$s);

      //       $msg="$name $s (org) $pattern, $starred ".($result?'matched':'failed')."\n";
      //       $this->logMissingName($msg);
      if ( !$result ) { 
	$this->errorcount++;
      }
      return $result;
    }
  }

  function  set_log_unknown_names( $b){
    $this->log_unknown_names=$b;
  }
  function  set_log_validation_failures( $b){
    $this->log_validation_failures=$b;
  }

  function logopen(){
    global $site_home;
    $this->fp=fopen($site_home.'/log/missing_validators.txt',"a+");
    if ($this->fp !== false) $this->logison=true;
  }
  
  function logMissingName($msg){
    if ($this->fp !== false  && $this->logison && $this->log_unknown_names){
      fwrite($this->fp,date(DATE_ISO8601).':'.$msg);
    }
  }

  function addValidator($key) {
    global $dbConn;
    if ($this->collect_unknown_names) {
      $sql = "select * from validator_occurrences where page = '".$this->page."' and identifier='$key'";
      $resultSet = $dbConn->Execute($sql);
      if ($resultSet->EOF) {
	$sql = "insert into validator_occurrences (page,identifier) values('".$this->page."','$key')";
	$dbConn->Execute($sql);
      } 
    }
  }
  function getErrorCount() { 
    return $this->errorcount;
  }
  function getValidMap(){
    return $this->validMap;
  }

  function validationClass($key){
    if (isSet($this->validMap[$key])) {
      switch($this->validMap[$key]) {
      case 'I': return 'invalid'; break;
      default: 
      case 'V': return 'valid'; break;
      }
    }
    else return 'valid';
  }
}

?>
