<?php

require_once'./peerlib/rainbow.inc';

/**
 * SimpleTableFormatter formats simple tables with optional table definitions and
 * checkboxes.
 * If a column of checkboxes is produced, the formatter will add a checkbox in the head to check all boxes at once.
 *
 * @author hom
 */
class SimpleTableFormatter {

    private $dbConn;
    private $page;
    private $query;
    private $checkName = 'snummer[]';
    private $checkColumn = -1;
    private $tabledef = "<table summary='simple table'>";
    private $rainbow;
    private $colorChangerColumn = -1;

    /**
     * Set the column that alters the row colour.
     * @param type $colorChangerColumn column number (0 based ) of column to check for value changes.
     * @return \SimpleTableFormatter 
     */
    public function setColorChangerColumn($colorChangerColumn) {
        $this->colorChangerColumn = $colorChangerColumn;
        return $this;
    }

    public function setTabledef($tabledef) {
        $this->tabledef = $tabledef;
        return $this;
    }

    /**
     * Get the name of the checkbox name for the check javascript.
     * @return type string
     */
    public function getCheckName() {
        return $this->checkName;
    }

    /**
     * Set the name of the checkbox name for the check javascript.
     * @param the checkbox name .
     */
    public function setCheckName($checkName) {
        $this->checkName = $checkName;
        return $this;
    }

    public function getCheckColumn() {
        return $this->checkColumn;
    }

    public function setCheckColumn($checkColumn) {
        $this->checkColumn = $checkColumn;
        return $this;
    }

    /**
     * Construct a formatter.
     * @param type $dbConn database connectio to use.
     * @param type $query to reterive table data.
     * @param type $page the page to attach scripts to.
     */
    public function __construct($dbConn, $query, $page = null) {
        $this->dbConn = $dbConn;
        $this->page = $page;
        $this->query = $query;
        $this->rainbow = new RainBow();
    }

    /**
     * Retreive the composed table.
     * If as checkcolumn is present (checkbox >= 0) then a onload script will be loaded to the page header.
     */
    public function getTable() {
        global $ADODB_FETCH_MODE;
        $result = '';
        $rowCount = 1;
        $ADODB_FETCH_MODE = ADODB_FETCH_NUM;
        $coltypes = array();
        $columnNames = array();
        $resultSet = $this->dbConn->Execute($this->query);
        if ($resultSet === false) {
            $result .= "<pre style='color:800'>Cannot read table data with \n\t"
                    . $this->query . " </pre>\n\treason \n\t"
                    . $this->dbConn->ErrorMsg() . " at\n";
            stacktrace(1);
            $result .= "</pre>";
            return $result;
        }

        $colcount = $resultSet->FieldCount();
        $result .= $this->tabledef . "\n";
        $result .= "<thead>\n";
        if ($this->checkColumn >= 0 && $this->page != null) {
            $this->page->addHeadText(' 
       <script type="text/javascript">
          function checkThem(ref,state){
            var checks = document.getElementsByName(ref);
            var boxLength = checks.length;
            for ( i=0; i < boxLength; i++ ) {
              checks[i].checked = state;
            }
      }</script>
      ');
            $checkRow = "<tr style='background:rgba(255,128,0,0.4)'>";
            if ($this->checkColumn > 0) {
                $checkRow .="<td colspan='" . $this->checkColumn . "'>";
            }
            $checkBox = "<input name='checkAll' type='checkbox' onclick='javascript:checkThem(\""
                    . $this->checkName
                    . "\",this.checked)'/>&nbsp;(un)Check all";
            $checkRow .="<td>&nbsp;</td><td colspan='" . ($colcount - $this->checkColumn) . "'style='font-weight:bold;border:none'>$checkBox</td>";
            $checkRow .="</tr>\n";
            $result .= $checkRow;
        }
        $result .="<th>#</th>";
        for ($i = 0; $i < $colcount; $i++) {
            $field = $resultSet->FetchField($i);
            $columnNames[$i] = $field->name;
            $result .= "\t\t<th class='tabledata head' style='text-algin:left;'>" . niceName($field->name) . "</th>\n";
            $columntypes[$i] = $resultSet->MetaType($i);
        }
        $result .= "</tr>\n</thead>\n<tbody>\n";
        $oldValue = '';
        $rowColor = $this->rainbow->restart();
        if (!$resultSet->EOF) {
            if ($this->colorChangerColumn >= 0 && isSet($resultSet->fields[$this->colorChangerColumn])) {
                $oldValue = $resultSet->fields[$this->colorChangerColumn];
            }
        }
        while (!$resultSet->EOF) {
            if ($this->colorChangerColumn >= 0 && isSet($resultSet->fields[$this->colorChangerColumn]) && $oldValue != $resultSet->fields[$this->colorChangerColumn]) {
                $rowColor = $this->rainbow->getNext();

                $oldValue = $resultSet->fields[$this->colorChangerColumn];
            }
            $result .= "\t<tr style='background:$rowColor'>\n"
                    . "<td align='right'>" . ($rowCount++) . "</td>";
            for ($i = 0, $max = $resultSet->FieldCount(); $i < $max; $i++) {

                $val = (isSet($resultSet->fields[$i])) ? trim($resultSet->fields[$i]) : '';
                if (substr($val, 0, 1) != '<') {
                    $val = $val;
                }
                if ((substr($val, 0, 1) == '{') && (substr($val, -1) == '}')) {
                    $val = substr($val, 1, strlen($val) - 2);
                    $val = substr($val, 0, strlen($val) - 2);
                    $a = explode(',', $val);
                    $val = '<td>' . implode('</td><td>', $a) . '</td>';
                }
                $tdclass = 'tabledata';
                switch ($columntypes[$i]) {
                    case 'int2':
                    case 'integer':
                    case 'numeric':
                    case 'float':
                    case 'real';
                    case 'N':
                        $tdclass .=' num';
                        break;
                    default:
                        break;
                }
                $result .= "\t\t<td class='$tdclass'>" . $val . "</td>\n";
            }
            $result .= "\t</tr>\n";
            $resultSet->MoveNext();
        }
        $result .= "</tbody>\n</table>\n";
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
        return $result;
    }

    /**
     * Print teh composed table.
     */
    public function printTable() {
        echo $this->getTable();
    }

    public function __toString() {
        return $this->getTable();
    }

}

?>
