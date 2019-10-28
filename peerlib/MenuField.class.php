<?php

require_once('peerutils.php');
require_once 'youtubelink.php';

/**
 * Menufield creates a input element to be put into a form (possibly inside a table)
 * The main interface consists of a recordSet, containing the data to be shown,
 * and a fieldSet containing the description of the fields.
 * It is assumed that the fieldSet defines the variables to be presented.
 * The fieldSet is an (associative) array with the fieldname as key
 * and a set of attributes as value.
 * It is assumed that the field names and the names of the recordset are the same.
 * The generated field is either and input element (text or hidden, possibly readonly) or
 * a select with an optionList.
 */
class MenuField {

    /**
     * input 'name' of the &lt;input&gt; tag
     */
    var $name;

    /**
     * input 'value' of the &lt;input&gt; tag
     */
    var $value;

    /** for database */
    var $dbConn;

    /**
     * data_type CHAR or NUMBER. Only first letter is considered. if N adjust right, else default
     */
    var $data_type;

    /**
     * size in input tag (if 0 , size is not written in input, and takes browser default)
     */
    var $item_length;

    /**
     * number of digits in input tag (if 0 , size is not written in input, and takes browser default)
     */
    var $data_precision;

    /**
     * number of digits after point
     */
    var $data_scale;

    /**
     * edit_type supported are T(ext) (default) N(oneditable) (visable but disabled), 
     * H(idden) and S(elect) (select with optionList) se(Q)uence (P)icture
     */
    var $edit_type;

    /**
     * query to fill the select option list.
     */
    var $selectQuery;

    /**
     * capability to be able to edit this field
     */
    var $capability;
    var $optionPreloadList;
    var $itemValidator;
    var $placeholder = 'some input';
    var $nullable = '?';

    function setPlaceHolder($p) {
        $this->placeholder = $p;
        return $this;
    }

    function setOptionPreloadList($list) {
        $this->optionPreloadList = $list;
    }

    function setName($val) {
        $this->name = $val;
        // echo '<br>setName on '.$this->name.'<br>';
    }

    function getValue() {
        return $this->value;
    }

    /** quite obvious */
    function getName() {
        return $this->name;
    }

    function setValue($val) {
        $this->value = $val;
        //    echo '<hr>setValue on '.$this->name.' value '.$val.'<br>';
    }

    function setData_Type($val) {
        $this->data_type = $val;
    }

    function setItem_Length($val) {
        $this->item_length = $val;
        //    echo 'item_length '.$this->item_length.'<br>';
    }

    function setData_Precision($val) {
        $this->data_precision = $val;
        //    echo 'data_precision '.$this->data_precision.'<br>';
    }

    function setData_Scale($val) {
        $this->data_scale = $val;
        //    echo 'data_scale '.$this->data_scale.'<br>';
    }

    function setEdit_Type($val) {
        $this->edit_type = $val;
        //    echo $val;
    }

    function setSelectQuery($val) {
        $this->selectQuery = $val;
    }

    function setCapability($val) {
        $this->capability = $val;
    }

    /**
     * constructor
     */
    function __construct(&$con, &$valid, &$page) {
        $this->dbConn = $con;
        $this->optionPreloadList = array(array('name' => '&nbsp;', 'value' => ''));
        $this->itemValidator = $valid;
        $this->page = $page;
    }

    var $page;

    /**
     * Prepare for database insert op.
     * relevant for items of type sequence and creator_owner. They should get the value here.
     * @param $dbConn database connection
     * @param resultbuffer: in case of failure, text is appended to this buffer
     * @result boolean, true on success
     */
    function prepareForInsert(&$resultBuffer) {
        global $peer_id;
        $result = true;
        $resText = '';
        switch ($this->edit_type) {
            case 'Q':
                /* its a sequence. The table menu_option_queries contains the sequence name in the column query */
                $seqVal = sequenceNextValue($this->dbConn, $this->selectQuery);
                if (!$seqVal === false) {
                    $this->setValue($seqVal);
                } else {
                    $result = false;
                }
                break;
            case 'C': // creator-owner
            case 'U': /* set mutator */
                $this->setValue($peer_id);
                break;
            case 'X':
                $this->setValue(getDBTime($dbConn));
                break;
            case 'T': // trim
                if ($this->item_length) {
                    $this->setValue(substr($this->getValue(), 0, $this->item_length));
                }
                break;
            default:
                /* validate inputs is already done en masse on the $_REQUEST[] array. */
                break;
        }
        $resultBuffer .= $resText;
        return $result;
    }

