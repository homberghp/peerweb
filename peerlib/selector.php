<?php

# $Id: selector.php 1723 2014-01-03 08:34:59Z hom $
# show uniform selector in all group def php pages

class Selector {

    private $dbConn;
    private $query = '';
    private $selected = '';
    private $selector_name = '';
    private $auto_submit = false;
    private $groupedSelector = false;

    public function setGroupedSelector( $groupedSelector ) {
        $this->groupedSelector = $groupedSelector;
        return $this;
    }

    /**
     * Create a selector.
     * @param type $conn database connection
     * @param type $selector_name the name of the form element 
     * @param type $query the database query
     * @param type $selected the currrent selected value
     * @param type $auto_submit when to autosubmit.
     */
    function __construct( $conn, $selector_name, $query, $selected, $auto_submit = false ) {
        $this->dbConn = $conn;
        $this->selector_name = $selector_name;
        $this->query = $query;
        $this->selected = $selected;
        $this->auto_submit = $auto_submit;
    }

    function getQuery() {
        return $this->query;
    }

    function getSelector() {
        $asubmit = ($this->auto_submit) ? 'onchange=\'submit()\'' : '';
        $result = "\t<select name='$this->selector_name' $asubmit>\n" .
                ($this->groupedSelector ? getOptionListGrouped( $this->dbConn, $this->getQuery(), $this->selected ) : getOptionList( $this->dbConn, $this->getQuery(), $this->selected )) .
                "\n\t</select>\n";
        return $result;
    }

    public function __toString() {
        return $this->getSelector();
    }

    public function getAuto_submit() {
        return $this->auto_submit;
    }

    public function setAuto_submit( $auto_submit ): void {
        $this->auto_submit = $auto_submit;
    }

}
