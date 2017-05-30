<?php

require_once './peerlib/peerutils.php';
require_once './peerlib/validators.php';
# $Id: studentPrjMilestoneSelector.php 1826 2014-12-27 15:01:13Z hom $
# show uniform selector in all group def php pages

class StudentMilestoneSelector {

    private $dbConn;
    private $isAdmin = false;
    private $extraJoin = '';
    private $whereClause = '';
    private $prjm_id = 0;
    private $judge = 1;
    private $submitOnChange = true;
    private $orderBy = "year desc,prj_grp_open desc,afko";
    private $fieldsetLegend = 'Select your project/milestone';
    private $extraConstraint = "";
    private $emptySelector = true;
    private $emptySelectorResult = "<h1>Sorry, the selection criteria do not result in any project/milestones</h1>";
    private $nullResult = array('description' => 'no project',
				'year' => 0,
				'prjtg_id'=>0,
				'prj_id'=>0,
				'prjm_id'=>0,
				'milestone'=>0,
				'prjtg_id'=>0,
				'milestone'=>0,
				'tutor_owner'=> '???',
				'afko'=>'????',
				'valid_unit' =>'1900-01-01',
				'assessent_due'=> '2099-12-31',
				'grp_alias'=> 'no group',
				'grp_num'=>0
				);

    function __construct( $conn, $judge, $prjm_id = 0 ) {
        global $_SESSION;
        global $_REQUEST;
        $this->dbConn = $conn;
        $this->judge = $judge;
        if ( $prjm_id ) {
            $this->prjm_id = $prjm_id;
        }
        if ( isSet( $_SESSION['prjm_id'] ) ) {
            $this->prjm_id = $_SESSION['prjm_id'];
        }
        if ( isSet( $_REQUEST['prjm_id'] ) ) {
            //	  $this->dbConn->log('found ' .$_REQUEST['prjm_id']);
            $this->prjm_id = validate( $_REQUEST['prjm_id'], 'integer',
                    $this->prjm_id );
        }
        if ( isSet( $_REQUEST['judge'] ) ) {
            $this->judge = validate( $_REQUEST['judge'], 'integer', $this->judge );
        }
        if ( hasCap( CAP_SYSTEM ) )
            $this->isAdmin = 'true';
    }

    function getQuery() {
        $sql = "SELECT distinct pm.prjm_id as value, \n" .
                "p.afko||': '||p.description||'('||p.year::text||')'||' milestone '||pm.milestone as name\n" .
                ",p.year as namegrp\n" .
                ", pg.prjtg_id,pm.prj_id,pm.prjm_id,pm.milestone,p.afko, p.year,\n" .
                " p.description,pt.grp_num,pg.prj_grp_open,ga.alias,pr.capabilities \n" .
                " FROM project p natural join prj_milestone pm natural join prj_tutor pt natural join prj_grp pg " .
                " natural left join grp_alias ga \n" .
                "left join student_role using(prjm_id,snummer)\n" .
                "left join project_roles pr using(prj_id,rolenum)\n" .
                " where snummer=" . $this->judge .
                ' ' . $this->extraConstraint .
                ' order by ' . $this->orderBy;
        //echo "<pre>".$sql."</pre>";
        return $sql;
    }

    function getSelector() {
        $resultSet = $this->dbConn->Execute( $this->getQuery() );
        if ( $resultSet->EOF ) {
            $this->emptySelector = true;
            $result = $this->emptySelectorResult;
        } else {
            $this->emptySelector = false;
	    if ($this->prjm_id === null) {
	      $this->prjm_id = $resultSet->fields['prjm_id'];
	    }
            $result = "\t<select name='prjm_id'" . (($this->submitOnChange) ? (" onchange='submit()' ") : ("")) . ">\n"
	      //                    . "<option value='0'>&nbsp;</option>"
                    . getOptionListGroupedFromResultSet( $resultSet,
                            $this->prjm_id ) .
                    "\n\t</select>\n";
        }
        return $result;
    }

    function getWidget() {
        global $PHP_SELF;
        $result = "<fieldset><legend>" . $this->fieldsetLegend . "</legend><form method='get' action='$PHP_SELF'>\n"
                . $this->getSelector()
                . (!$this->emptySelector ? "<input type='submit' name='s' value='Get'/>" : "")
                . "\n<br/>\n"
                . (!$this->emptySelector ? $this->getSelectionDetails() : 'No data')
                . "\n</form>\n</fieldset>\n";
        return $result;
    }

    function getSelectedData() {
        $sql = "select prjm_id,prj_id,prjm_id,prjtg_id,milestone,year,trim(afko) as afko,\n"
                . "trim(description) as description,\n"
                . "tutor_owner,valid_until,assessment_due, \n"
                . "trim(coalesce(alias,'g'||grp_num)) as grp_alias, grp_num  \n"
                . " from prj_grp pg join all_prj_tutor apt using(prjtg_id) where prjm_id=" . $this->prjm_id . ' and snummer=' . $this->judge;

        $resultSet = $this->dbConn->Execute( $sql );
        if ( $resultSet === false ) {
            echo( "<br>Cannot get project data with <pre>\"" . $sql . '"</pre>, cause ' . $this->dbConn->ErrorMsg() . "<br>");
            stacktrace( 1 );
            die();
        }
        if ( $resultSet->EOF ) {
	  return $this->nullResult;
        }
        return $resultSet->fields;
    }

    function getSelectionDetails() {
        if ( count( $this->getSelectedData() ) > 0 ) {
            extract( $this->getSelectedData() );
            return "Selected project <span style='font-weight:bold;font-size:120%;'>$afko $year \"$description\"$prjm_id $milestone $grp_alias $grp_num ($prjtg_id)</span> ";
        } else {
            return 'Your selection has no data';
        }
    }

    function setWhere( $w ) {
        $this->whereClause = $w;
        return $this;
    }

    function setJoin( $j ) {
        $this->extraJoin = $j;
        return $this;
    }

    function setOrderBy( $o ) {
        $this->orderBy = $o;
        return $this;
    }

    public function __toString() {
        return 'studentMilestoneSelector for sql' . $this->getQuery();
    }

    public function setSubmitOnChange( $b ) {
        $this->submitOnChange = $b;
    }

    public function isEmptySelector() {
        return $this->emptySelector;
    }

    public function setExtraConstraint( $c ) {
        $this->extraConstraint = $c;
    }

    public function setEmptySelectorResult( $r ) {
        $this->emptySelectorResult = $r;
    }

}

?>