    /* end prepareForInsert */

    /**
     * prepare for database update.
     *
     * not relevant for items of type sequence as they are key,and keycolumns are not updatable. 
     * Relevant for mutator and mutator date.
     * @param $dbConn database connection
     * @param resultbuffer: in case of failure, text is appended to this buffer
     * @result boolean, true on success
     */
    function prepareForUpdate(&$resultBuffer) {
        global $peer_id;
        $result = true;
        $resText = '';
        switch ($this->edit_type) {
            case 'U': /* set mutator */
                $this->setValue($peer_id);
                break;
            case 'X':
                $this->setValue(getDBTime($this->dbConn));
                break;
            case 'T':
                if ($this->item_length) {
                    $this->setValue(substr($this->getValue(), 0, $this->item_length));
                }
                break;
            default:
                /* validate inputs */
                break;
        }
        $resultBuffer .= $resText;
        return $result;
    }

    /* end prepareForInput */

    /**
     * expand the item into an <input...> or <select>...</select>
     */
    function expand() {
        global $validator;
        global $_SESSION;
        //global $page;
        global $login_snummer;
        $cssClass = "{$validator->validationClass($this->name)} ";
        $result = '';
        $onChange = '';
        $alignText = ' style="text-align:left;"';
        $textSize = $this->item_length;
        $cssClass .= $this->nullable === 'N' ? 'required ' : '';

        switch (substr($this->data_type, 0, 1)) {
            case 'N':
                $textSize = $this->data_precision;
                $alignText = ' style="text-align:right;" ';
                break;
            default:
            case 'C':
                break;
        }
        $readText = (hasCap($this->capability)) ? '' : ' readonly="readonly"';
        $sizeText = ($textSize > 0) ? ' size="' . $textSize . '"' : '';
        switch ($this->edit_type) {
            case 'A':
                $maxCols = 80;
                $cols = $textSize;
                $rows = 1;
                if ($textSize > $maxCols) {
                    $cols = $maxCols;
                    $rows = floor(($textSize + $maxCols) / $maxCols);
                }
                // HACK
                //	    $rows=12;$cols=72;
                $result .= '<textarea id="' . $this->name . '" name="' . $this->name
                        . '" cols="' . $cols . '" rows="' . $rows . '">' . $this->value . '</textarea>';
                break;
            // informational, value only, no vardef.
            case 'b':
                $checkedTrue = (isSet($this->value) && $this->value == 't') ? 'selected' : '';
                $checkedFalse = (isSet($this->value) && $this->value == 'f') ? 'selected' : '';
                /* $result .= "<input type='checkbox' {$checked} name='{$this->name}'" */
                /*         . " value='{$this->placeholder}' " */
                /*         . "style='vertical-align:middle;'><span style='font-weight:bold'>" */
                /*                 .niceName($this->name) */
                /*                 ."</span></input>\n"; */
                $result .= "<label style='font-weigth:bold' for='{$this->name}'>{$this->name}</label><select name='{$this->name}' id='{$this->name}'>\n\t<option value=''></option>\n"
                        . "\t<option " . $checkedFalse . " value='false'>False</options>\n"
                        . "\t<option " . $checkedTrue . " value='true'>True</options>\n"
                        . "</select>\n";
                break;
            case 'I':
                $result .= '<div class="informational"' . $alignText . "class=\"{$cssClass}\"" . '>' . $this->value . '</div>&nbsp;' . "\n";
                break;
            /* hidden only */
            case 'H': $result .= '<input type="hidden" id="' . $this->name . '" name="' . $this->name . '" value ="' . $this->value . '"/>' . "\n";
                break;
            /* Visible, not editable */
            case 'Q':
                $result .= '<input type="hidden" id="' . $this->name . '" name="' . $this->name . '" value ="' . $this->value . '"/>' .
                        '<span class="sequence">&nbsp;' . $this->value . '</span>' . "\n";
                break;
            case 'V':
                $result .= '<input type="hidden" id="' . $this->name . '" name="' . $this->name . '" value ="' . $this->value .
                        '"/><span class="visible"' . $alignText . '>' . $this->value . '</span>' . "\n";
                break;
            case 'P' : // Image. wrap without border and alt text
                if (isSet($this->value)) {
                    $pict = 'fotos/' . $this->value;
                    $result .= '<img src=\'' . $pict . '\' alt=\'' . $this->value . '\' border=\'0\' />';
                } else {
                    $result = '';
                }
                break;
            case 'C': // Creator-owner
            case 'G':
                if (!empty($this->selectQuery)) {
                    $result .= '<select id="' . $this->name . '" name="' . $this->name . '" ' . "$onChange" .
                            ' onkeypress="selectKeyPress();" onkeydown="selectKeyDown();" onblur="clr();" ' .
                            'onfocus="clr();">' . "\n";
                    extract($_SESSION);
                    $q = $this->selectQuery;
                    $q = templateWith($q, get_defined_vars()); //$substitutions)
                    $result .= getOptionListGrouped($this->dbConn, $q, $this->value, 'value', isSet($this->optionPreloadList) ? $this->optionPreloadList : array('name' => '&nbsp;', 'value' => ''));
                    $result .= "\n" . '</select>' . "\n";
                } else {
                    $result .= '<input' . $alignText . $sizeText . $readText . "class=\"{$cssClass}\"" . ' type="text" id="' . $this->name . '" name="' . $this->name .
                            '" value="' . $this->value . '"/>' . "\n";
                }
                break;
            case 'M':
                $onChange = 'onChange="submit();"';
            case 'S':
                if (!empty($this->selectQuery)) {
                    $result .= '<select id="' . $this->name . '" name="' . $this->name . '" ' . "$onChange" .
                            ' onkeypress="selectKeyPress();" onkeydown="selectKeyDown();" onblur="clr();" ' .
                            'onfocus="clr();">' . "\n";
                    extract($_SESSION);
                    $q = $this->selectQuery;
                    //echo "<pre style='color:blue'> {$_SESSION['prjm_id']}:{$q}</pre>";
                    $q = templateWith($q, get_defined_vars()); //$substitutions)
                    //echo "<pre style='color:green'>{$q}</pre>";
                    $result .= getOptionList($this->dbConn, $q, $this->value, isSet($this->optionPreloadList) ? $this->optionPreloadList : array('name' => '&nbsp;', 'value' => ''));
                    $result .= "\n" . '</select>' . "\n";
                } else {
                    $result .= '<input' . $alignText . $sizeText . $readText . "class=\"{$cssClass}\"" . ' type="text" id="' . $this->name . '" name="' . $this->name .
                            '" value="' . $this->value . '"/>' . "\n";
                }
                break;
            case 'D':
                $result = "<!-- datepicker --><input type='text' placeholder='yyyy-mm-dd' style='text-align:left;' size='10' name='" . $this->name . "' id='" . $this->name . "' value='" . $this->value . "'/>\n";
                $this->page->addScriptResource('js/jquery.min.js')
                        ->addScriptResource('js/jquery-ui-custom/jquery-ui.min.js')
                        ->addFileContentsOnce('../templates/simpledatepicker.html')
                        ->addJqueryFragment("\$('#" . $this->name . "').datepicker(dpoptions);");
                break;
            case 't':
                $result = "<input type='time' placeholder='08:45' style='text-align:left;' size='8' name='" . $this->name . "' id='" . $this->name . "' value='" . $this->value . "'/>\n";
                break;
            case 'e':
                $result = "<input type='email' placeholder='" . $this->placeholder . "' style='text-align:left;' size='64' name='" . $this->name . "' id='" . $this->name . "' value='" . $this->value . "'/>\n";
                break;
            case 'd':
                $result = "<input type='number' placeholder='{$this->placeholder}' "
                        . "pattern='\\d+' style='text-align:right;' size='10' "
                        . "name='{$this->name}' id='{$this->name}' value='{$this->value}'/>\n";
                break;
            case 'p': // use peer_id of person logged in, unless it is a tutor.
                if (hasCap(CAP_TUTOR)) {
                    $result = "<input type='number' placeholder='2123456' pattern='\\d{7}' size='7' "
                            . "name='" . $this->name
                            . "' id='" . $this->name
                            . "' value='" . $this->value . "'/>\n";
                } else {
                    $result = $login_snummer;
                }
                break;
// intentional fallthrough
            case 'X': /* data is editable (for search) but discarded on insert */
                $result = $this->value;
                break;
            case 'U': /* mutator is editable (for search) but discared on insert */
            case 'T':
            default:
                $result .= "<input type=\"text\" {$alignText} {$sizeText} {$readText} class=\"{$cssClass}\"  id=\"{$this->name}\""
                        . " name=\"{$this->name}\" value=\"{$this->value}\" placeholder=\"{$this->placeholder}\" />\n";
                break;
            case 'C':
                $result .= '<input type=\'checkbox\' name=\'' . $this->name . '\' value=\'t\' '
                        . ($this->value == 't' ? 'checked' : '') . '/>&nbsp' . "\n";
                break;
            case 'Z' :// to print out data in supp fields
                $result .= '<b>' . $this->value . '</b>';
                break;
        }
        //    var_dump($this); echo "<br>";
        return $result;
    }

