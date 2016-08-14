<?php
/* $Id: peerutils.inc 1826 2014-12-27 15:01:13Z hom $ */
$PHP_SELF = $_SERVER['PHP_SELF'];
require_once 'peer_capabilities.inc';
define('STARTCOLOR', 0xF0F0F0);
define('COLORINCREMENT_RED', +20);
define('COLORINCREMENT_BLUE', +20);
define('COLORINCREMENT_GREEN', +20);

require_once('rainbow.inc');
/* get user class of login user */
$logon_id = ''; //trim($_SERVER['PHP_AUTH_USER']);
$unix_id = $logon_id;

function myprint($item, $key) {
    echo "$key => $item<br/>\n";
}

require_once('languagemap.inc');

//$callCounter=0;
/**
 * get one (first) record
 * @param $dbConn database connection to use
 * @param $sql query string for this datum
 * @return $resultSet: resultSet to use for any next items.
 */
function getFirstRecordSetFields($dbConn, $sql) {
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        $msg = $dbConn->ErrorMsg();
        echo "Cannot execute select statement \"" . $sql . "\", cause=" . $msg . "\n";
        stacktrace(2);
        exit;
    }
    if ($resultSet->EOF) {
        die("peerutil: cannot get data with query $sql because it is empty");
    } else {
        // copy to arr
    }
    return $resultSet;
}

/**
 * get all data for unix_id from medewerkers_plusplus
 */
function getUserDataInto($dbConn, $unix_id, &$arr) {
    $sql = "select * from medewerkers_plusplus " .
            "where unix_id='$unix_id'";
    $resultSet = getFirstRecordSetFields($dbConn, $sql);
    $arr = array(); //empty
    foreach ($resultSet->fields as $key => $value) {
        $arr[strtoupper($key)] = $value;
    }
#  var_dump($arr);
}

/**
 * test the capabilty the user has
 * @param $cap required capability
 * @return true if user has required cap, false if not
 */
function hasCap($cap) {
    global $_SESSION;
    global $db_name;
    global $dbConn;
    $result = false;
    //allways allow everyone if cap 0 is requested
    if ($cap == 0) {
        $result = true;
    } else {
        // otherwise test as bitmask
        $result = (($_SESSION['userCap'] & $cap) != 0);
    }
    //  if ($db_name!='peer') $dbConn->log( "requested cap=$cap, usercap=".$_SESSION['userCap']." result = $result \n");
    return $result;
}

/**
 * Test the user capability and bail out if not sufficient
 * @param $cap required capability
 */
function requireCap($cap) {
    global $root_url;
    if (!hasCap($cap)) {
        header("location: $root_url/home.php");
        die('');
    }
}

/**
 * Test the user capability and bail out if not sufficient
 * @param $cap required capability
 */
function requireStudentCap($snummer, $cap, $prj_id, $milestone, $grp_num) {
    global $root_url;
    if (!hasStudentCap($snummer, $cap, $prj_id, $milestone, $grp_num)) {
        header("location: $root_url/home.php");
        die('');
    }
}

function hasStudentCap($snummer, $cap, $prjm_id, $grp_num = 0) {
    global $dbConn;
    $sql = "select pr.capabilities from student_role\n " .
            "join prj_milestone using(prjm_id) \n" .
            " join project_roles pr using(prj_id,rolenum)\n" .
            "join prj_tutor using(prjm_id)\n" .
            " join prj_grp using(prjtg_id,snummer)\n" .
            "where snummer=$snummer and prjm_id=$prjm_id";
    // with 0 parameter is a hack
    if ($grp_num != 0) {
        $sql .=" and grp_num=$grp_num";
    }
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === null) {
        $msg = $dbConn->ErrorMsg();
        echo "Cannot execute select statement \"" . $sql . "\", cause=" . $msg . "\n";
        stacktrace(2);
        die();
    }
    //$dbConn->log($sql."\nresult capabilities:[".$resultSet->fields['capabilities']."]\n");
    return ((!$resultSet->EOF) && (($cap & $resultSet->fields['capabilities']) != 0));
}

function hasStudentCap2($snummer, $cap, $prjm_id, $grp_num = 0) {
    global $dbConn;
    // $sql = "select pr.capabilities from student_role join project_roles pr using(prjm_id,rolenum)" .
    //         " join prj_grp using(snummer) join prj_tutor using(prjtg_id) join prj_milestone using(prjm_id)\n" .
    //         "where snummer=$snummer and prjm_id=$prjm_id";
    $sql = "select pr.capabilities from student_role sr join prj_milestone pm on(sr.prjm_id=pm.prjm_id)\n" .
            " join project_roles pr on(pr.prj_id=pm.prj_id and pr.rolenum=sr.rolenum)\n" .
            " join prj_tutor pt on(pt.prjm_id=pm.prjm_id) join prj_grp pg on(pg.snummer=sr.snummer and pg.prjtg_id=pt.prjtg_id)\n" .
            "where sr.snummer=$snummer and pm.prjm_id=$prjm_id";

    // with 0 parameter is a hack
    if ($grp_num != 0) {
        $sql .=" and grp_num=$grp_num";
    }
    $resultSet = $dbConn->doSilent($sql);
    //  $dbConn->log($sql."\nresult capabilities:[".$resultSet->fields['capabilities']."]\n");
    return ((!$resultSet->EOF) && (($cap & $resultSet->fields['capabilities']) != 0));
}

/**
 * optionList creates a list of html options for a select box.
 * @param $query the query that produces the value an name lists in that order.
 * construct this query in such a way that it produces two columns, value and name
 * e.g. select unix_id value,rtrim(achternaam)||' '||rtrim(roepnaam) name from medewerkers;
 * @param $selected_value, the  value in the list that is selected. e.g. hombergp
 * resources: $stmh is used.
 */
function optionList($dbConn, $query, $selected_value, $preload = array()) {
    echo getOptionList($dbConn, $query, $selected_value);
}

