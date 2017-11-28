<?php

require_once("peerutils.php");
require_once('navigation2.php');
require_once("utils.php");
require_once('screenutils.php');
require_once('searchquery2.php');
require_once('DeleteChecker.php');
require_once 'SpreadSheetWriter.php';

/**
 * Simple table editor is a toolset to construct a page to edit a database
 * table record by record. This simple table editor provides basic functionsl
 * like search, insert, update and delete.
 * Using a template file, adapt 4 to 10 lines of code and you have a table
 * specific editor.
 * @package peerweb
 * @author Pieter van den Hombergh
 * $Id: ste.php 1859 2015-07-27 08:08:55Z hom $
 */

/**
 * Simple table editor details:
 * The editor reads the table definitions from the database. 
 * The menu, the menu_fields and the like are
 * contained in a separate file. The menu and its fields attributes (input-type, database variable type
 * etc) are stored in special database tables called MENU and MENU_ITEM respectively.
 * Can update, insert delete and search data in a table.
 * The layout of the editor consists of three parts: 
 * 1 the form which is used to enter user data for editing, searching etc
 * 2 a error-area where the database errors (if any) are put
 * 3 a list area, where the search results are listed.
 * The form is build by means of a Menu (see screenutils). This
 * again needs a template file which must be supplied to SimpleTableEditor.
 * The searching is done by assembling a searchQuery through a 
 * SearchQuery Object.
 * The form is filled with data from the $_GET or $_POST set, 
 * meaning that url of the type
 * page.php?column_name=column_value will select the proper database element.
 * @example ./template.php
 * @filesource
 */
class SimpleTableEditor {

    private $logQuery = true;
    private $searchQueryValues = null;
    private $searchQuery = null;

    /**
     * the constructor
     */
    function __construct(&$dbConn, &$page, $allowIUD = true) {
        global $PHP_SELF;
        global $validator;
        $this->dbConn = $dbConn;
        $this->itemValidator = $validator;
        $this->allowIUD = $allowIUD;
        $this->page = $page;
        $this->setDefaultButtons();
        $this->searchQuery = new SearchQuery($this->dbConn, null);
        if ($PHP_SELF == $_SESSION['ste_referer'] && isSet($_SESSION['searchQueryValues'])) {
            $this->searchQueryValues = $_SESSION['searchQueryValues'];
        }
    }

    private $page;
    private $queryLog = '';

    private function addLogQuery($l) {
        if ($this->logQuery) {
            $this->queryLog .= "<br/>\n" . $l;
        }
    }

    public function getPage() {
        return $this->page;
    }

    public function setPage($page) {
        $this->page = $page;
    }

    private $spreadSheetWriter;
    private $title;

    /**
     * Allow modifications to table?
     */
    private $allowIUD = true;

    /**
     * Name of relation to be edited.
     *
     * @var type */
    private $relation;

    /** DB connection to use */
    private $dbConn;

    /** */
    private $itemValidator;

    /**
     * To show extra data in the list tabl.
     * @var type join with other table 
     */
    private $listQueryExtension;

    /**
     * Set the extra data join for list table generation.
     * @param type $lqe
     */
    function setListQueryExtension($lqe) {
        $this->listQueryExtension = $lqe;
        return $this;
    }

    private $subRel = null;
    private $subRelJoinColumns = null;

    /**
     * An expression A that can serve part in a join (A) sub_rel on (...) subquery.
     * @param type $s
     * @return this SimpleTableEditor
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
     * @return this SimpleTableEditor
     */
    public function setSubRelJoinColumns($a) {
        if (is_array($a)) {
            $this->subRelJoinColumns = $a;
        }
        return $this;
    }

    /**
     * @param $rel string: relation (table or view) name
     */
    function setRelation($rel) {
        $this->relation = strtolower($rel);
//        $this->searchQuery->setRelation($rel);
//        $this->ste_query = 'select * from ' . $this->relation . ' where false';
        return $this;
    }

    function getLogHtml() {
        return $this->dbConn->getLogHtml();
    }

    function getLog() {
        return $this->dbConn->getLog();
    }

    private $isTransactional = false;

    public function setTransactional($t) {
        $this->isTransactional = $t;
        return $this;
    }

