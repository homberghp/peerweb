<?php

require_once './peerlib/peerutils.inc';
require_once './peerlib/validators.inc';

# $Id: prjMilestoneSelector2.php 1841 2015-02-26 19:00:09Z hom $
# show uniform selector in all group def php pages

class PrjMilestoneSelector2 {

    private $dbConn;
    private $isAdmin = false;
    private $extraJoin = '';
    private $whereClause = '';
    private $peer_id = 879417;
    private $selectorName = 'prjm_id';
    private $submitOnChange = true;
    private $orderBy = "year desc,ismine desc,tutor_owner,css_class,afko,milestone";
    private $prjm_id = 0;
    private $dataCache = null;
    private $fieldsetLegend = 'Select project milestone combination';
    private $selectorHelp = '';
    private $selectionChanged = false;
    private $extraInfo = '';
    private $nullResult = array( 'first' => '99',
        'description' => 'no project',
        'year' => 0,
        'prjtg_id' => 0,
        'prj_id' => 0,
        'prjm_id' => 0,
        'milestone' => 0,
        'prjtg_id' => 0,
        'milestone' => 0,
        'tutor_owner' => '???',
        'afko' => '????',
        'valid_unit' => '1900-01-01',
        'assessent_due' => '2099-12-31',
        'grp_alias' => 'no group',
        'grp_num' => 0,
        'course_short' => '????'
    );

    function __construct( $conn, $peer_id, $prjm_id = 0, $selName = 'prjm_id' ) {
        global $_SESSION;
        global $_REQUEST;
        $this->dbConn = $conn;
        $this->peer_id = $peer_id;
        $this->prjm_id = $prjm_id;
        $this->selectorName = $selName;
        if ( isSet( $_SESSION[ $this->selectorName ] ) ) {
            $this->prjm_id = $_SESSION[ $this->selectorName ];
        }
        if ( isSet( $_REQUEST[ $this->selectorName ] ) ) {
            $newSelect = validate( $_REQUEST[ $this->selectorName ], 'integer', $this->prjm_id );
            if ( $this->prjm_id != $newSelect ) {
                $this->selectionChanged = true;
            }
            $this->prjm_id = $newSelect;
        }
        if ( $this->prjm_id === 0 ) { // only guess if undefined.
            $this->prjm_id = $this->guessPrjMid( $this->peer_id );
        }

        if ( hasCap( CAP_SELECT_ALL ) ) {
            $this->isAdmin = 'true';
        } else {
            //$this->whereClause =" tutor_id={$peer_id} ";
            //$this->extraJoin = " tutor_my_project_milestones({$peer_id}) tmpm on(pm.prjm_id=tmpm.prjm_id) ";
        }
    }

    public function setSelectorName( $n ) {
        $this->selectorName = $n;
    }

    function getQuery() {
        if ( $this->prjm_id === 0 || $this->prjm_id === '' ) { // only guess if undefined.
            $this->prjm_id = $this->guessPrjMid( $this->peer_id );
        }

        $sql = "select p.afko||'.'||trim(course_short)||': '||substr(p.description,1,12)||'('||p.year||')'||\n"
                . "' ['||t.tutor||'#'||p.prj_id||'] '||' mils. '||pm.milestone||coalesce(': '||pm.milestone_name,'') as name,\n"
                . "pm.prjm_id as value,p.owner_id as tutor_owner,\n"
                . " p.year||' ['||t.tutor||']' as namegrp, \n"
                . " case when p.owner_id={$this->peer_id} then 1 else 0 end as ismine,\n"
                . " case when now()::date <= valid_until and now()::date <=assessment_due then 'active'\n"
                . "      when now()::date <= valid_until and now()::date > assessment_due then 'cold' else 'inactive'"
                . " end as css_class\n"
                . " from project p join tutor t on(owner_id=userid) join prj_milestone pm using(prj_id) join fontys_course fc on (p.course = fc.course)\n"
                . (($this->extraJoin !== '') ? ("\njoin " . $this->extraJoin . "\n") : '')
                . (($this->whereClause !== '') ? ("\nwhere " . $this->whereClause . "\n") : '')
                . ' order by ' . $this->orderBy;
        //      echo "<pre style='padding:2em'>{$sql}</pre>";
        return $sql;
    }

    function getSelector() {
        $query = $this->getQuery();
        $result = "<!--prjMilestoneSelector2-->"
                . "\n\t<select name='" . $this->selectorName . "' " . (($this->submitOnChange) ? (" onchange='submit()' ") : ("")) . ">\n" .
                getOptionListGrouped( $this->dbConn, $query, $this->prjm_id )
                . "\n\t</select>\n"
                . "\n<!--/prjMilestoneSelector2-->\n";
        //        echo $query;
        return $result;
    }

    function guessPrjMid( $tutor_id ) {
        $sql = "select max(prjm_id) as guess from prj_tutor where tutor_id={$tutor_id} union select max(prjm_id) as guess from prj_tutor limit 1";
        $resultSet = $this->dbConn->Execute( $sql );
        echo "guessed {$resultSet->fields[ 'guess' ]}";
        return $resultSet->fields[ 'guess' ];
    }