function getOptionList($dbConn, $query, $selected_value, $preload = array()) {
    $result = '';
    $resultSet = $dbConn->Execute($query);
    if ($resultSet === false) {
        echo $dbConn->ErrorMsg() . ": cannot execute query <pre style='color:red'>$query</pre>\nat\n";
        stacktrace(1);
        return $result;
    }
    return getOptionListFromResultSet($resultSet, $selected_value, $preload);
}

function getOptionListFromResultSet($resultSet, $selected_value, $preload = array()) {
    $result = '';
    $arr = array();
    // trim $selected_value for comparison
    $selected_value = trim($selected_value);
    for ($i = 0; $i < count($preload); $i++) {
        $result .= "\t\t" . '<option value="' . $preload[$i]['value'] . '">' . $preload[$i]['name'] . '</option>' . "\n";
    }
    $resultSet->MoveFirst();
    while (!$resultSet->EOF) {
        $value = trim($resultSet->fields['value']);
        if (isSet($resultSet->fields['style'])) {
            $style = "style='" . $resultSet->fields['style'] . "'";
        } else {
            $style = '';
        }
        $selected = ($value == $selected_value) ? "selected" : "";
        $result .= "\t\t<option $selected value=\"" . $value . "\" $style>" . htmlspecialchars($resultSet->fields['name']) . "</option>\n";
        $resultSet->moveNext();
    }
    return $result;
}

/**
 * 
 * @param type $dbConn
 * @param type $query
 * @param type $selected_value
 * @param type $selectColumn
 * @param type $preload
 * @return string 
 */
function getOptionListGrouped($dbConn, $query, $selected_value, $selectColumn = 'value', $preload = array()) {
    $result = "\n";
    $resultSet = $dbConn->Execute($query);
    if ($resultSet === false) {
        echo "Error " . $dbConn->ErrorMsg() . ": cannot execute query <pre style='color:#080'>$query</pre>";
        return $result;
    }
    return getOptionListGroupedFromResultSet($resultSet, $selected_value, $selectColumn, $preload);
}

function getOptionListGroupedFromResultSet($resultSet, $selected_value, $selectColumn = 'value', $preload = array()) {
    $result = "\n";
    $grpList = '';
    $oldGrp = '';
    $seperator = '';
    $arr = array();
// trim $selected_value for comparison
    $selected_value = trim($selected_value);
    for ($i = 0; $i < count($preload); $i++) {
        $result .= "\t\t<option value='" . $preload[$i]['value'] . "'>" . $preload[$i]['name'] . "</option>" . "\n";
    }
    //  if (empty($query)) {
    //  return $result; //}
    $resultSet->MoveFirst();
    $grpContinue = '';
    $grpCount = 0;
    while (!$resultSet->EOF) {
        $value = trim($resultSet->fields['value']);
        $disabled = (isSet($resultSet->fields['disabled']) && ($resultSet->fields['disabled'] == 'disabled')) ? ' disabled' : '';
        $selected = '';
        $selected = ($resultSet->fields[$selectColumn] == $selected_value) ? "selected" : "";
        $optionClass = '';
        if (!isSet($resultSet->fields['css_class'])) {
            if ($disabled != '') {
                $optionClass = 'class=\'disabled\'';
            }
        } else {
            $optionClass = 'class=\'' . $resultSet->fields['css_class'] . '\'';
        }
        $name = $resultSet->fields['name'];
        $grp = $resultSet->fields['namegrp'];
        if ($grp != $oldGrp && $oldGrp != '') {
            $grpContinue = "\t\t</optgroup>\n";
            $seperator = "\t\t<optgroup label=\"" . $oldGrp . " (" . $grpCount . ")\">\n";
            $result .= $seperator . $grpList . $grpContinue;
            $grpList = '';
            $grpCount = 1;
        } else {
            $grpCount++;
        }
        $oldGrp = $grp;
        if (isSet($resultSet->fields['title'])) {
            $title = " title='" . $resultSet->fields['title'] . "' ";
        } else {
            $title = '';
        }
        $grpList .= "\t\t\t<option $selected value='$value' $disabled $title $optionClass>" . htmlspecialchars($name) . "</option>\n";
        $resultSet->moveNext();
    }
    $grpContinue = "\t\t</optgroup>\n";
    $seperator = "\n\t\t<optgroup label=\"" . $oldGrp . " (" . $grpCount . ")\">\n";
    $result .= $seperator . $grpList . $grpContinue;
    return $result;
}

/**
 * queryToTable creates a csv file that is presented as application/msexcel
 * @param $query the query send to the db
 * @param $filename the name for the presented file
 */
function queryToTable($dbConn, $query, $numerate = 0, $watchColumn = -1, $rb) {
    queryToTableChecked($dbConn, $query, $numerate = 0, $watchColumn = -1, $rb, -1, '');
}

/**
 * @param $dbConn database connection to use
 * @param $query, sql statement to produce the table
 * @param $numerate boolean if the table should be numbered
 * @param $watchColumn column which is watched for changes. When changed the color of the rows changes
 * @param $rb rainbow; color set producing object interface rainbow
 * @param $checkColumn column that has the checkbox
 * @param $checkName name of the column in the query that proces the check value
 * @param $checkSet set of values in checkcolumn that must have checkbox checked
 * @param $inputColumn array columns that are inputs. In array, type and width is defined
 */