    /**
     * A relation (table or view) to support the user in editing the data proper.
     * Example: client data when editing a contract.
     */
    private $supportingRelation;

    /**
     * sets the supporting relation name
     * @param $rel string relation name
     */
    function setSupportingRelation($rel) {
        $this->supportingRelation = strtolower($rel);
        return $this;
    }

    /**
     * A join list of the form leftkey => rightkey
     * to get the data from the supporting Relation
     */
    private $supportingJoinList;

    /**
     * sets the supporting join list
     * @param $jl array of style left_column_name => right_column_name
     * which is used to left join the two tables
     */
    function setSupportingJoinList($jl) {
        $this->supportingJoinList = $jl;
        return $this;
    }

    /**
     * the menu used. Constructed by generateForm
     * @param $menu :string. This name is used to pick up all the items for this menu from the database
     */
    private $menu;

    /**
     * menuName. This is used in the database to get the field details.
     */
    private $menuName;

    function setMenuName($mn) {
        $this->menuName = strtolower($mn);
        return $this;
    }

    /**
     * The template to build the form. This can be a relativily simple html table
     * or something fancy, as long as the menu_fields are marked specially.
     * In this application there is a @see generateform.php page to generate (initial versions of) this kind of pages.
     */
    private $formTemplate;

    function setFormTemplate($ft) {
        $this->formTemplate = $ft;
        return $this;
    }

    /**
     * the layout of rows in the list
     * normaly 
     */
    private $listRowTemplate;

    function setListRowTemplate($lrt) {
        $newList = array();
        foreach ($lrt as $key => $value) {
            if (is_numeric($key)) {
                $newList[$value] = $value;
            } else {
                $newList[$key] = $value;
            }
        }
        $this->listRowTemplate = $newList;
        return $this;
    }

    /**
     * list (array) of columns that are primary key in this (the edited) table.
     */
    private $keyColumns;

    function setKeyColumns($kc) {
        $this->keyColumns = $kc;
        return $this;
    }

    /**
     * This sql-syntax element is used to create the link-tag, used in <a href=....></a> in the list that results from a query
     */
    private $nameExpression = '*';

    public function setNameExpression($ne) {
        $this->nameExpression = $ne;
        return $this;
    }

    /**
     * sets the value of a named menuitem
     * @param $name string: the items name
     * @param $value database value (string or number or date etc)
     */
    private function setValue($name, $value) {
        if (isSet($this->menu)) {
            $this->menu->setValue($name, $value);
        }
        return $this;
    }

    private $showQuery = false;

    function setShowQuery($b) {
        $this->showQuery = $b;
        return $this;
    }

    /**
     * gets the value of a named menuitem
     * @param $name string: the items name
     */
    function getValue($name) {
        if (isSet($this->menu)) {
            return $this->menu->getValue($name);
        }
        return NULL;
    }

    /**
     * The orderlist determines the sort order for the list resulting from a search.
     */
    private $orderList;

    function setOrderList($ol) {
        $this->orderList = $ol;
        return $this;
    }

    /**
     * The destination URL of the submit action of the form.
     */
    private $formAction;

    function setFormAction($act) {
        $this->formAction = $act;
        return $this;
    }

    /**
     * button template is a template file
     */
    private $buttonTemplate;

    /**
     * sets the buttonTemplate
     * @param buttonTemplate string filename of buttonTemplatefile.
     */
    function setButtonTemplate($bt) {
        $this->buttonTemplate = $bt;
        return $this;
    }

    /**
     * buttonsList is an assoc array of button definitions
     * buttons are primarily buttons but any construct will be used and instantiated
     * allowed abuse is adding regular input fields.
     */
    private $buttonList;

