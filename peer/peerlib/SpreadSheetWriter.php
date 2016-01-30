<?php

/*
 * Fontys venlo peerweb.
 */

/**
 * Prduces a widget and the processing code to output a table from a query as one on three 
 * output formats.
 * Output formats supported are Excel2007, Excel5 and CSV.
 *
 * @author hom
 */
class SpreadSheetWriter {

    private $dbConn;
    private $query;
    private $url = 'http://www.fontysvenlo.org';
    private $linkText = 'Peerweb';
    private $name = 'spreadsheetwidget';
    private $weights = null;
    private $firstWeightsColumn = -1;
    private $weightSumsColumn = -1;

    public function getQuery() {
        return $this->query;
    }

    public function setName($n) {
        $this->name = $n;
        return $this;
    }

    public function setQuery($query) {
        $this->query = $query;
    }

    public function getUrl() {
        return $this->url;
    }

    public function setUrl($url) {
        $this->url = $url;
    }

    public function setWeights($weights) {
        $this->weights = $weights;
        return $this;
    }

    public function setFirstWeightsColumn($firstWeightsColumn) {
        $this->firstWeightsColumn = $firstWeightsColumn;
        return $this;
    }

    public function setWeightSumsColumn($weightSumsColumn) {
        $this->weightSumsColumn = $weightSumsColumn;
        return $this;
    }

    private $title = 'Set a title';
    private $filename = 'set_a_filename';
    private $rowparser = null;
    private $colorChangerColumn = -1;
    private $autoZebra = false;
    private $startRow = null;

    function getStartRow() {
        return $this->startRow;
    }

    function setStartRow($startRow) {
        $this->startRow = $startRow;
    }

    public function __construct($dbC, $query) {
        $this->dbConn = $dbC;
        $this->query = $query;
        $this->setLinkText("spread sheet produced by Fontys Venlo peerweb service on "
                . date(DATE_ATOM));
    }

    public function setLinkUrl($u) {
        $this->url = $u;
        return $this;
    }

    public function setTitle($t) {
        $this->title = $t;
        return $this;
    }

    public function setLinkText($t) {
        $this->linkText = $t;
        return $this;
    }

    public function setFilename($f) {
        $this->filename = $f;
        return $this;
    }

    public function setAutoZebra($az) {
        $this->autoZebra = $az;
        return $this;
    }

    public function processRequest() {
        global $_SESSION;
        global $_REQUEST;

        $excelFormat = 'Excel2007';
        $fileExtension = 'xlsx';

        if (isSet($_REQUEST[$this->name]) && isSet($_REQUEST['filetype'])) {
            switch ($_REQUEST['filetype']) {
                case 'Excel2007':
                    $fileExtension = 'xlsx';
                    $excelFormat = 'Excel2007';
                    break;
                case 'Excel2003':
                    $fileExtension = 'xls';
                    $excelFormat = 'Excel5';
                    break;
                default:
                case 'csv':
                    $fileExtension = 'csv';
                    $excelFormat = 'CSV';
                    // all work here
                    $this->writeCSVFile($this->query, $this->filename . '.' . $fileExtension);
                    break;
                case 'AMC-exam':
                    $fileExtension = 'csv';
                    $excelFormat = 'CSV';
                    // all work here
                    $this->writeAMCFile($this->query, $this->filename . '.' . $fileExtension);
                    break;
                case 'PROGRESS-set':
                    $fileExtension = 'txt';
                    $excelFormat = 'CSV';
                    // all work here
                    $this->writeProgresFile($this->query, $this->filename . '.' . $fileExtension);
                    break;
            }
            require_once 'queryToXlsx.php';
            $student_encoding_relation = ' student_email s ';

            $xlsWriter = new XLSWriter($this->dbConn, $this->query);
            $xlsWriter->setFilename($this->filename . '.' . $fileExtension)
                    ->setTitle($this->title)
                    ->setLinkText($this->linkText)
                    ->setLinkUrl($this->url)
                    ->setAutoZebra($this->autoZebra)
                    ->setWeights($this->weights)
                    ->setWeightedSumsColumn($this->weightSumsColumn)
                    ->setFirstWeightColumn($this->firstWeightsColumn)
                    ->setExcelFormat($excelFormat);
            if (isSet($this->rowparser)) {
                $xlsWriter->setRowParser($this->rowparser);
            }
            if (isSet($this->rainBow)) {
                $xlsWriter->setRainBow($this->rainBow);
            }
            if ($this->colorChangerColumn >= 0) {
                $xlsWriter->setColorChangerColumn($this->colorChangerColumn);
            }
            $xlsWriter->writeXlsx($this->query);
            exit(0);
        }
    }

    public function getWidget() {
        $widget = "<span name='{$this->name}' style='border:1px dashed black;padding: 8px;border-radius:8px;'>
            Spread sheet&nbsp;output:
            <select name='filetype'>
                <option value='Excel2007'>Excel2007</option>
                <option value='Excel2003'>Excel2003</option>
                <option value='csv'>semicolon separated</option>
                <option value='AMC-exam'> comma ',' seperated</option>
                <option value='PROGRESS-set'>Progress result set</option>
                <!--option value='semicol'>semi colon text(progress)</option-->
            </select><button type='submit' name='{$this->name}' value='{$this->name}'>Get as file</button>
            &nbsp; only works if you have a valid list.</span>\n";
        return $widget;
    }

    public function getForm($action) {
        return "\n<form id='spreadsheet' method='get' action='$action'>\n"
                . $this->getWidget()
                . "</form>\n";
    }

    function setRowParser($p) {
        $this->rowparser = $p;
        return $this;
    }

    function setRainBow($r) {
        $this->rainBow = $r;
        return $this;
    }

    public function setColorChangerColumn($c) {
        $this->colorChangerColumn = $c;
        return $this;
    }

    function writeAMCFile($query, $filename) {

        $this->dbConn->queryToCSV($query, $filename, ',', false, 'Content-type: text/csv; charset: UTF-8;', false);
        exit(0);
    }

    function writeProgresFile($query, $filename) {

        $this->dbConn->queryToCSV($query, $filename, ';', false, 'Content-type: text/x-comma-separated-values; charset: UTF-8;', false);
        exit(0);
    }

    function writeCSVFile($query, $filename) {

        $this->dbConn->queryToCSV($query, $filename, ';', true, 'Content-type: text/x-comma-separated-values; charset: UTF-8;', true);
        exit(0);
    }

}

?>