function getQueryToTableChecked($dbConn, $query, $numerate, $watchColumn, $rb, $checkColumn, $checkName, $checkSet = array(), $inputColumns = array(), $summary = 'list of data') {
    global $ADODB_FETCH_MODE;
    $result = "<!-- start queryTableChecked -->\n";
    $ADODB_FETCH_MODE = ADODB_FETCH_NUM;
    $coltypes = array();
    $sums = array();
    $watchVal = '';
    $nr = 0;
    $resultSet = $dbConn->Execute($query);
    if ($resultSet === false) {
        $result .= "<pre>Cannot read table data \nreason \n\t" . $dbConn->ErrorMsg() . " at <br/>\n";
        stacktrace(1);
        $result .= "</pre>";
    }
    $colcount = $resultSet->FieldCount();
    if (!$resultSet->EOF && ($watchColumn >= 0) && ($watchColumn < $colcount))
        $watchVal = $resultSet->fields[$watchColumn];
    //  $rb = new RainBow(0xFF8844,-20,20,40);
    $result .= "<table summary='$summary' id='myTable' class='tablesorter' border='1' style='empty-cells:show; border-collapse:collapse;'>\n";
    $result .= "<thead>\n<tr>\n";
    if ($numerate)
        $result .= "\t\t<th class='tabledata head num'>#</th>\n";
    $columnNames = array();
    for ($i = 0; $i < $colcount; $i++) {
        $field = $resultSet->FetchField($i);
        $columnNames[$i] = $field->name;
        $result .= "\t\t<th class='tabledata head' style='text-algin:left;'>" . niceName($field->name) . "</th>\n";
        $columntypes[$i] = $resultSet->MetaType($i);
        $sums[$i] = 0;
    }
    $result .= "</tr>\n</thead>\n";
    $rowColor = $rb->restart();
    $textstyle = " style='background-color:" . $rowColor . "'";
    while (!$resultSet->EOF) {
        $nr++;
        if ($watchColumn >= 0) {
            if ($resultSet->fields[$watchColumn] != $watchVal) {
                $rowColor = $rb->getNext();
            }
            $watchVal = $resultSet->fields[$watchColumn];
        }
        $textstyle = " style='background-color:" . $rowColor . "'";
        $result .= "\t<tr $textstyle>\n";
        if ($numerate)
            $result .= "\t\t<td class='tabledata num'>" . $nr . "</td>\n";
        for ($i = 0, $max = $resultSet->FieldCount(); $i < $max; $i++) {
            $val = (isSet($resultSet->fields[$i])) ? trim($resultSet->fields[$i]) : '';
            $tdclass = 'tabledata';
            switch ($columntypes[$i]) {
                case 'int2':
                case 'integer':
                case 'numeric':
                case 'float':
                case 'real';
                case 'N':
                    $tdclass .=' num';
                    $sums[$i] +=$val;
                    break;
                default:
                    break;
            }
            if ($checkColumn == $i) {
                $checked = in_array($val, $checkSet) ? ' checked ' : '';
                $val = "<input type='checkbox' name='" . $checkName . "' value='" . $val . "'$checked />";
            }
            if (isSet($inputColumns[$i])) {
                $cName = $columnNames[$i] . '[]';
                $cSize = $inputColumns[$i]['size'];
                $cAlign = ($inputColumns[$i]['type'] == 'N') ? 'right' : 'left';
                $val = "<input type='text' name='$cName' align='$cAlign' size='$cSize' value='$val'/> ";
            }
            $result .= "\t\t<td class='$tdclass'>" . $val . "</td>\n";
        }
        $result .= "\t</tr>\n";
        $resultSet->MoveNext();
    }
    /* $result .=  "\t<tr>\n";
      if ($numerate) $result .=  "\t\t<th class='tabledata head'></th>\n";
      for ($i=0; $i < $colcount; $i++) {
      $val = '';
      $tdclass='tabledata head';
      switch ($columntypes[$i]) {
      case 'I':
      case 'N':
      $val=$sums[$i];
      $tdclass .=' num';
      break;
      default:
      $val = '';
      break;
      }
      $result .=  "\t\t<th class='$tdclass'>".$val."</th>\n";
      }
      $result .=  "\t</tr>\n"; */
    $result .= "</table>\n<!-- end queryTableChecked -->";

    $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
    return $result;
}

/**
 * @param $dbConn database connection to use
 * @param $query, sql statement to produce the table
 * @param $numerate boolean if the table should be numbered
 * @param $watchColumn column which is watched for changes. When changed the color of the rows changes
 * @param $rb rainbow; color set producing object interface rainbow
 * @param $checkColumn column that has the checkbox
 * @param $checkName name of the column in the query that proces the check value
 * @param $checkSet set of values in checkcolumn that must have checkbox checked
 * @param $inputColumn array columns that are inputs. In array, type and width is defined
 */
function queryToTableChecked($dbConn, $query, $numerate, $watchColumn, $rb, $checkColumn, $checkName, $checkSet = array(), $inputColumns = array(), $summary = 'list of data') {
    echo getQueryToTableChecked($dbConn, $query, $numerate, $watchColumn, $rb, $checkColumn, $checkName, $checkSet, $inputColumns, $summary);
}

/* queryToTableChecked */

/**
 * does the parse and execute and error testing.
 * Does not do the fetch. Nor a rollback on errors.
 * @param $dbConn: the connectio to use
 * @param $query select query to present to db
 * @param message buffer for db messages
 * @return resultSet to do the fetches on.
 */
function prepareQuery($dbConn, $query, &$resultString) {
    $resultString = '';
    ob_start();
    $resultSet = $dbConn->Execute($query);
    if ($resultSet === false) {
        echo ("Cannot execute select statement\n\nreason" . $dbConn->ErrorMsg());
        stacktrace(2);
        ob_end_flush();
        exit;
    }
    $resultString .= ob_get_contents();
    ob_end_clean();
    return $resultSet;
}

/**
 * do a Database insert
 * The querry string is executed on the database.
 * any messages are appended to resultString
 * @paran $dbConn database connection to use
 * @param $query qurey string
 * @param $resultString reference to resultString.
 */
function doInsert($dbConn, $query, &$resultString) {
    return doUpdate($dbConn, $query, $resultString);
}

/**
 * do a Database update
 * The querry string is executed on the database.
 * any messages are appended to resultString
 * @paran $dbConn database connection to use
 * @param $query qurey string
 * @param $resultString reference to resultString.
 */