    /**
     * defaultButtons sets the default buttons
     */
    function setDefaultButtons() {
        if ($this->allowIUD) {
            $this->setButtonTemplate('templates/buttontemplate.html');
            $butDefs = array(
                array('name' => 'Clear', 'value' => 'Clear', 'accessKey' => 'C',
                    'type' => 'submit'), //,'onclick'=>'clearForm(this.form);'),
                array('name' => 'Search', 'value' => 'Search', 'accessKey' => 'S'
                    , 'options' => 'novalidate'),
                array('name' => 'Insert', 'value' => 'Add', 'accessKey' => 'I'),
                array('name' => 'Update', 'value' => 'Update', 'accessKey' => 'U'),
                array('name' => 'Delete', 'value' => 'Delete', 'accessKey' => 'D'),
                array('name' => 'Reset', 'value' => 'Reset', 'accessKey' => 'R',
                    'type' => 'reset')
            );
        } else {
            $this->setButtonTemplate('templates/buttontemplate_search_only.html');
            $butDefs = array(
                array('name' => 'Clear', 'value' => 'Clear', 'accessKey' => 'C',
                    'type' => 'submit'), //,'onclick'=>'clearForm(this.form);'),
                array('name' => 'Search', 'value' => 'Search', 'accessKey' => 'S'
                    , 'options' => 'novalidate'),
                array('name' => 'Reset', 'value' => 'Reset', 'accessKey' => 'R',
                    'type' => 'reset')
            );
        }

        $buttonList = array();
        for ($i = 0; $i < count($butDefs); $i++) {
            $this->makeButton($butDefs[$i]);
        }
        $buttonDefs = null;
    }

    /**
     * makes a button from def
     * @param $butDef array with indices 'name','value','accessKey'
     * created buttons are of type "submit", class "button" and add it to the buttonList
     * the button definition returned
     */
    function makeButton($butDef) {
        $onclick = 'this.form.submit()';
        if (isSet($butDef['onclick'])) {
            $onclick = 'onclick=\'' . $butDef['onclick'] . '\'';
        }
        $type = isSet($butDef['type']) ? $butDef['type'] : 'submit';
        $options = isset($butDef['options']) ? $butDef['options'] : '';
        $this->buttonList[$butDef['name']] = '<button type="' . $type . '" class="button" name="' . $butDef['name'] . '"' .
                ' accessKey="' . $butDef['accessKey'] . '" ' . $onclick . ' ' . $options . ' style="width:70px;" >' . $butDef['value'] . '</button>';
        return $this->buttonList[$butDef['name']];
    }

    /**
     * Add buttons to the buttonList
     * @param buttonDef array ('<name>'=><Def>);
     * use this to add buttons or other input fields
     * not that is possible to redefine (as in overwrite) buttons
     * there is no check for exsisting buttons.
     * example <code>addButton(array('SplitWeek'=>'<input type="submit" class="button" name="Splitweek" value="Splits" style=....>'))
     * </code>;
     */
    function addButton($buttons) {
        while (list($key, $value) = each($buttons)) {
            $this->buttonList[$key] = $value;
        }
    }

    /**
     * create the button table
     */
    function buttonTable() {
        if (isSet($this->buttonList)) {
            extract($this->buttonList, EXTR_PREFIX_ALL, 'button');
            include($this->buttonTemplate);
        }
    }

    /**
     * gets the keyValues from an assoc array (e.g. $_GET)
     * @param $arr the assoc to search in
     */
    private function getKeyValues($arr) {
        $result = array();
        foreach ($this->keyColumns as $kc) {
            if (!empty($arr[$kc])) {
                $result[$kc] = $arr[$kc];
            }
        }
        return $result;
    }

    /**
     * the search result generating query
     */
    private $list_query;

    /**
     * the menu generating query. (should fetch one record)
     */
    private $ste_query;

    /**
     * the key values of the record. (prim keys)
     * if set, should produce one record
     */
    private $keyValues;

    /**
     * dbMessage: string to which all db messages are appended
     */
    private $dbMessage;

    function addDbMessage($m) {
        if ($this->dbMessage) {
            $this->dbMessage .= "<br/>$m";
        } else {
            $this->dbMessage = $m;
        }
    }

    /**
     * set the menu values form a database result record
     */
    function setMenuValues($arr) {
        if (isSet($this->menu)) {
            $this->menu->setMenuValues($arr);
        }
        return $this;
    }

    /**
     * prepare this record for Insertion into database.
     */
    function prepareForInsert() {
        if (isSet($this->menu)) {
            return $this->menu->prepareForInsert($this->dbMessage);
        } else {
            $this->dbMessage .= "<br/>ste: Menu not defined";
            return false;
        }
    }