    /**
     * @param defs assoc array with definition values
     */
    function setDef($def) {
        // var_dump($def);
        if (isSet($def['column_name'])) {
            $this->setName(trim($def['column_name']));
        }
        if (isSet($def['data_type'])) {
            $this->setData_Type(trim($def['data_type']));
        }
        if (isSet($def['item_length'])) {
            $this->setItem_Length(trim($def['item_length']));
        }
        if (isSet($def['length'])) {
            $this->setItem_Length(trim($def['length']));
        }
        if (isSet($def['data_precision'])) {
            $this->setData_Precision(trim($def['data_precision']));
        }
        if (isSet($def['data_scale'])) {
            $this->setData_Scale(trim($def['data_scale']));
        }
        if (isSet($def['edit_type'])) {
            $this->setEdit_Type(trim($def['edit_type']));
        }
        if (isSet($def['query'])) {
            $this->setSelectQuery(trim($def['query']));
        }
        if (isSet($def['placeholder'])) {
            $this->setPlaceHolder(trim($def['placeholder']));
        }
        if (isSet($def['capability'])) {
            $this->setCapability(trim($def['capability']));
        } // 
        if (isSet($def['nullable'])) {
            $this->setNullable(trim($def['nullable']));
        } // 
    }

    function toString() {
        return "\n\t" . 'menuField ' . $this->name . ' type ' .
                $this->edit_type . ' value ' .
                $this->value . ' length ' .
                $this->item_length . ' prec ' .
                $this->data_precision . ' scale' . "\n\t" .
                $this->data_scale . ' query ' .
                (isSet($this->query) ? $this->query : '') . ' cap ' .
                $this->capability;
    }

    public function __toString() {
        return $this->toString();
    }

    /**
     * @param scalar sets the value of the menu item
     */
    function pickValueFromAssoc($values) {
        $this->setValue(nstripslashes(trim($values[$this->name])));
    }

    public function setNullable($nb) {
        $this->nullable = $nb;
    }

}

/* MenuField */