function doUpdate($dbConn, $query, &$resultString) {
    $result = 0;
    $resultString = '';
    @ $resultSet = $dbConn->Execute($query);
    if ($resultSet === false) {
        $msg = $dbConn->ErrorMsg();
        $msg = "Cannot execute select statement $query\n cause $msg\n";
        //      $dbConn->log($msg);
        $resultString = $msg . stacktracestring(2);
        return -1; //exit;
    }
    $result = $dbConn->Affected_Rows();
    //if ($result == 0) {
    $resultString .= $result . ' row' . ($result > 1 ? 's' : '') . ' affected by update';
    //}
    return $result;
}

/**
 * do a Database delete
 *
 */
function doDelete($dbConn, $query, &$resultString) {
    return doUpdate($dbConn, $query, $resultString);
}

/**
 * Gets columnNames for a relation
 * @param $dbConn
 * @param $relation name of the relation
 */
function getColumnNames($dbConn, $relation) {
    return $dbConn->MetaColumnNames($relation);
}

/**
 * getColumnTypes
 */
function getColumnTypes($dbConn, $relname) {
    return $dbConn->MetaColumns($relname);
}

/**
 * replace '_', by ' ' and Capitalize.
 */
function niceName($s) {
    $result = str_replace('_', ' ', $s);
    $result = ucfirst(strtolower($result)); //Capitalize
    return $result;
}

function bvar_dump($var) {
    $result = '';
    ob_start();
    var_dump($var);
    $result.=ob_get_contents();
    ob_end_clean();
    return $result;
}

function naddslashes($s) {
    //  return $s;
    return str_replace('\'', '\'\'', $s);
}

function nstripslashes($s) {
    //  return $s;
    return str_replace('\'\'', '\'', $s);
}

/**
 * get the criteria list for an assessment
 * @param $prj projectname 
 */
function getCriteria($prjm) {
    global $dbConn;
    $criteria = array();

    $sql = "select criterium_id as criterium,nl_short,de_short,en_short,nl,de,en,prjm_id from criteria_pm where prjm_id='$prjm' order by criterium";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die("getCriteria: cannot get data for $sql : " . $dbConn->ErrorMsg() . "<br/>\n");
    } else
        while (!$resultSet->EOF) {
            $criteria[$resultSet->fields['criterium']] = $resultSet->fields;
            $resultSet->moveNext();
        }
    return $criteria;
}

/* getCriterita() */

function criteriaHead($criteria, $lang) {
    criteriaHead2($criteria, $lang, 0xFFFFFF, 0);
}

function criteriaHead2($criteria, $lang, $rainbow) {
    echo criteriaHead2String($criteria, $lang, $rainbow);
}

function criteriaHead2String($criteria, $lang, $rainbow) {
    $startColor = $rainbow->restart();
    $result = '';

    foreach ($criteria as $value) {
        $result .= "\t<th class='navleft' style=\"background:" . $startColor . ";\">" . $value[$lang . '_short'] . "</th>\n";
        $startColor = $rainbow->getNext();
    }
    return $result;
}

function criteriaShortAsArray($criteria, $lang) {
    $result = array();
    foreach ($criteria as $value) {
        switch ($lang) {
            case 'nl': $result[] = $value['nl_short'];
                break;
            case 'de': $result[] = $value['de_short'];
                break;
            default:
            case 'en': $result[] = $value['en_short'];
                break;
        }
    }
    return $result;
}

function criteriaRow($criteria, $judge, $contestant) {
    criteriaRow2($criteria, $judge, $contestant, 0xFFFFFF, 0);
}

function criteriaRow2($criteria, $judge, $contestant, $rainbow) {
    $startColor = $rainbow->restart();
    foreach ($criteria as $value) {
        echo "\t<td align='right' style='background:" . $startColor . ";'>" .
        "<input type='hidden' name='contestant[]' value='$contestant' />" .
        "<input type='hidden' name='criterium[]' value='" . $value['criterium'] . "' />" .
        "<input type='text' align='right' size='2' name='grade[]' value='0' /></td>\n";
        $startColor = $rainbow->getNext();
    }
}

/**
 * default pagehead with title, style and body start, optional script
 */
function pagehead($title, $script = '') {
    echo '<?xml version="1.0" encoding="utf-8" ?>' . "\n" .
    '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"' . "\n" .
    '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . "\n" .
    '<html xmlns="http://www.w3.org/1999/xhtml">';
    echo "<head>
  <meta http-equiv='Content-type' content='text/html; charset: utf-8'/>
  <meta http-equiv='Content-Script-Type' content='text/javascript'/>
  <meta http-equiv='Content-Style-Type' content='text/css'/>
  <meta name='GENERATOR' content='(x)emacs'/>
" . '<!-- $Id: peerutils.inc 1826 2014-12-27 15:01:13Z hom $ -->' . "
  <link rel='stylesheet' type='text/css' href='" . STYLEFILE . "'/>
";
    if ($script != '') {
        ?>
        <script type='text/javascript'>
        <?= $script ?>
        </script>
        <?php
    }
    ?>
    <title><?= $title ?></title>
    </head>
    <?php
    echo "<body class='" . BODY_CLASS . "' >";
}

/* pagehead() */

/**
 * default pagehead with title, style and body start, optional extra headers.
 */
function pagehead2($title, $script = '') {
    echo '<?xml version="1.0" encoding="utf-8" ?>' . "\n" .
    '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"' . "\n" .
    '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . "\n" .
    '<html xmlns="http://www.w3.org/1999/xhtml">';
    echo "<head>
  <meta http-equiv='Content-type' content='text/html; charset: utf-8'/>
  <meta http-equiv='Content-Script-Type' content='text/javascript'/>
  <meta http-equiv='Content-Style-Type' content='text/css'/>
  <meta name='GENERATOR' content='(x)emacs'/>
" . '<!-- $Id: peerutils.inc 1826 2014-12-27 15:01:13Z hom $ -->' . "
  <link rel='stylesheet' type='text/css' href='" . STYLEFILE . "'/>
";
    ?>
    <?php echo $script ?>
    <title><?= $title ?></title>
    </head>
    <?php
    echo "<body class='" . BODY_CLASS . "'>\n";
}

/* pagehead2() */

function pagefoot() {
    ?></body></html><?php
}

