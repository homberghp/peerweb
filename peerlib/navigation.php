<?php
require_once('peerutils.php');

class Navigation {
    var $navtable;
    var $skin;
    var $hasCloseButton=true;
    var $pageOpening;
    var $page;
    var $helpPage='help.php';
    var $helpIcon='help.png';
    var $navcolspacer='navcolspacer.png';
    var $logoutlink='../logout.php';
    function __construct( $navtable,$page,$pageOpening ) {
	global $root_url;
	$this->navtable = $navtable;
	$this->pageOpening= $pageOpening;
	$this->page=$page;
	$this->logoutlink=$root_url.'/logout.php';
    }
    function setTable( $t ) {
	$this->navtable=$t;
    }
    function show( ){
	reset ($this->navtable);
	$row=-1;
	$col=-1;
	// search $this->page to get row and column index
	for ($i=0; $i < count( $this->navtable ); $i++ ) {
	    //	echo "col <pre>\n";print_r($this->navtable[$i]); echo "</pre>\n";
	    for ( $j=0; $j < count($this->navtable[$i]); $j++ ) {
		//  echo "row <pre>\n";print_r($this->navtable[$i][$j]); echo "</pre>\n";
		if ( $this->page == $this->navtable[$i][$j]['target']) {
		    $col=$i;
		    $row=$j;
		}
		//	    echo $leftlinkdata['target']."<br/>";
	    }
	}
	echo "<div id='navhead'>\n";
	// display top row
	echo "<table style='border-collapse:collapse; empty-cells:show' width='100%' summary='navigation'>".
	    "<tr><th></th>\n";
	for ($i=0; $i < count( $this->navtable ); $i++ ) {
	    $tdClass='navtop ';
	    if ($i == $col) {
		if ( $i == 0 ) {
		    $tdClass .= ' lefton';
		} else {
		    $tdClass .= ' midon';
		}
		$previousActive=true;
	    } else {
		if ( $i == 0 ) {
		    $tdClass .= ' leftoff';
		} else if ($previousActive){
		    $tdClass .= ' midonoff';
		} else {
		    $tdClass .= ' midoff';
		}
		$previousActive=false;
	    }
	
	    echo "\t\t\t<td class='$tdClass'><a href='".$this->navtable[$i][0]['target']."'".
		" title='".$this->navtable[$i][0]['title']."'>".
		$this->navtable[$i][0]['linktext'].
		"&nbsp;<img border='0' alt='' src='".IMAGEROOT.'/'.$this->navtable[$i][0]['image']."'/>".
		"</a></td>\n";

	}
	// display head trailer (with logoff)
	if ( $col != count($this->navtable) ) { // was last column active one?
	    $tdClass ='navtop rightoff';
	} else {
	    $tdClass ='navtop righton';
	}
	if ( $this->hasCloseButton ) {
	    echo "\t<th class='$tdClass'></th><th align='right'>".
		"<a href='".$this->logoutlink."' title='logout'><img src='".IMAGEROOT."/close_1.png' border='0' alt='logout'/></a></th></tr>";
	} else {
	    echo "\t</tr>\n";
	}
	echo "</table>\n";
	// display page opening in special div
	echo "<div class='navopening'>$this->pageOpening</div>\n";
	echo "</div>\n";
	// display left nav column
	if ( $col >=0 ) {
	    $tdClass='navcol';
	    echo "<div id='navcol'>";
	    echo "<table class='navcol'>\n";
	    if ( $this->helpPage !='' ) {
		echo "\t<tr><td class='$tdClass'><a href='".$this->helpPage."'".
		    " title='Help' target='_blank'>".
		    "<img border='0' alt='' src='".IMAGEROOT.'/'.$this->helpIcon."'/>".
		    "Help".
		    "</a><br/><hr/></td></tr>\n";
	    }
	    for ( $j=0; $j < count($this->navtable[$col]); $j++ ) {
		$tdClass='navcol';
		if ($j == $row)
		    $tdClass .=' selected';
		echo "\t<tr><td class='$tdClass'><a href='".$this->navtable[$col][$j]['target']."'".
		    " title='".$this->navtable[$col][$j]['title']."'>".
		    "<img border='0' alt='' src='".IMAGEROOT.'/'.$this->navtable[$col][$j]['image']."'/>".
		    $this->navtable[$col][$j]['linktext'].
		    "</a><br/><hr/></td></tr>\n";
	    }
	    echo "</table>\n</div>\n";
	}
    }
    function setLogoutLink($l){
	$this->logoutlink = $l;
    }
} // class Navigation;
?>