    protected $rawNames = null;

    public function setRawNames($a) {
        $this->rawNames = $a;
        return $this;
    }

    private function dbConnExecute($q) {
        return $this->dbConn->Execute($q);
    }

    /**
     * the actionURL is the set of keyColumns (name,value) and a
     * list_query packed onto the page URL
     * it is composed from a Search request-query and the keyColumns defining
     * the record presented in the Menu table.
     * the search request query is used to rebuild the resultlist
     */
    function buildActionURL() {
        $this->actionURL = $this->formAction;
        $urlGetOptions = '';
        $continuation = '?';
        if ($this->ste_query != '') {
            $rs = $this->dbConnExecute($this->ste_query);
            if ($rs === false) {
                $this->dbConn->log('Error occured, cause ' . $this->dbConn->ErrorMsg() . ' with statement ' . $this->ste_query);
                return 'Boe';
            }
            if (!$rs->EOF) {
                $this->setMenuValues($rs->fields);
                $this->keyValues = $this->getKeyValues($rs->fields);
            } else {
                $this->keyValues = array();
            }
        }
        if (count($this->keyValues) > 0) {
            // prepare a $_GET set for the action url,
            while (list($key, $val) = each($this->keyValues)) {
                $urlGetOptions .= $continuation . $key . '=' . $val;
                $continuation = '&amp;';
            }
        }
        if ($urlGetOptions != '') {
            $this->actionURL .= $urlGetOptions;
        }
    }

    /* end buildActionURL */

    /**
     * generate the form (embedded in a fieldset).
     */
    function generateMenu() {
        echo "<fieldset class='outer'><legend class='outer'>Fill in and choose</legend>\n" .
        "<form id=\"editform\" method=\"post\" action=\"$this->actionURL\">\n" .
        "<table>\n\t<tr>\n\t\t<td valign=\"top\">";

        $this->menu->setSubRel($this->subRel)
                ->setSubRelJoinColumns($this->subRelJoinColumns);
        $this->menu->setTemplateFileName($this->formTemplate);
        $this->menu->generate();
        echo "</td>\n\t\t<td valign=\"top\">";
        $this->buttonTable();
        echo "</td>\n\t</tr>\n</table>\n<input type=\"hidden\" name=\"keys\"/>\n" .
        "</form>";
        if (isSet($this->spreadSheetWriter) && isSet($this->ste_query) && ($this->ste_query !== '')) {
            echo $this->spreadSheetWriter->getForm($this->formAction);
        }
        echo "\n</fieldset>\n";
    }

    private $errorLog = '';

    function addError($e) {
        $this->errorLog .= $e;
    }

    /**
     * make the db message box 
     */
    function generateMessageBox() {
        $result = '';
        if (($this->dbMessage != '' || $this->queryLog != '' || $this->errorLog != '')) {
            $result = "<fieldset><legend>Database message</legend>\n";
            if ($this->dbMessage != '') {
                $result .= "<span style='font-weight:bold; color:#060;'>{$this->dbMessage}</span>\n";
            }
            if ($this->queryLog != '') {
                $result .= "<span style='font-weight:bold; color:#008;'>{$this->queryLog}</span>\n";
            }
            if ($this->errorLog != '') {
                $result .= "<span style='font-weight:bold; color:#800;'>{$this->errorLog}</span>\n";
            }
            $result .= "</fieldset>\n";
        }
        echo $result;
    }

    function expandListRowTemplate() {
        $result = '';
        $con = ', ';
        foreach ($this->listRowTemplate as $expr => $colName) {
            if ($expr === $colName) {
                $result .= $con . "$colName";
            } else {
                $result .= $con . "$expr as $colName";
            }
        }
        return $result;
    }

    function getHtmlHeaderListCells() {
        $result = '';
        foreach ($this->listRowTemplate as $expr => $colName) {
            $colName = nicerName($colName);
            $result .= "\t\t<th class = \"listhead\">{$colName}</th>\n";
        }

        return $result;
    }