/* pagefoot() */

/**
 * authenticate user. side effect : userCap is set to users capabilities
 * @param $uid userid to test
 * @param $pw password to test
 * @return 0 if succes, error code > 0 if failure
 * post: $_SESION contains crypted password, tutor_code and capabilities
 */
function authenticate($uid, $pw) {
    global $dbConn;
    global $db_name;
    global $_SESSION; // tutor_code;  if found, export it to persistent environment
    $_SESSION['userCap'] = 0;
    // db connection must be set
    if (!isSet($dbConn))
        return 1;
    // valid userid is a \d{6,8}
    if (!preg_match("/^\d{4,8}$/", $uid))
        return 2;
    // user must be in user table
    $sql = "select userid as username,rtrim(password) as password,\n" .
            " capabilities,tutor.tutor from passwd left join tutor using (userid) where userid='{$uid}'";
    $resultSet = $dbConn->execute($sql);
    // if no result, fail
    if ($resultSet === false) {
        echo('Error: ' . $dbConn->ErrorMsg() . ' with <pre>' . $sql . "</pre>\n");
        return 3;
    }
    // f user not present fail
    if ($resultSet->EOF)
        return 4;
    extract($resultSet->fields);
    // cryptcompare user entered password and database version
    $cpasswd = crypt($pw, $password);
    if ($cpasswd != $password)
        return 5;
    // update password to bluefish
    if (substr($cpasswd, 0, 4) != '$2y$') {
        $c2pw = password_hash($pw, PASSWORD_BCRYPT);
        $sql = "update passwd set password='{$c2pw}' where userid='{$uid}'";
        $resultSet = $dbConn->execute($sql);
        if ($resultSet === false) {
            echo('Error: generating newly encrypted password');
            return 9;
        }
    }
    // apply capabilities
    $_SESSION['userCap'] = $capabilities;
    if (isSet($tutor))
        $_SESSION['tutor_code'] = $tutor;
    //  if ($db_name!='peer') $dbConn->log( "usercap=".$_SESSION['userCap']."\n");
    $_SESSION['password'] = $cpasswd;
    return 0;
}

/**
 * return 0  if all is well.
 */
function authenticateCrypt($uid, $pw) {
    global $dbConn;
    global $db_name;
    $sql = "select userid as username from passwd where userid='$uid' and password='$pw'";
    $resultSet = $dbConn->execute($sql);
    // if no result, fail
    if ($resultSet === false) {
        echo('Error: ' . $dbConn->ErrorMsg() . ' with <pre>' . $sql . "</pre>\n");
        return 3;
    }
    return ($resultSet->EOF) ? 5 : 0;
}

$authenticatefailure = array(
    '0' => 'Ok',
    '1' => 'no dbConn',
    '2' => 'invalid username format',
    '3' => 'sql query failed',
    '4' => 'username not in table',
    '5' => 'password does not match'
);

function rainbow($color, $increment) {
    return ($color - $increment) & 0xffffff;
}

/**
 * create tablecontents for form or result.
 */
function groupAssessmentTable($dbConn, $sql, $inputs, $criteria, $lang, $rainbow) {
    groupAssessmentTableH($dbConn, $sql, $inputs, true, $criteria, $lang, $rainbow);
}

function groupAssessmentTableH($dbConn, $sql, $inputs, $header, $criteria, $lang, $rainbow) {
    global $langmap;
    $resultSet2 = $dbConn->Execute($sql);
    $oldContestant = 0;
    if ($resultSet2 === false) {
        $dbConn->logError("cannot get resultTable with $sql, reason: " . $dbConn->ErrorMsg());
        return;
    } else if ($resultSet2->EOF) {
        echo "<h1>Sorry, no data yet</h1>\n";
        return;
    } else {
        if ($header) {
            echo "<tr>\n";
            echo "\t<th>" . $langmap['nummer'][$lang] . "</th>\n";
            echo "\t<th>" . $langmap['medestudent'][$lang] . "</th>\n";
            criteriaHead2($criteria, $lang, $rainbow);
            echo "</tr>\n";
        }
        $continuation = '';
        $color = $rainbow->restart();
        while (!$resultSet2->EOF) {
            $contestant = $resultSet2->fields['contestant'];
            if ($oldContestant !== $contestant) {
                echo $continuation;
                echo "<tr>\n";
                echo "\t<td>" . $resultSet2->fields['contestant'] . "</td>\n";
                echo "\t<td>" . $resultSet2->fields['naam'] . "</td>\n";
                $continuation = "</tr>\n";
                $color = $rainbow->restart();
            }
            $oldContestant = $contestant;
            $grade = $resultSet2->fields['grade'];
            $criterium = $resultSet2->fields['criterium'];
            $grp_num = $resultSet2->fields['grp_num'];
            if ($inputs) {
                echo "\t<td align='right' style='background:" . $color . ";'>"
                . "<input type='hidden' name='contestant[]' value='$contestant' />"
                . "<input type='hidden' name='criterium[]' value='" . $criterium . "' />"
                . "<input type='number' class='num' min='1' max='10' size='2' name='grade[]' value='"
                . $grade . "' onChange='validateGrade(this)' /></td>" .
                "<td><textarea rows='2' cols='50' name='remark[]'>$remark</textarea></td>\n";
            } else {
                echo "\t<td align='right' style='background:" . $color . ";'>" .
                $grade . "</td>\n";
            }
            $color = $rainbow->getNext();
            $resultSet2->moveNext();
        }
        echo $continuation;
    }
}

function criteriaList($criteria, $lang, $rb) {
    echo getCriterialist($criteria, $lang, $rb);
}

function getCriterialist($criteria, $lang, $rb) {
    $rainbow = new RainBow();
    $startColor = $rainbow->getCurrent();
    $result = '';
    $critCount = 1;
    foreach ($criteria as $crit) {
        $result .= "\n\t\t<tr style='background:" . $startColor . ";'>\n";
        $result .= "\t\t\t<td style='font-weight:bold;'>$critCount&nbsp;" . $crit[$lang . '_short'] . "</td>\n";
        $result .= "\t\t\t<td>" . $crit[$lang] . "</td>\n";
        $result .= "\t\t</tr>\n";
        $startColor = $rainbow->getNext();
        $critCount++;
    }
    return $result;
}

