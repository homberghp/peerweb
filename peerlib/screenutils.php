<?php

require_once('peerutils.php');
require_once('MenuField.class.php');

/**
 * Menu builds a (table) part of a form dependent on a relation (table)
 * and a template.
 */
class Menu {

    var $dbConn;
    var $menuName;
    var $menuItems;
    var $columnCount;
    var $templateFileName;
    var $fieldPrefix;
    var $itemValidator;
    var $requiredCap;
    private $logString = '';

    function setFieldPrefix($p) {
        $this->fieldPrefix = $p;
    }

    private $subRel = null;
    private $subRelJoinColumns = null;

    /**
     * An expression A that can serve part in a join (A) sub_rel on (...) subquery.
     * @param type $s
     * @return this menu
     */
    public function setSubRel($s) {
        if ($s !== '') {
            $this->subRel = $s;
        }
        return $this;
    }

    /**
     * Set array that maps left part of join to right part.
     * @param array. Keys are left hand, values right hand column names $a
     * @return this menu
     */
    public function setSubRelJoinColumns($a) {
        if (is_array($a)) {
            $this->subRelJoinColumns = $a;
        }
        return $this;
    }

    var $itemDefQuery;
    var $page;

    function __construct($val, $page) {
        //    echo '<br> created Menu';
        $this->setFieldPrefix('veld');
        $this->itemValidator = $val;
        $this->page = $page;
    }

    function setItemDefQuery($q) {
        $this->itemDefQuery = $q;
    }

    function setValue($name, $value) {
        if (isSet($this->menuItems[$name])) {
            $this->menuItems[$name]->setValue($value);
        }
    }

    function getValue($name) {
        if (isSet($this->menuItems[$name])) {
            return $this->menuItems[$name]->getValue();
        }
        return NULL;
    }

    function setDBConn(&$con) {
        $this->dbConn = $con;
        return $this;
    }

    private $relation;

    function setMenuRelation($r) {
        $this->relation = $r;
        return $this;
    }

    protected $rawNames = null;

    function setRawNames($ar) {
        $this->rawNames = array();
        foreach ($ar as $name) {
            $this->rawNames[$name] = '';
        }
    }

    /**
     * @paramn name String
     * sets the menu name and by that the field definitions
     */
    function setMenuName($name) {
        $this->menuName = $name;
        $this->menuItems = array();
        // get the field definitions from the database
        // and generate the menuitem defs
        $dbMessage = '';
        $sql = $this->itemDefQuery;
        $resultSet = getFirstRecordSetFields($this->dbConn, $sql);
        while (!$resultSet->EOF) {
            $name = trim($resultSet->fields['column_name']);
            //echo "myname=".$name. ",";
            $edit_type = trim($resultSet->fields['edit_type']);
            if ($edit_type == 'B') { /* handle bitsets seperately, they expand into a set of items */
                $dbM2 = '';
                $sql2 = "select query from menu_option_queries where menu_name='$this->menuName' and column_name='$name'";
                $resultSet2 = getFirstRecordSetFields($this->dbConn, $sql2);
                $nameList = trim(',', $resultSet2->fields['query']);
            } else {
                $mi = new MenuField($this->dbConn, $this->itemValidator, $this->page);
                $mi->setDef($resultSet->fields);
                $mi->setName($name);
                $this->menuItems[$name] = $mi;
            }
            $resultSet->moveNext();
        }
    }

    /**
     * gets values from a database record set
     * $stmh is assumed to be prepared
     * @param $arr record set
     * at then end the arr is reset (local iterator is set to 0)
     */
    var $menuValues;

    function getMenuValues() {
        return $this->menuValues;
    }

    function setMenuValues(&$arr) {
        /* test precondition */

        if (!isSet($this->menuItems)) {
            die("Must first do a setMenuName()");
        }
        /* cache */
        $this->menuValues = $arr;
        //    reset($arr);
        foreach ($arr as $key => $val ){
            $name = trim($key);
            // test if this 'name' is present
            if (isSet($this->menuItems[$name])) {
                $value = trim($val);
                $this->menuItems[$name]->setValue($value);
            }
        }
    }

    /**
     * get the names of all columns 
     */
    function getColumnNames() {
        $result = array();
        $i = 0;
        foreach ($this->menuItems as $name => $value) {
            $result[$i++] = $name;
        }
        return $result;
    }

    /**
     * gets values of the named columns from the menuItems
     */
    function getColumnValues($columnNames) {
        $result = array();
        for ($i = 0; $i < count($columnNames); $i++) {
            $name = $columnNames[$i];
            if (isSet($this->menuItems[$name])) {
                $result[$name] = $this->menuItems[$name]->getValue();
            }
        }
        return $result;
    }

    /**
     * the contents of the file is included/evaluated in the generate process
     */
    function setTemplateFileName($name) {
        $this->templateFileName = $name;
    }

    /*
     * prepare the values for insert into db
     * validate and or get sequence.nextvalue
     * @param $resultBuffer buffer to append any error text to.
     */

    function prepareForInsert(&$resultBuffer) {
        /* for all items do it */
        $result = true;
        $columnNames = $this->getColumnNames();
        for ($i = 0; $i < count($columnNames); $i++) {
            $name = $columnNames[$i];
            // AND all the values in next line with &=. Any failure (false) will set result false.
            $result &= $this->menuItems[$name]->prepareForInsert($resultBuffer);
        }
        return $result;
    }

    /*
     * prepare the values for update
     * @param $resultBuffer buffer to append any error text to.
     */