    function getHtmlListCells($fields) {
        foreach ($this->listRowTemplate as $expr => $colName) {
            $colNames = preg_split('/\./', $colName);
            $lastCol = count($colNames) - 1;
            $colData = $fields[strtolower($colNames[$lastCol])];
            echo "\t<td class=\"listdata\">$colData</td>\n";
        }
    }

    function printResulList($rs) {
        echo "<table id='resultlist' class='tablesorter'>\n";
        $headRow = '';
        if (isSet($this->listRowTemplate)) {
            $headRow .= "<thead>\n\t<tr>\n\t\t<th>&nbsp;"
                    . "</th>\n\t\t<th class=\"listhead\" "
                    . "align=\"right\">#</th>\n"
                    . "\t\t<th class=\"listhead\">Link</th>\n";
            $headRow .= $this->getHtmlHeaderListCells() . "</tr>\n</thead>\n";
        }
        echo $headRow;
        echo "<tbody>\n";
        $counter = 1;
        while (!$rs->EOF) {
            $continuation = '?';
            $itsMe = '';
            $itsMeStyle = '';
            if ($this->keyColumnsEqual($rs->fields)) {
                $itsMe = '<img src="' . IMAGEROOT . '/right-arrow.gif" alt=">>"/>';
                $itsMeStyle = 'style=\'background:#fff;font-weight:bold\'';
            }
            echo "<tr $itsMeStyle>\n\t<td>$itsMe</td>\n" .
            "\t<td class=\"listdata\" align=\"right\">$counter</td>\n" .
            "\t<td class=\"listlink\">\n" .
            "\t\t<a href=\"" . htmlspecialchars($this->formAction);
            $urlTail = '';
            for ($i = 0; $i < count($this->keyColumns); $i++) {
                $urlTail .= $continuation . strtolower($this->keyColumns[$i]) . '=' . trim($rs->fields[strtolower($this->keyColumns[$i])]);
                $continuation = '&amp;';
            }
            echo $urlTail . "\">\n\t\t\t";
            echo trim($rs->fields['result_name']) . "\n\t\t</a>\n\t</td>\n";
            if (isSet($this->listRowTemplate)) {
                echo $this->getHtmlListCells($rs->fields);
            }
            echo "</tr>\n";
            $counter++;
            $rs->moveNext();
        } /* while OCI */
        echo "</tbody>\n</table>\n";
    }

    /**
     * generate a list (a table with one link per row)
     * from the Search request-query ($this->list_query)
     */
    function generateResultList() {

        if ($this->searchQueryValues != NULL) {
            $this->page->addHeadText('<link rel="stylesheet" href="style/tablesorterstyle.css" type="text/css" media="print, projection, screen" />')
                    ->addScriptResource('js/jquery-1.7.1.min.js')
                    ->addScriptResource('js/jquery.tablesorter.min.js')
                    ->addJqueryFragment("$('#resultlist').tablesorter({widthFixed: true, widgets: ['zebra']});");
            $this->searchQuery->setSubmitValueSet($this->searchQueryValues);
            try {
                $rs = $this->searchQuery->executeExtendedQuery();
                $this->printResulList($rs);
            } catch (SQLExecuteException $se) {
                $this->addError("cannot get list with {$se->getMessage()}<br/>");
            }
        } /* if (!empty...) */
    }

    /* function generateResultList() */

    /**
     * U P D A T E
     */
    function doUpdate() {
        /* test if all keycolumn values are set */
        $uq = new UpdateQuery($this->dbConn, $this->relation);
        $uq->setKeyColumns($this->keyColumns);

        /* refill menu from post data */
        /* done in processResponse    $this->menu->setMenuValues($_POST); */
        $this->menu->prepareForUpdate($this->dbMessage);
        $cnames = $this->menu->getColumnNames();
        $arr = $this->menu->getColumnValues($cnames);
        $uq->setSubmitValueSet($arr);
        $uq->setUpdateSet($arr);
        if ($uq->areKeyColumnsSet()) {
            /* allow update */
            try {
                $afrows = $uq->excute()->affected_rows();
                $this->addDbMessage(" {$afrows} row(s) updated");
            } catch (SQLExecuteException $se) {
                $this->addError("DB ERROR: Update failed with {$se->getMessage()}");
            }
        }
    }