function individualResultTable($dbConn, $lang, $prjtg_id, $snummer, $gg, $rainbow) {
    echo getIndividualResultTable($dbConn, $lang, $prjtg_id, $snummer, $gg, $rainbow);
}

function getIndividualResultTable($dbConn, $lang, $prjtg_id, $snummer, $gg, $rainbow) {
    $result = '';
    $sql = "select cr.criterium, round(st.grade,2) as grade,round(g.grp_avg,2) as grp_avg,\n" .
            "round(grade/(case when g.grp_avg> 0 then g.grp_avg else 1 end),2) as multiplier ,cr."
            . $lang . "_short," . $lang . " FROM " .
            "stdresult2 st join prj_grp using(prjtg_id,snummer) join prj_tutor using(prjtg_id)\n" .
            " JOIN criteria_v cr USING (prjm_id,criterium) join grp_average2 g \n" .
            " using(prjtg_id,criterium) where prjtg_id=$prjtg_id " .
            "AND snummer=$snummer order by cr.criterium\n";

    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        $result .= ('Error: ' . $dbConn->ErrorMsg() . ' with <pre>' . $sql . "</pre>\n");
        stacktrace(1);
        die();
    }
    if ($resultSet->EOF) {
        $result .= "<h1>Sorry, no data yet</h1>";
        return $result;
    }
    $result .= "<table class='tabledata' border='1' summary='individual results'>";
    $result .= "<tr><th colspan='2'>Nr, Criterion</th>\n" .
            "<th>Grade</th><th>Group</th>\n" .
            "<th>mult</th><th>Description</th></tr>\n";
    $color = $rainbow->getCurrent();
    while (!$resultSet->EOF) {
        $tdStyle = "class='tabledata' style='background:" . $color . ";'";
        $tddStyle = "style='background:" . $color . ";text-align:right;'";
        $result .= "\t<tr>\n" .
                "\t\t<td $tdStyle>" . $resultSet->fields['criterium'] . "</td>\n" .
                "\t\t<td $tdStyle>" . $resultSet->fields[$lang . '_short'] . "</td>\n" .
                "\t\t<td $tddStyle>" . $resultSet->fields['grade'] . "</td>\n" .
                "\t\t<td $tddStyle>" . $resultSet->fields['grp_avg'] . "</td>\n" .
                "\t\t<td $tddStyle>" . $resultSet->fields['multiplier'] . "</td>\n" .
                "\t\t<td $tdStyle>" . $resultSet->fields[$lang] . "</td>\n" .
                "\t</tr>\n";
        $resultSet->moveNext();
        $color = $rainbow->getNext();
    }
    $sql = "select round(s.grade,2) as grade,round(g.grp_avg,2) as grp_avg, \n" .
            "round(s.grade/(case when g.grp_avg> 0 then g.grp_avg else 1 end),2) as multiplier_r,\n" .
            "s.grade/(case when g.grp_avg> 0 then g.grp_avg else 1 end) as multiplier, \n" .
            "round($gg*s.grade/(case when g.grp_avg> 0 then g.grp_avg else 1 end),2) as final_grade " .
            "from stdresult_overall s join grp_overall_average g using (prjtg_id) \n" .
            " where snummer=$snummer and prjtg_id=$prjtg_id";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die('Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql);
    }
    if (!$resultSet->EOF)
        extract($resultSet->fields);
    $final_grade = max(1, $final_grade);
    $final_grade = min(10, $final_grade);
    $result .= "<tr><th colspan='2'>Overall</th><td style='text-align:right;'>$grade</td>\n" .
            "<td style='text-align:right;'>$grp_avg</td><td  style='text-align:right;'>$multiplier_r</td>\n" .
            "<th>An estimate for your overall  grade <i>$multiplier_r*" .
            "<input type='text' name='groupgrade' size='1' align='right' value='$gg' onChange='submit()'/> " .
            "<input type='submit' style='fontsize:16pt' name='recalc' value='='/> $final_grade</i>.</th></tr>\n";
    $result .= "</table>\n";
    return $result;
}

/*
 * csv version of resulttable
 */

function groupResultTableCSV($dbConn, $sql, $header, $critcount) {
    $resultSet2 = $dbConn->Execute($sql);
    $oldContestant = 0;
    $avg = array();
    if ($resultSet2 === false || $resultSet2->EOF) {
        return;
    }
    if ($header) {
        echo "nummer;naam;grp;";
        for ($i = 1; $i <= $cricount; $i++)
            echo "crit" . $i . "_grade;crit" . $i . "mult;";
        echo ";\n";
    }
    while (!$resultSet2->EOF) {
        $contestant = $resultSet2->fields['contestant'];
        if ($oldContestant !== $contestant) {
            echo ";\n";
            echo $resultSet2->fields['contestant'] . ";";
            echo $resultSet2->fields['naam'] . ';';
            echo $resultSet2->fields['grp_num'] . ';';
        }
        $oldContestant = $contestant;
        $grade = $resultSet2->fields['grade'];
        $criterium = $resultSet2->fields['criterium'];
        $grp_num = $resultSet2->fields['grp_num'];
        $grp_avg = $resultSet2->fields['grp_avg'];
        $multiplier = $resultSet2->fields['multiplier'];
        echo $grade . ";";
        $avg[$colCounter] = $grp_avg;
        echo $multiplier . ";";
        $resultSet2->moveNext();
    }
    echo ";\n";
}

/**
 * Test to check if a group is still open for judgements
 * @param $dbConn established database connection
 * @param $judge judge for whom the openness of the group isto be tested
 * @param $prj_id for project and milestone
 * @param $milestone
 * @return true if open, false if close, goup nonexistand or otherwise db error
 * 
 * Group name is irrelevant for this test
 */