    function prepareForUpdate(&$resultBuffer) {
        /* for all items do it */
        $result = true;
        $columnNames = $this->getColumnNames();
        for ($i = 0; $i < count($columnNames); $i++) {
            $name = $columnNames[$i];
            // AND all the values in next line with &=. Any failure (false) will set result false.
            $result &= $this->menuItems[$name]->prepareForUpdate($resultBuffer);
        }
        return $result;
    }

    /**
     * expand all items
     */
    var $expandedMenuItems;

    function expand() {
        if (!isSet($this->menuItems)) {
            return;
        }
        $this->expandedMenuItems = array();
        reset($this->menuItems);
        foreach ($this->menuItems as $miName => $menuItem ) {
            $this->expandedMenuItems[$miName] = $menuItem->expand();
        }
    }

    /**
     * first all the menuItems are expanded into a new array
     * then that array is extracted, thereby setting the menu_items
     * then these values are evaluated into the template.
     */
    function generate() {
        $this->expand();
        //$this->logString .= "<br/>menu.generate <pre>" . print_r($this->expandedMenuItems, true) . "</pre><br/>";
        if (isSet($this->rawNames)) {
            extract($this->rawNames, EXTR_PREFIX_ALL, 'raw');
        }
        extract($this->expandedMenuItems, EXTR_PREFIX_ALL, $this->fieldPrefix);
        echo $this->getSubRelData();
        extract($this->getSubRelData(), EXTR_PREFIX_ALL, 'supp');

        include($this->templateFileName);
        unSet($this->expandedMenuItems); // discard
    }

    /**
     * display content. In other words puke.
     */
    function toString() {
        $result = 'Menu ' . $this->menuName . ' itemlist: ' . "\n\t";
        reset($this->menuItems);
        foreach ( $this->menuItems as $key){
            $result .=$this->menuItems[$key]->toString();
        }
        reset($this->menuItems);
        return $result;
    }

    public function __toString() {
        return $this->toString();
    }

    function getSubRelData() {
        if (!isSet($this->subRel) || !isSet($this->subRelJoinColumns)) {
            // return empty result.
            return array();
        }
        $query = new SearchQuery($this->dbConn, $this->relation);
        $query->setSubRel($this->subRel)
                ->setSubRelJoinColumns($this->subRelJoinColumns);
        $sql = $query->getSubRelQuery();
        $this->logString .= 'subrel:' . $sql;
        return $this->dbConn->Execute($sql)->fields;
    }

    function getLogString() {
        return $this->logString;
    }

}

/* Menu */

/**
 * a Menu with a not editable part, the supporting columns
 */
class SupportingMenu extends Menu {

    var $supportingRelation;
    var $supportingJoinList;

    function setSupportingJoinList($sjl) {
        $this->supportingJoinList = $sjl;
    }

    function setSupportingRelation($rel) {

        $this->setItemDefQuery("select column_name,data_type,data_length," .
                " 'Z' as edit_type,'' as query,0 as capability " .
                "from all_tab_columns  where table_name='$rel'");
        $this->setMenuName($rel);
        // echo '<pre>'.bvar_dump($this->menuItems).'</pre>';
    }

    /**
     * pick up the values from the 'main' value set
     * and get supporting data through join query
     */
    /*
     *
     */
    var $joinValues;

    function setJoinValues(&$arr) {
        if (isSet($this->menuName) && isSet($arr)) {
            $this->joinValues = $arr;
            $sjq = new SupportingJoinQuery();
            $sjq->setRelation($this->menuName);
            $sjq->setKeyMap($this->supportingJoinList);
            $sjq->setSubmitValueSet($this->joinValues);
            $sql = $sjq->getQuery();
            if ($sql != '') {
                $dbMessage = '';
                $larr = array();
                $rs = $this->dbConn->Execute($sql);
                $this->setMenuValues($rs->fields);
                echo $sql;
            }
        }
    }

}

/**
 * a Menu with a not editable part, the supporting columns
 */
class ExtendedMenu extends Menu {

    var $supportingMenu;

    function __construct($val, &$page) {
        parent::__construct($val, $page);
        $this->supportingMenu = new SupportingMenu($val, $page);
    }

    function setSupportingRelation($rel) {
        $this->supportingMenu->setSupportingRelation($rel);
        return $this;
    }

    var $supportingJoinList;

    function setSupportingJoinList($sjl) {
        $this->supportingMenu->setSupportingJoinList($sjl);
        return $this;
    }

    function setSupportingValues(&$arr) {
        $this->supportingMenu->setMenuValues($arr);
        return $this;
    }

    function setDBConn(&$con) {
        $this->dbConn = $con;
        $this->supportingMenu->setDBConn($con);
        return $this;
    }

    /**
     * as parent-> generate, but first expand supporting Items
     * first all the menuItems are expanded into a new array
     * then that array is extracted, thereby setting the menu_items
     * then thes values are evaluated into the template.
     */
    function generate() {
        $this->supportingMenu->setJoinValues($this->menuValues);
        $this->expand();
        extract($this->expandedMenuItems, EXTR_PREFIX_ALL, 'veld');
        if (isSet($this->menuValues)) {
            extract($this->menuValues, EXTR_PREFIX_ALL, 'raw');
        } else if (isSet($this->rawNames)) {
            extract($this->rawNames, EXTR_PREFIX_ALL, 'raw');
        }


        $this->supportingMenu->expand();
        if (isSet($this->supportingMenu->expandedMenuItems)) {
            extract($this->getSubRelData(), EXTR_PREFIX_ALL, 'supp');
        }
        include($this->templateFileName);
        unSet($this->expandedMenuItems); // discard
    }

}

/* $Id: screenutils.php 1769 2014-08-01 10:04:30Z hom $ */