    /**
     * 
     * @return typeGet the values from the form, filtered by type and generation type.
     * It replaces post values with new values from the relevant sequence.
     * @return the values to use form an insert.
     */
    function getPostValusForInsert(){
        $cnames = $this->menu->getColumnNames();
            $arr = $this->menu->getColumnValues($cnames);
        return $arr;
    }
    /* doUpdate() */

    /**
     * do database insert
     */
    function doInsert() {
        /* refill menu from post data */
        /* $this->menu->setMenuValues($_POST); */
        /* now test the menu values */
        if ($this->prepareForInsert()) {
            /* then get the data into the query */
            $iq = new InsertQuery($this->dbConn, $this->relation);
            $iq->setKeyColumns($this->keyColumns);
            // The menu knows how to generate values from form or e.g. sequences.
            $iq->setUpdateSet($this->getPostValusForInsert());
            if ($iq->areKeyColumnsSet()) {
                /* allow insert */
                try {
                    $affectedRows = $iq->execute()->affected_rows();
                    $this->addDbMessage("  added or updated {$affectedRows} record" . ($affectedRows == 1 ? '' : 's'));
                } catch (SQLExecuteException $se) {
                    $this->addError("failed with {$se->getMessage()}");
                }
            } else {
                $this->addError("DB ERROR: Insert failed with query <pre>{$iq}</pre><br/>Not all keyColumns have been set");
            }
        }
    }

    /**
     * deleteChecker is called (is set) to check if delete is allowed
     * deleteChecker is and object with the method (interface) checkForDelete($arr) where $arr a hashmap of
     * the record to be deleted. This can be used to check database consistency rules before the
     * delete is done. This function was built for an application which had a not properly normalised schema,
     * preventing a setup in which the database does the consistency check, which of course is preferable.
     */
    private $deleteChecker;

    function setDeleteChecker($dc) {
        $this->deleteChecker = $dc;
        return $this;
    }

    /**
     * execute the delete
     */
    function doDelete() {
        /**
         * is there a checker and does it allow delete?
         */
        if (isSet($this->deleteChecker)) {
            if (!$this->deleteChecker->checkForDelete($this->menu->getMenuValues(), $this->dbMessage)) {
                return 0;
            }
        }
        if (hasCap($this->menu->requiredCap)) {
            $dq = new DeleteQuery($this->dbConn, $this->relation);
            $dq->setKeyColumns($this->keyColumns);
            try {
                $res = $dq->execute();
                $rowCount = $res->affected_rows();
                if ($rowCount > 0) {
                    $this->dbMessage .= $rowCount . ' rows deleted';
                } else {
                    $this->addError('delete failed');
                }
            } catch (SQLExecuteException $sqe) {
                $this->addError('delete failed with ' . $sqe->getMessage());
            }
        }
    }

    /* end doDelete() */

    function doSearch() {
        global $_SESSION;
        //$this->searchQuery->setSubmitValueSet($_POST);
        $this->searchQueryTailText = $this->searchQuery->getQueryTailText();
        $this->searchQueryValues = $this->searchQuery->getPreparedValues();
        //echo " <pre>"; var_dump($this->searchQuery); echo " </pre>";
        if ($this->showQuery) {
            $this->addDbMessage("<br/>list query=<pre>{$this->searchQuery}</pre>");
        }
        $rs = $this->searchQuery->executeAllQuery2();
        //echo "<span style=' color:#f0f;font-size:120%' >  aha {$rs}</span>" ;
        if ($rs !== false && !$rs->EOF) {
            /* if search succeeded, load the first hit */
            $rowCount = $rs->rowCount();
            $this->addDbMessage("found {$rowCount} row" . ($rowCount == 1 ? '' : 's'));
            $this->setMenuValues($rs->fields);
            $this->keyValues = $this->getKeyValues($rs->fields);
            $_SESSION['searchQueryValues'] = $this->searchQueryValues = $this->searchQuery->getSubmitValueSet();
        } else {
            /* reload screen from _POST data */
            $this->setMenuValues($_POST);
            $this->dbMessage .= "Nothing found<br/>";
            unset($_SESSION['searchQueryValues']);
        }
    }