function grpOpen($dbConn, $judge, $prj_id, $milestone) {
    $result = 0;
    $sql = "select pg.prj_grp_open  as grp_open, pm.prj_milestone_open as mil_open from prj_grp pg join prj_milestone pm using(prj_id,milestone) where prj_id=$prj_id and\n" .
            " milestone=$milestone and snummer=$judge";
    $resultSet = $dbConn->Execute($sql);
    if (($resultSet != false ) && (!$resultSet->EOF )) {
        $grp_open = $resultSet->fields['grp_open'];
        $mil_open = $resultSet->fields['mil_open'];
        $result = ($resultSet->fields['grp_open'] == 't') && ($resultSet->fields['mil_open'] == 't');
    }
    return $result;
}

/**
 * Test to check if a group is still open for judgements
 * @param $dbConn established database connection
 * @param $judge judge for whom the openness of the group isto be tested
 * @param $prjm_id for project and milestone
 * @return true if open, false if close, goup nonexistand or otherwise db error
 * 
 * Group name is irrelevant for this test
 */
function grpOpen2($dbConn, $judge, $prjtg_id = 0) {
    $result = false;
    $sql = "select (pg.prj_grp_open  and pm.prj_milestone_open)  as all_open \n"
            . "from prj_grp pg natural join prj_tutor natural join prj_milestone pm\n"
            . " where prjtg_id=$prjtg_id and\n"
            . " snummer=$judge";
    $resultSet = $dbConn->Execute($sql);
    if (($resultSet != false ) && (!$resultSet->EOF )) {
        $result = ($resultSet->fields['all_open'] == 't');
    }
    return $result;
}

/**
 * same interface as mail, for testing
 */
function fakemail($to, $sub, $msg, $head) {
    global $dbConn;
    $sql = "select email1 from fake_mail_address limit 1";
    extract($dbConn->Execute($sql)->fields);
    //mail($email1, $sub, $msg, $head);
    echo "<hr/><pre style='color:#004;font-weight:bold;font-family:courier'>FAKE EMAIL\nHEAD:" . htmlentities($head) . "\nRCPT:"
    . htmlentities($to) . "\n\nSubject:{$sub}\nBody:" . htmlentities($msg) . "</pre><hr/>\n";
}

/**
 * create a set of headers to send a html/mime email text
 */
function htmlmailheaders($from, $from_name, $to, $cc = '') {
    $msgid = @`date +%Y%m%d%H%M%S`;
    $msgid = rtrim($msgid);
    $msgid .='.@fontysvenlo.org';
    $mailtimestamp = date('D, j M Y H:i:s O'); // Mon, 5 Nov 2007 11:22:33 +0100
    $headers = "From: $from
Reply-To: $from 
";
    if ($cc != '')
        $headers = "CC:$cc
";
    $headers = "Content-Type: text/html; charset=\"utf-8\"
Received: from hermes.fontys.nl (145.85.2.2) by fontysvenlo.org (85.214.120.66) with PEERWEB mailer for $to; $mailtimestamp
From: $from
Reply-To: $from
MIME-Version: 1.0
Content-Transfer-Encoding: 8bit
Return-Path: $from
Message-Id: " . '<' . "$msgid" . '>' . "
";
    return $headers;
}

/**
 * same interface as mail, to send only on live database
 */
function domail($to, $sub, $msg, $head) {
    global $db_name;
    global $dbConn;
    if ($db_name == 'peer')
        mail($to, $sub, $msg, $head);
    else {
        $dbConn->log("<strong>fake mail because database is $db_name.</strong><br/>");
        fakemail($to, $sub, $msg, $head);
    }
}

/**
 * same interface as mail, to send only on live dataabase
 */
function dopeermail($to, $sub, $msg, $head, $altto = ADMIN_EMAILADDRESS) {
    global $db_name;
    global $dbConn;
    if ($db_name == 'peer')
        mail($to, $sub, $msg, $head);
    else {
        domail($to, $sub, $msg, $head);
    }
}

/**
 * returns tutor owner and project data
 * @param $dbConn database connection
 * @param $prj_id project id
 * @return resultset.
 * if prj_id < 0 then the max prject in the set is taken
 */
function getTutorOwnerData($dbConn, $prj_id) {
    $sql = "select * from project join tutor on(owner_id=userid) join student on(userid=snummer)\n";
    if ($prj_id >= 0) {
        $sql .= " where prj_id=$prj_id";
    } else {
        $sql .= " where prj_id=(select max(prj_id) as prj_id from project)";
    }
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        echo( "<br>Cannot get Tutor owner data with with $sql, cause " . $dbConn->ErrorMsg() . "<br>");
        stacktrace(1);
        die();
    }
    if ($resultSet->EOF) {
        return array();
    } else {
        return $resultSet->fields;
    }
}

/**
 * returns tutor owner and project data
 * @param $dbConn database connection
 * @param $prj_id project id
 * @return resultset.
 * if prj_id < 0 then the max prject in the set is taken
 */
function getTutorOwnerData2($dbConn, $prjm_id) {
    $sql = "select * from prj_milestone pm natural join project p join tutor t on(p.owner_id=t.userid) join student s on(t.userid=s.snummer)\n";
    if ($prjm_id >= 0) {
        $sql .= " where prjm_id=$prjm_id";
    } else {
        $sql .= " where prjm_id=(select max(prjm_id) as prjm_id from prj_milestone)";
    }
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        echo( "<br>Cannot get Tutor owner data with with $sql, cause " . $dbConn->ErrorMsg() . "<br>");
        stacktrace(1);
        die();
    }
    if ($resultSet->EOF) {
        return array();
    } else {
        return $resultSet->fields;
    }
}

/**
 * get the next value of a named sequence
 */
function sequenceNextValue($dbC, $seqnam) {
    $sql = " select nextval('$seqnam')";
    $resultSet = $dbC->Execute($sql);
    if ($resultSet === false) {
        echo( "<br>Cannot get sequence value with $sql, cause " . $dbC->ErrorMsg() . "<br>");
        stacktrace(1);
        die();
    } else
        return $resultSet->fields['nextval'];
}

