<?php
/**
 * build a tree for the peerweb menu
 */

class TreeBuilder {
  var $peertreeviewid = array();
  var $dbConn;
  var $navtable;
  var $interestMap;
  /**
   * construct the treebuilder
   */
  function __construct($dbConn, $navtable, $interestMap){
    $peertreeviewid = array();
    $this->dbConn = $dbConn;
    $this->navtable = $navtable;
    $this->interestMap = $interestMap;
  }

  function buildTrees() {
    $this->prune_navigation_table();
    for( $i=0; $i < count($this->navtable); $i++){
      $menu_name=$this->navtable[$i]['menu_name'];
      array_push($this->peertreeviewid,$menu_name);
    }
    
    foreach ($this->peertreeviewid as $treeid) {
	if (isset($_SESSION[$treeid]) == false) {
	    $objMenu = new TreeView($treeid);
	    $objMenu->SetBackGroundColor("white");
	    //$objMenu->SetWidth(25);
	    $objMenu->SetNodeHeight(22);
	    //      $objMenu-SetCurrentPhpPage('peertree.php');
	    $_SESSION[$treeid] = $objMenu;
	  }
	
	if (isset($_POST["treeviewid"]) == true) {
	    if ($_POST["treeviewid"] == $treeid) {
		//$_SESSION[$treeid]->UpdateNodesCheckBoxValues();
		//$_SESSION[$treeid]->UpdateNodesRadioButtonValues();			
	      }
	  }
	
	if (isset($_GET["treeviewid"]) == true and isset($_GET["nodeid"]) == true) { 
	    if ($_GET["treeviewid"] == $treeid) {
	      $_SESSION[$treeid]->HttpUpdateNodeById($_GET["nodeid"]);
	    }
	}
    }
    if (isset($_SESSION["NodesHasBeenAddedUrl"]) == false)
      {
	$genid=0;
	for( $i=0; $i < count($this->navtable); $i++ ){
	  $menu_name=$this->navtable[$i]['menu_name'];
	  $linktext=$this->navtable[$i]['toplinktext'];
	  
	  unset($_SESSION[$menu_name]->Nodes);
	  
	  $node = new TreeNode($genid, $menu_name,"",$linktext);  //Create a new node object with id "1"
								  //and set name to "Root Folder".
	  if (isSet($this->navtable[$i]['tooltip']) && $this->navtable[$i]['tooltip'] != '') {
	    $node->SetToolTip($this->navtable[$i]['tooltip']);
	  }
	  if ($i==0)  $node->setOpened(true);
	  $icon=PEERICONS.$this->navtable[$i]['image'];
	  $node->SetClosedImageSource($icon); //This node has no childs,
	                                      //which means it´s always closed. 
	  $node->SetOpenedImageSource($icon);
	  $_SESSION[$menu_name]->AddNode($node);     //Add "Root Folder" node to treeview.
	  $_SESSION[$menu_name]->SetWidthBetweenNodeLayers(6);
	  $parent=$genid;
	  
	  $genid++;
	  
	  $submenu_count=0;
	  for( $j=0; $j < count($this->navtable[$i]['subitems']); $j++ ){
	    $submenu_name = $this->navtable[$i]['subitems'][$j]['target'];
	    $linktext     = $this->navtable[$i]['subitems'][$j]['linktext'];
	    unset($_SESSION[$submenu_name]->Nodes);
	    $subnode = new TreeNode($genid, $submenu_name,"",$linktext);  //Create a new node object and
									  //set name to "Root Folder".
	    $subnode->SetParentId($parent);                               //Set "sites" node as parent.
	    $subnode->SetExternUrl(PEERSITE.$this->navtable[$i]['subitems'][$j]['target'],false);
	    $icon =               PEERICONS.$this->navtable[$i]['subitems'][$j]['image'];
	    $subnode->SetClosedImageSource($icon); 
	    if (isSet($this->navtable[$i]['subitems'][$j]['tooltip']) && $this->navtable[$i]['subitems'][$j]['tooltip'] != '') {
	      $subnode->SetToolTip($this->navtable[$i]['subitems'][$j]['tooltip']);
	    }
	    $subnode->SetOpenedImageSource($icon);
	    
	    $_SESSION[$menu_name]->AddNode($subnode);     //Add "Root Folder" node to treeview.
	    $genid++;
	  }
	}
	$_SESSION["NodesHasBeenAddedUrl"] = true;
      }
  }

  function printTrees(){
    foreach($this->peertreeviewid as $subtree){
      $_SESSION[$subtree]->PrintTreeView();
    }
  }
  /**
   * prune the unwanted nav items from navigation.
   * @param $navarray three dim array to prune. array contains hashkey called interest. This yeilds a key into the interest map. 
   * @param $interestmap map which determines what is interesting. Non nul leaves nav link in tact.
   *
   * @return pruned array
   */
  function prune_navigation_table( ) {
    if ($this->interestMap === false) return;
    $result = array();
    for ( $i=0; $i < count($this->navtable); $i++) {
      $subset = array();
      if ( $this->interestMap[$this->navtable[$i]['interest']] > 0 ) {
	for ( $j=0; $j < count($this->navtable[$i]['subitems']); $j++) {
	  if ($this->interestMap[$this->navtable[$i]['subitems'][$j]['interest']] > 0 ) {
	    array_push($subset, $this->navtable[$i]['subitems'][$j]);
	  }
	}
      }
      if (count($subset) > 0) {
	$menu= $this->navtable[$i];
	$menu['subitems'] = $subset;
				  
	array_push( $result, $menu );
      }
    }
    $this->navtable= $result;
  }
  
}
