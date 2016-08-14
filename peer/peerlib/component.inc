<?php

/**
 * Component and xxcontainer are designed as a composite pattern
 * Component and xxcontainer Both implement the show() method
 * xxcontainer has an add method
 * HtmlContainer derives the endtag from the parameter to the constructor.
 */
$formattingspaces = '';

class Component {

    var $content;

    function __construct($c = '') {
        $this->content = $c;
    }

    function show() {
        global $formattingspaces;
        if ($this->content != '')
            echo $formattingspaces . $this->content . "\n";
    }

}

class Container extends Component {

    var $children = array();
    var $name = '';

    function __construct() {
        
    }

    function add($child) {
        array_push($this->children, $child);
        return $this;
    }

    // assigment copies text.
    function addText($text) {
        $this->add(new Component($text));
        return $this;
    }

    function show() {
        global $formattingspaces;
        echo $formattingspaces;
        Component::show();
        $formattingspaces .='  ';
        for ($i = 0; $i < count($this->children); $i++) {
            $o =$this->children[$i];
            if (is_object($o)) {
            $o->show();
            } else {
                echo $o;
            }
        }
    }

}

class HtmlContainer extends Container {

    var $endTag;

    function __construct($st) {
        $this->setStartTag($st);
    }

    function setStartTag($st) {
        $this->content = $st;
        if ($st != '') {
            $matches = array();
            preg_match('/^\<((\w+)?)(\s|\>)/', $this->content, $matches);
            if (isSet($matches[1])) {
                $this->endTag = '</' . $matches[1] . '>';
            }
            preg_match('/^\<.*?id=\'(\w+)\'.*?>$/', $this->content, $matches);
            if (isSet($matches[1])) {
                $this->name = $matches[1];
            }
        }
        return $this;
    }

    function show() {
        Container::show();
        $this->showEndTag();
    }

    function showStartTag() {
        Component::show();
    }

    function showEndTag() {
        global $formattingspaces;
        if (isSet($this->endTag)) {
            $formattingspaces = substr($formattingspaces, 0, strlen($formattingspaces) - 2);
            echo $formattingspaces . $this->endTag;
            if ($this->name != '') {
                echo '<!--' . $this->name . '-->';
            }
            echo "\n";
        }
    }

}

class PageContainer extends Container {

    var $dtd;
    var $head;
    var $body;
    var $log = '';
    var $jqueryOnLoad;
    var $scriptResources;
    var $fileOnce;

    function __construct($t = 'Peerweb Pagecontainer') {
        global $body_background;
        
        $this->setTitle($t);
        $this->dtd = new Component('<?xml version="1.0" encoding="utf-8" ?>'
                . "\n<!DOCTYPE html>\n<html>\n");
        $this->head = new HtmlContainer("<head>");
        $this->head->add(new Component("  <meta http-equiv=\"Content-Type\" content=\"text/html;charset: utf-8\" />\n" .
                "  <meta http-equiv='Content-Script-Type' content='text/javascript'/>\n" .
                "  <meta http-equiv='Content-Style-Type' content='text/css'/>\n" .
                '  <!-- $Id: component.inc 1769 2014-08-01 10:04:30Z hom $ -->'));
        $this->head->add(new Component("<link rel='stylesheet' type='text/css' href='" . STYLEFILE . "'/>"));
        $this->head->add(new Component("<link rel='icon' href='" . IMAGEROOT . "/favicon.ico' type='image/png' />"));
        $this->head->add(new Component("<link rel='shortcut icon' type='image/png' " .
                "href='" . IMAGEROOT . "/favicon.png'/>"));
        $bodyStyle=$body_background;//$db_name==='peer2')?'background:#cfc url(style/images/local.png)':'background:#ffc';
        $this->body = new HtmlContainer("<body id='body' onload='init' style='{$bodyStyle}'>\n<!-- component build -->");
        $this->log = '';
    }

    function addHeadComponent($hc) {
        $this->head->add($hc);
        return $this;
    }

    function addHeadText($hc) {
        $this->addHeadComponent(new Component($hc));
        return $this;
    }