/**
 * @param $dbConn
 * @param prj_id
 * @param milestone
 * @param tutor_code
 * @return true if tutor_owner or has syscap
 */
function checkTutorOwner($dbConn, $prj_id, $tutor_id) {
    if (hasCap(CAP_SYSTEM))
        return true;
    $sql = "select owner_id from project where prj_id=$prj_id and owner_id=$tutor_id";
    $resultSet = $dbConn->Execute($sql);
    return (!$resultSet->EOF);
}

function checkTutorOwnerMilestone($dbConn, $prjm_id, $tutor_id) {
    if (hasCap(CAP_SYSTEM))
        return true;
    $sql = "select count(1) from project natural join prj_milestone where prjm_id=$prjm_id and owner_id=$tutor_id";
    $resultSet = $dbConn->Execute($sql);
    return (!$resultSet->EOF);
}

function checkGroupTutor($dbConn, $prjtg_id, $tutor_id) {
    $sql = "select count(1) as tutor_count from prj_tutor where prjtg_id={$prjtg_id} and tutor_id={$tutor_id}";
    $resultSet = $dbConn->Execute($sql);
    return ($resultSet->fields['tutor_count'] == 1);
}

/**
 * Ensure input string is usable in a (unix) file system as a valid filename.
 * A filename is the last part of a file path and is what is returned by basename(1)
 * In particular, '/', '<' '>' are replaced.
 * @param type $fn 
 * @return sanitised filename.
 */
function sanitizeFilename($fn) {
    return preg_replace('/([\]()|\/;\'[]|\s)+/', '_', trim($fn));
}

/**
 * @param $dbConn: database connection
 * @param $recipient: entity to find email address for
 * @param $isTutor: distincts between students and tutors, in different tables
 * @return emails address(es), comma separated if found or empty string and message to output
 */
function getEmailAddress($dbConn, $recipient, $istutor) {
    $result = '';
    if ($istutor) {
        $sql = "select email1,email2 from tutor join student on(userid=snummer)\n"
                . " left join alt_email using(snummer) where tutor='$recipient'";
    } else {
        $sql = "select email1,email2 from student left join alt_email \n"
                . "using(snummer) where student.snummer=$recipient";
    }
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        echo ("getCriteria: cannot get data for $sql : " . $dbConn->ErrorMsg() . "\n");
    } else if (!$resultSet->EOF) {
        extract($resultSet->fields);
        $result = trim($email1);
        if (isSet($email2))
            $result .= ', ' . trim($email2);
    }
    return $result;
}

/**
 * @param $dbConn: database connection
 * @param $recipient array of snummers entity to find email address for
 * @return emails address(es), comma separated if found or empty string and message to output
 */
function getEmailAddresses($dbConn, $recipients) {
    $result = '';
    $con = '';
    $recps = '\'' . implode("','", $recipients) . '\'';
    $sql = "select distinct roepnaam||' '||coalesce(voorvoegsel||' ','')||achternaam||' <'||trim(email1)||'>' as email from student where snummer in ($recps)";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        echo ("getEmailAddresses: cannot get data for $sql : " . $dbConn->ErrorMsg() . "\n");
    } else
        while (!$resultSet->EOF) {
            $result .= $con . $resultSet->fields['email'];
            $con = ',';
            $resultSet->moveNext();
        }
    return $result;
}

function isProjectScribe($prj_id, $snummer) {
    global $dbConn;
    $sql = "select count(*) as is_scribe from all_project_scribe where prj_id=$prj_id and scribe=$snummer";
    $resultSet = $dbConn->Execute($sql);
    return $resultSet->fields['is_scribe'];
}

/**
 * Test the user capability and bail out if not sufficient
 * @param $cap required capability
 */
function requireScribeCap($snummer) {
    global $root_url;
    global $dbConn;
    $sql = "select count(*) as is_scribe from all_project_scribe where scribe=$snummer";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet->fields['is_scribe'] == 0) {
        header("location: $root_url/home.php");
        die('');
    }
}

// read settings

$sql = "select key,value from peer_settings";
$resultSet = $dbConn->Execute($sql);
$system_settings = array();
$set_log_unknown_names = 0;
$set_log_validation_failures = 0;
if ($resultSet === false) {
    echo( "<br>Cannot get settings values with $sql, cause " . $dbConn->ErrorMsg() . "<br>");
    stacktrace(1);
    die();
}

while (!$resultSet->EOF) {
    extract($resultSet->fields);
    $system_settings[$key] = $value;
    $resultSet->moveNext();
}

// test the input against malicious values
include_once 'validationmap.php';
include_once 'requestvalidator.php';
$validator_clearance = true;
$validator = new RequestValidator($validationmap);
$validator->set_log_unknown_names(('true' == $set_log_unknown_names) ? 1 : 0 );
$validator->set_log_validation_failures(('true' == $set_log_validation_failures) ? 1 : 0 );

$VPOST = array();
$validator->clean('_POST', $_POST, $VPOST);
// $_POST_RAW = $_POST;
// $_POST = $VPOST;

$VGET = array();
$validator->clean('_GET', $_GET, $VGET);
// $_GET_RAW = $_GET;
// $_GET = $VGET;
// turn off to prevent duplicates
$validator->set_log_unknown_names(false);
$validator->set_log_validation_failures(false);
$VREQUEST = array();
$validator->clean('_REQUEST', $_REQUEST, $VREQUEST);
// $_REQUEST_RAW = $_REQUEST;
// $_REQUEST = $VREQUEST;

$validator_clearance = ($validator->getErrorCount() == 0);
//$dbConn->log("Validator clearance ".($validator_clearance?'YES':'NO') );
$_VALIDATORMAP = $validator->getValidMap();
// end tests

$tutor_navtable = array();
$tabInterestCount = 0;
// set some default values.
$prj_id = 1;
$prjtg_id = 1;
$prjm_id = 0;

/* EOF */