    function getSelectedData() {
        if ( $this->dataCache != null ) {
            return $this->dataCache;
        }
        //echo "extra join <pre>{$this->extraJoin}</pre>";
        $sql = "select 0 as first, pm.prj_id,pm.prjm_id,pm.milestone,p.year,trim(p.afko) as afko,trim(p.description) as description"
                . ",t.tutor as tutor_owner,p.valid_until,pm.assessment_due,trim(fc.course_short) as course_short\n"
                . " from prj_milestone pm join project p using(prj_id) join tutor t on(owner_id=userid) join fontys_course fc on (p.course=fc.course)\n"
                . (($this->extraJoin != '') ? ("\njoin " . $this->extraJoin . "\n") : '')
                //. "join {$this->extraJoin} \n"
                . " where pm.prjm_id=" . $this->prjm_id
                . (($this->whereClause != '') ? ("\n and " . $this->whereClause . "\n") : '')
                . "\nunion\n"
                . "select 1 as first,pm.prj_id,pm.prjm_id,pm.milestone,p.year,trim(p.afko) as afko,trim(p.description) as description"
                . ",t.tutor as tutor_owner,p.valid_until,pm.assessment_due,trim(fc.course_short) as course_short\n"
                . " from prj_milestone pm join project p using(prj_id) join tutor t on(owner_id=userid) join fontys_course fc on (p.course=fc.course)\n"
                . (($this->extraJoin != '') ? ("\njoin " . $this->extraJoin . "\n") : '')
                . (($this->whereClause != '') ? ("\n where " . $this->whereClause . "\n") : '') . " order by first limit 1";
//        echo "<pre style='color:#800;padding:2em'>{$sql}</pre>";
        $resultSet = $this->dbConn->Execute( $sql );
        if ( $resultSet === false ) {
            echo( "<br>Cannot get project data with <pre>\"" . $sql . '"</pre>, cause ' . $this->dbConn->ErrorMsg() . "<br>");
            stacktrace( 1 );
            die();
        }
        if ( $resultSet->EOF ) {
            return $this->nullResult;
        } else {
            $this->dataCache = $resultSet->fields;
        }
        return $this->dataCache;
    }

    function getWidget() {
        global $PHP_SELF;
        extract( $this->getSelectedData() );
        $result = "\n<!--start prjMilestoneSelector->getWidget() -->\n"
                . "<fieldset><legend>" . $this->fieldsetLegend . "</legend><form method='get' action='$PHP_SELF'>\n"
                . $this->getSelector()
                . "<input type='submit' name='s' value='Get'/>"
                . "\n<br/>\n"
                . $this->getSelectionDetails()
                . "\n</form>\n{$this->extraInfo}</fieldset>\n<!-- end prjMilestoneSelector->getWidget()-->\n";
        return $result;
    }

    function getSimpleForm() {
        global $PHP_SELF;
        return "<form method='get' action='$PHP_SELF'>\n"
                . $this->getSelector()
                . "<input type='submit' name='s' value='Get'/>"
                . "\n<br/>\n"
                . $this->getSelectionDetails()
                . "\n</form>\n";
    }

    function getSelectionDetails() {
        extract( $this->getSelectedData() );
        return "<table border='0'><tr><td>current selection</td><td style='font-size:160%'>" .
                "<b>$afko.$course_short</b> $year<sub>(prj_id={$prj_id})</sub> milestone $milestone"
                . " \"<i>$description</i>\" (prjm_id $prjm_id)</span></td></tr>"
                . "<tr><td>Owning tutor</td><td ><strong>$tutor_owner</strong>, project valid until: $valid_until, milestone assessment due $assessment_due</td></tr></table> ";
    }

    function setWhere( $w ) {
        $this->whereClause = $w;
        $this->dataCache = null;
        return $this;
    }

    function setJoin( $j ) {
        if ( '' === $this->extraJoin ) {
            $this->extraJoin = $j;
        } else {
            $this->extraJoin .= ' join ' . $j;
        }
        $this->dataCache = null;
        return $this;
    }

    function setOrderBy( $o ) {
        $this->orderBy = $o;
        return $this;
    }

    public function __toString() {
        return 'prjMilestoneSelector2 for sql' . $this->getQuery();
    }

    public function setSubmitOnChange( $b ) {
        $this->submitOnChange = $b;
        return $this;
    }

    public function setPrjmId( $pm ) {
        $this->prjm_id = $pm;
        $this->dataCache = null;
        return $this;
    }

    public function setFieldsetLegend( $l ) {
        $this->fieldSetLegend = $l;
        return $this;
    }

    public function setSelectorHelp( $h ) {
        $this->selectorHelp = $h;
        return $this;
    }

    public function isSelectionChange() {
        return $this->selectionChanged;
        return $this;
    }

    public function getExtraInfo() {
        return $this->extraInfo;
        return $this;
    }

    public function setExtraInfo( $extraInfo ) {
        $this->extraInfo = $extraInfo;
        return $this;
    }

}

?>