    /**
     * Process the user response from $_GET, $_POST or $_SESSION.
     * @return void
     */
    function processResponse() {
        global $PHP_SELF;
        global $_SESSION;
        global $validator_clearance;
        global $system_settings;
        $this->list_query = ''; // declare list query
        $this->ste_query = ''; // declare main query
        $this->menu = new ExtendedMenu($this->itemValidator, $this->page);
        if (isSet($this->rawNames)) {
            $this->menu->setRawNames($this->rawNames);
        }
        $this->menu->setFieldPrefix('veld');
        $this->menu->setItemDefQuery("select column_name,data_type,item_length," .
                "edit_type,query,capability,precision,placeholder,regex_name\n" .
                "from menu_item_defs where menu_name='$this->menuName'");

        $this->menu->setDBConn($this->dbConn);
        /*  let the menu learn about its content */
        $this->menu->setMenuName($this->menuName);
        $this->menu->setSubRel($this->subRel);
        $this->menu->setSubRelJoinColumns($this->subRelJoinColumns);
        /* now menu knows its columns, process the inputs */
        if (!empty($_SESSION['list_query']) && $PHP_SELF == $_SESSION['ste_referer']) {
            $this->list_query = $_SESSION['list_query'];
        }
        // prepare query width defintions  from client-page.
        $this->searchQuery = new SearchQuery($this->dbConn, $this->relation);
        $this->searchQuery->setKeyColumns($this->keyColumns);
        $this->searchQuery->setNameExpression($this->nameExpression);
        if (isSet($this->listRowTemplate)) {
            $this->searchQuery->setAuxColNames($this->listRowTemplate);
        }

        $this->searchQuery->setQueryExtension($this->listQueryExtension);
        $this->searchQuery->setOrderList($this->orderList);
        $this->searchQuery->setSubRel($this->subRel);
        $this->searchQuery->setSubRelJoinColumns($this->subRelJoinColumns);

        /* pick up potential key values from $_GET */
        $this->keyValues = $this->getKeyValues($_GET);
        /* pick up the _POST inputs such as the submit values */
        if (count($_POST) > 0) {
            $this->searchQuery->setSubmitValueSet($_POST);
            if (isSet($_POST['Clear'])) {
                /*
                 * L E E G
                 */
                /* throw away any old search result, i.e. the query */
                /* by kicking it out of the $_GET array */
                $_GET = array();
                $_POST = array();
                unset($_SESSION['searchQueryText']);
                unset($_SESSION['searchQueryValues']);
                unset($_SESSION['ste_referer']);
                $this->searchQueryTailText = null;
                $this->searchQueryValues = null;

                /* THATS all folks, empty results etc */
                return;
            }
            /* load only  if request is not LEEG */

            $this->setMenuValues($_POST);
            if ($validator_clearance) {
                // save edit values to session.
                if (isSet($system_settings['edit_to_session'])) {
                    $save = explode(',', $system_settings['edit_to_session']);
                    foreach ($save as $s) {
                        list($k, $d) = split('=', $s);
                        $v = $d;
                        if (isSet($_POST[$k]) && $_POST[$k] !== '') {
                            $v = $_POST[$k];
                        }
                        $_SESSION[$k] = $v;
                    }
                }

                if (isSet($_POST['Search'])) {
                    /*
                     * S E A R C H
                     */

                    /** build a query from the $_POST data */
                    $this->doSearch();
                } else if ($this->allowIUD && isSet($_POST['Insert'])) {
                    /*
                     * I N S E R T
                     */
                    $this->doInsert();
                } else if ($this->allowIUD && isSet($_POST['Update'])) {
                    $this->doUpdate();
                } else if ($this->allowIUD && isSet($_POST['Delete'])) {
                    /*
                     * D E L E T E
                     */
                    $this->doDelete();
                } else if (isSet($_POST['Reset'])) {
                    /*
                     * reset is handled by the browser
                     */
                }
            } else {
                // redisplay input
                $this->setMenuValues($_POST);
            }
        }/* end of if (count($_POST))) */ {
            /*
             * use _GET to determine the key columns
             */
            $sq = new SearchQuery($this->dbConn, $this->relation);
            $sq->setKeyColumns($this->keyColumns);
            $sq->setNameExpression($this->nameExpression);
            $sq->setOrderList($this->orderList)
                    ->setSubRel($this->subRel)
                    ->setSubRelJoinColumns($this->subRelJoinColumns);
            $sq->setSubmitValueSet($_GET);
            //var_dump($_GET);
            if ($sq->areKeyColumnsSet()) {

                try {
                    $rs = $sq->executeAllQuery2();
                    $rowCount = $rs->rowCount();
                    $this->addDbMessage("found {$rowCount} row" . ($rowCount == 1) ? '' : 's');
                    if ($rs !== false && !$rs->EOF) {
                        $this->setMenuValues($rs->fields);
                        $this->dbConn->log("<pre>" . print_r($rs->fields, true) . "</pre><br>");
                        $this->keyValues = $this->getKeyValues($rs->fields);
                    }
                } catch (SQLExecuteException $se) {
                    $this->addError("search failed with {$se->getMessage()}");
                }
            }
        } /* end of else branch if (count($_POST)) */
    }