    /**
     * Add a Jquery fragment for the $. function.
     * @param type $f 
     */
    function addJqueryFragment($f) {
        if (!isSet($this->jqueryOnLoad)) {
            $this->jqueryOnLoad = array();
        }
        $this->jqueryOnLoad[] = $f;
        return $this;
    }

    function addScriptResource($sr) {
        if (!isSet($this->scriptResources)) {
            $this->scriptResources = array();
        }
        if (!in_array($sr, $this->scriptResources)) {
            $this->scriptResources[] = $sr;
        }
        return $this;
    }

    function setBodyTag($bt) {
        $this->body->setStartTag($bt);
        return $this;
    }

    function addBodyComponent($b) {
        $this->body->add($b);
        return $this;
    }

    function addHtmlFragment($frag, $params = array()) {
        global $PHP_SELF;
        global $LOGINDATA;
        ob_start();
        extract($params);
        include $frag;
        $this->body->add(new Component(ob_get_clean()));
        return $this;
    }

    function addHeadFragment($frag, $params = array()) {
        global $PHP_SELF;
        ob_start();
        extract($params);
        include $frag;
        $this->head->add(new Component(ob_get_clean()));
        return $this;
    }

    var $title;

    function setTitle($t) {
        $this->title = $t;
        return $this;
    }

    public function getTitle() {
        return $this->title;
    }

    function addLog($msg) {
        $this->log .= $msg . "<br/>\n";
        return $this;
    }

    function addFileContentsOnce($fc) {
        if (!isSet($this->fileOnce)) {
            $this->fileOnce = array();
        }
        if (!in_array($fc, $this->fileOnce)) {
            $this->fileOnce[] = $fc;
        }
        return $this;
    }

    var $bodyFooter;

    function addBodyFooter($fc) {
        if (!isSet($this->bodyFooter)) {
            $this->bodyFooter = array();
        }
        $this->bodyFooter[] = $fc;
    }

    function show() {
        global $dbConn;
        global $db_name;
        global $system_setting;
        //header('Content-type: application/xhtml+xml; charset: utf-8;');
        header("Content-type: text/html;" . "charset: utf-8;");
        $this->dtd->show();
        $src = '';
        if (isSet($this->scriptResources)) {
            for ($i = 0; $i < count($this->scriptResources); $i++) {
                $src .= "<script type='text/javascript' src='{$this->scriptResources[$i]}' charset='utf-8'></script>\n";
            }
            $this->addHeadText($src);
        }
        if (isSet($this->fileOnce)) {
            $fO = '<!-- add once start -->' . "\n";
            for ($i = 0; $i < count($this->fileOnce); $i++) {
                $file = $this->fileOnce[$i];
                $fO .= file_get_contents($file);
            }
            $fO .= '<!-- add once end -->' . "\n";

            $this->addHeadText($fO);
        }
        if (isSet($this->jqueryOnLoad)) {
            $jq = "<!-- jqueryOnLoad start -->\n\t<script type='text/javascript'>"
                    . "\n\t\t\$(function(){";
            for ($i = 0; $i < count($this->jqueryOnLoad); $i++) {
                $jq .= "\n\t\t\t" . $this->jqueryOnLoad[$i];
            }
            $jq .= "\n\t\t});\n\t</script>\n<!-- jqueryOnLoad start -->\n";
            $this->addHeadText($jq);
        }
        $this->addHeadComponent(new Component('<title>' . $this->title . '</title>'));
        $this->head->show();
        if ($this->log != '') {
            $this->body->add(new Component('<div style=\'width:60%;\'>' . "\n" . $this->log . "\n" . '</div>'));
        }

        if (isSet($system_setting['show_logged_queries']) && ($system_setting['show_logged_queries'] == 'true')) {
            $this->body->add(new Component($dbConn->getLogHtml()));
        }

        if (isSet($this->bodyFooter)) {
            for ($i = 0; $i < count($this->bodyFooter); $i++) {
                $this->body->addText($this->bodyFooter[$i]);
            }
        }

        $this->body->show();
        echo "</html>\n";
    }

}

?>