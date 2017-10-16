<?php

require_once 'component.php';

class ConfFileEditor {

    private $filenameField = 'filename';
    private $rows = 60;
    private $acedir = '/ace-builds/src-min-noconflict';
    private $editorName = 'confeditor';
    private $title = 'Edit a file';
    private $template = '';
    private $description = '<p>The file access rights or authorization file is stored in the root repos at svnroot/conf/authz. Here you can edit it using a html form.</p>';
    private $testStyle = '';
    private $client;

    function __construct($client, $template = 'templates/confeditor.html') {
        $this->client = $client;
        $this->template = $template;
    }

    /**
     *  Output the file editor to the browser.
     */
    function getWidgetForPage($page, $pp = array()) {
        global $PHP_SELF;
        global $_SESSION;

        $pp['textName'] = $this->textName;
        $pp['editorName'] = $this->editorName;
        $pp['filename'] = $_SESSION['fileToEdit'];
        $mode = 'sh';
        $extension = end(explode('.', $pp['filename']));
        switch ($extension) {
            case 'sql': $mode = 'pgsql';
                break;
            case 'php': $mode = 'php';
                break;
            case 'css': $mode = 'css';
                break;
            case 'sty':
            case 'tex':
            case 'lco':
                $mode = 'latex';
                break;
            default: break;
        }
        $pp['filenameField'] = $this->filenameField;
        $_SESSION['fileToSave'] = $pp['fileToEdit'] = $_SESSION['conf_editor_basedir'] . '/' . $_SESSION['fileToEdit'];
        $pp['description'] = $this->description;
        $pp['client'] = $this->client;
        $page->addScriptResource('js/jquery.min.js');
        $page->addBodyFooter("<script src='ace-builds/src-noconflict/ace.js' type='text/javascript' charset='utf-8'></script>\n"
                . "<script type='text/javascript'>"
                . "   var editor = ace.edit('confeditor');\n"
                . "   editor.setTheme('ace/theme/chrome');\n"
                . "   editor.getSession().setMode('ace/mode/{$mode}');\n"
                . "    $('#confeditor_content').hide();\n"
                . "    $('#confeditform').submit(function(){\n"
                . "        $('#confeditor_content').val(editor.getSession().getValue());\n"
                . "    });\n"
                . "</script>\n"
        );
        $page->addHeadText("<style type='text/css'>\n"
                . "div#$this->editorName {position:relative;width:800px;height:800px;background:#fff;}\n</style>");
        $page->addHtmlFragment($this->template, $pp);
//    $page->addLog( "New page" . $_SESSION['fileToEdit'] );
    }

    /**
     * Save the POST fileeditorcontent.
     */
    public static function save() {
        global $dbConn;
        global $_SESSION;
        global $_POST;
        $result = '';
        if (isSet($_SESSION['fileToSave']) && isSet($_POST['confeditor_content']) && isSet($_POST['save_confeditform'])) {
            $fp = fopen($_SESSION['fileToSave'], 'w+');
            if ($fp) {
                if (fwrite($fp, $_POST['confeditor_content']) === FALSE) {
                    $result = "cannot write ${_SESSION['fileToSave']}";
                } else {
                    $result = "<fieldset><legend>Commit result</legend>\n<pre style='color:#008;font-weight:bold;'>\n";
                    $result .= "saved file ${_SESSION['fileToSave']}\n";
                    fclose($fp);
                    if ($_SESSION['mustCommit']) {
                        $cmdstring = "/usr/bin/svn ci -m'wwwrun update of file' ${_SESSION['fileToSave']}";
                        ob_start();
                        passthru($cmdstring);
                        $result .= ob_get_clean();
                    };
                    $result .= "\n</pre></fieldset>";
                }
            }
            unset($_SESSION['mustCommit']);
            unset($_SESSION['fileToSave']);
        }
        return $result;
    }

    /**
     * Set title.
     */
    function setTitle($t) {
        $this->title = $t

        ;
    }

    /**
     * Set description.
     */
    function setDescription($d) {
        $this->description = $d;
        return $this;
    }

    /**
     * Set commit flag
     */
    function setMustCommit($b) {
        $this->mustCommit = $b;
        return $this;
    }

    function setRows($r) {
        $this->rows = $r;
        return $this;
    }

    function setCols($c) {
        $this->colss = $c;
        return $this;
    }

    function setTextName($n) {
        $this->textName = $n;
        return $this;
    }

    function setTextStyle($s) {
        $this->textStyle = $s;
        return $this;
    }

    function setClient($c) {
        $this->client = $c;
        return $this;
    }

}

?>