    /* end processResponse() */

    /**
     * Do it all, proces the user response and
     * generate the actual record menu, buttons, table, messageBox, and result list
     */
    function generateForm() {
        global $PHP_SELF;
        global $server_url;
        $fdate = date('Y-m-d');
        $filename = $this->menuName . '-' . $fdate;
        $this->processResponse();
        if (isSet($this->spreadSheetWriter)) {
            $this->spreadSheetWriter->setTitle("peerweb query $fdate")
                    ->setLinkUrl($server_url . $PHP_SELF)
                    ->setFilename($filename)
                    ->setAutoZebra(true);
            $this->spreadSheetWriter->processRequest();
        }

        /*
         * All processing is done, showtime 
         * first build an action URL for this page. That is, save the list_query in the _GET by adding it
         * to the url.
         */
        $this->generateHTML();
        $_SESSION['ste_referer'] = $PHP_SELF;
        //        $_SESSION['searchQueryText'] = $this->searchQueryTailText;
        $_SESSION['searchQueryValues'] = $this->searchQueryValues;
    }

    /**
     * get form as string for component model
     */
    function generateFormString() {
        ob_start();
        $this->generateForm();
        $result = ob_get_contents();
        ob_clean();
        return $result;
    }

    /**
     * generate the HTML, building the page and send things off to the browser.
     */
    function generateHTML() {
        $this->buildActionURL();
        $this->generateMenu();
        $this->generateMessageBox();
        /* create the list */
        $this->generateResultList();
    }

    /* generateHTML() */

    /**
     * display content of this simple table editor.
     */
    public function __toString() {
        return 'SimpleTableEditor for ' . $this->menu->toString();
    }

    /**
     * Compare this->keyColumns values with the keyColumnValues of the passed array.
     * return if the keyColumn-values are the same. In that case the passed record may be assumed to be the same.
     * @param $arr assoc array: a db record.
     * Is used in generating the list to point at the current record.
     */
    function keyColumnsEqual(&$arr) {
        $result = true; // start optimistically
        for ($i = 0; $result && $i < count($this->keyColumns); $i++) {
            $result = $result && isSet($arr[$this->keyColumns[$i]]) && isSet($this->keyValues[$this->keyColumns[$i]]) && ($arr[$this->keyColumns[$i]] == $this->keyValues[$this->keyColumns[$i]]);
        }
        return $result;
    }

    function setTitle($t) {
        $this->title = $t;
        return $this;
    }

    private $topText = '';

    function setTopText($t) {
        $this->topText = $t;
        return $this;
    }

    /**
     * Render the final result on a page.
     */
    function render() {
        $this->page->addBodyComponent(new Component($this->topText))
                ->addBodyComponent(new Component($this->generateFormString()));
    }

    /**
     * Use page to show self.
     */
    function show() {
        $nav = new Navigation(array(), $this->formAction, $this->page->getTitle());
        $this->page->addBodyComponent($nav);
        $this->render();
        $this->page->show();
    }

}

/* $Id: ste.php 1859 2015-07-27 08:08:55Z hom $ */
?>
