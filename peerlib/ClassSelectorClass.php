<?php

require_once 'peerutils.php';

/**
 * Create a selector to select a group of stundetsn by class_id.
 * Allows to add constraints and  set autosubmit and name and id of generated 
 * html select.
 */
class ClassSelectorClass {

    private $dbConn;
    private $queryHead;
    private $queryConstriant;
    private $queryFrom;
    private $querySort;
    private $currentSelection;
    private $autoSubmit = false;
    private $selectorName = 'class_id';

    /**
     * Construct with db connection and current selection.
     * @global type $peer_id
     * @param type $conn the db connection
     * @param type $curSel current selection.
     */
    function __construct($conn, $curSel) {

        global $peer_id;
        $this->dbConn = $conn;
        $this->currentSelection = $curSel;
        $this->queryHead = "select  class_id as value, " .
                " sclass||' #'||class_id||', count '||coalesce(student_count,0) as name,\n" .
                "  trim(faculty_short)||'.'||trim(coalesce(cluster_name,'')) as namegrp, \n" .
                " cluster_order,sort1 ";
        //" from ";
        $this->queryFrom = "(select class_cluster, cluster_order from tutor_class_cluster where userid=$peer_id)"
                . " cc join  student_class cl using(class_cluster) natural left join class_size natural join faculty\n"
                . " natural left join class_cluster\n";
        //"  where 
        $this->queryConstriant = array(); //" student_count <>0\n";
        $this->querySort = " cluster_order, namegrp,sort1,name";
    }

    /**
     * Get the computed selector.
     * @return string the html select.
     *      */
    function getSelector() {
        $where = '';
        if (count($this->queryConstriant) > 0) {
            $where = "\n where \n"
                    . join("\n and ", $this->queryConstriant);
        }
        $query = $this->queryHead . "\n from\n "
                . $this->queryFrom
                . ' ' . $where . ' '
                . " order by "
                . $this->querySort;
        $result = "<select name='$this->selectorName' id='$this->selectorName'"
                . ($this->autoSubmit ? " onchange='submit()'" : "")
                . ">\n"
                . getOptionListGrouped($this->dbConn, $query, $this->currentSelection)
                . "</select>\n";
        return $result;
    }

    /**
     * Add a 'where' constraint. Constraints will be glued together with ' and '.
     * @param type $c
     * @return this \ClassSelectorClass
     */
    function addConstraint($c) {
        $this->queryConstriant[] = $c;
        return $this;
    }

    /**
     * Set autosubmit for generated select.
     * @param type $a true or false
     * @return this \ClassSelectorClass
     */
    function setAutoSubmit($a) {
        $this->autoSubmit = $a;
        return $this;
    }

    /**
     * Set the name of the selector.
     * @param type $n name
     * @return this \ClassSelectorClass
     */
    function setSelectorName($n) {
        $this->selectorName = $n;
        return $this;
    }

}

// eof ClassSelectorClass.php
?>
