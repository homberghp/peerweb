<?php
// <table> less version.
require_once('peerutils.php');
require_once('component.php');
include_once'tasktimer.php';

/**
 * Functionality reduced navigation class.
 * most of the functionality moved to peertree.php
 */
class Navigation extends HtmlContainer {

    var $skin;
    var $hasCloseButton = true;
    var $pageOpening;
    var $page;
    var $logoutlink;
    var $main;
    var $leftNavText;
    var $interestMap = false;
    var $extra_opening_line = '';
    var $navtable = array();

    function __construct($navtable, $page, $pageOpening) {
        global $LOGINDATA;
        global $root_url;
        $this->pageOpening = $pageOpening;
        $this->page = $page;
        $this->logoutlink = $root_url . '/logout.php';
        $this->main = new HtmlContainer("<div id='navmain' class='navmain'>");
        $this->leftNavText = array();
        $this->extra_opening_line = "<div class='nav' style='margin:-2em 0 0em 1em'><br/>" .
                " {$LOGINDATA['roepnaam']} {$LOGINDATA['voorvoegsel']} {$LOGINDATA['achternaam']} " .
                "(userid {$_SESSION['peer_id']}) " .
                "On since {$_SESSION['newest_login_record']['since']} from " .
                "{$_SESSION['newest_login_record']['from_ip']}. Last on {$_SESSION['last_login_record']['since']} from " .
                "{$_SESSION['last_login_record']['from_ip']}</div>";
    }

//    /**
//     * Add a component to the navigation 'body'.
//     * @param type $c the component to add.
//     * @deprecated do not use nav as container. Nav will get a reduced role in the future.
//     */
//    function addBodyComponent($c) {
//        $this->main->add($c);
//    }

    /**
     * No longer in use.
     * @param type $c 
     * @deprecated do not use.
     */
    function addLeftNavText($c) {
        //array_push( $this->leftNavText ,$c);
    }

    /**
     * @param type $map 
     * @deprecated do not use.
     */
    function setInterestMap($map) {
        $this->interestMap = $map;
    }

    function show() {
        global $PHP_SELF;
        global $_SESSION;
        global $_HTTP_USER_AGENT;
        //	$this->prune_navigation_table();
        $tdClass = 'navleft';
        $help_context=preg_replace('/\/\w+?\/(\w+)?\.php$/','$1',$PHP_SELF);
        // ruler
        echo "<div id='navopening' class='navopening'>\n";
        //	    taskTimerText($_SESSION['peer_id']).
        ?><div style='position:relative;top:0px;left:0px;right:0px'>
            <h1 align='left'><a class='logout' href='index.php' target='_top' title='goto peerweb home page'>
                    <img src='images/home.png' border='0' alt='home'/></a>&nbsp;&nbsp;
                <a class='print' href='<?=$this->page?>' target='_blank' title='print this page'>
                    <img src='images/printer.png' border='0' alt='print'/></a>&nbsp&nbsp;&nbsp&nbsp;<?= $this->pageOpening ?></h1></div>

        <!--span style='align:right'>&nbsp;
            <a href='helpme.php?page=<?= $help_context ?>' target='_blank'>
                <img src='images/help_button2.png' border='0' alt='DIY HELP' align='middle'/></a>
        </span-->
        <div style='position:relative;top:14px;right:5px;text-align:right;'>
            logoff<a class='logout' href='<?= $this->logoutlink ?>' title='logout' target='_top'>
                <img src='<?= IMAGEROOT ?>/close_1.png' border='0' alt='logout' align='middle'/>
            </a></div><?php
        $this->extra_opening_line;
        echo "</div><!--end id=navopening-->\n";
        // display main window
        if (count($this->main->children) > 0) {
            $this->main->show();
        }
    }

    function setLogoutLink($l) {
        $this->logoutlink = $l;
    }

    /**
     * prune the unwanted nav items from navigation.
     * @param $navarray three dim array to prune. array contains hashkey called interest. This yeilds a key into the interest map. 
     * @param $interestmap map which determines what is interesting. Non null leaves nav link in tact.
     *
     * @return pruned array
     */
    function prune_navigation_table() {
        if ($this->interestMap === false)
            return;
        $result = array();
        for ($i = 0; $i < count($this->navtable); $i++) {
            $subset = array();
            for ($j = 0; $j < count($this->navtable[$i]); $j++) {
                if ($this->interestMap[$this->navtable[$i][$j]['interest']] > 0) {
                    array_push($subset, $this->navtable[$i][$j]);
                }
            }
            if (count($subset) > 0) {
                array_push($result, $subset);
            }
        }
        $this->navtable = $result;
    }

    public function __toString() {
        return 'Navigation for' . $this->pageOpening;
    }

}

// class Navigation;
?>
