<?php

//Define where you have placed the phptreeview folder.
//define("TREEVIEW_SOURCE", "../../");
define('PEERICONS',"/peertest/images/");
define('PEERSITE',"$root_url/");
include 'navtable.php';        
//include(TREEVIEW_SOURCE."treeviewclasses.php"); //Include the phptreeview engine.
        
//session_start();

$xajax = new xajax(); 
include(TREEVIEW_SOURCE."ajax/ajax.php");       //Enables real-time update. Must be called before any headers or HTML output have been sent.
$xajax->processRequests();
        
//Define identify name(s) to your treeview(s); Add more comma separated names to create more than one treeview. The treeview names must always be unique. You can´t even use the same treeview names on different php sites. 

//$treeviewid = array("treeviewone","treeviewtwo");
  
$treeviewid = array();

for( $i=0; $i < count($navtable); $i++){
  $menu_name=$navtable[$i][0]['toplinktext'];
  array_push($treeviewid,$menu_name);
}

foreach ($treeviewid as $treeid)
{
  if (isset($_SESSION[$treeid]) == false)
    {
      $objMenu = new TreeView($treeid);
      $_SESSION[$treeid] = $objMenu;
    }
  
  if (isset($_POST["treeviewid"]) == true)
    {
      if ($_POST["treeviewid"] == $treeid)
	{
	  //$_SESSION[$treeid]->UpdateNodesCheckBoxValues();
	  //$_SESSION[$treeid]->UpdateNodesRadioButtonValues();			
	}
    }
  
  if (isset($_GET["treeviewid"]) == true and isset($_GET["nodeid"]) == true)
    { 
      if ($_GET["treeviewid"] == $treeid)
	{
	  $_SESSION[$treeid]->HttpUpdateNodeById($_GET["nodeid"]);
	}
    }
}

include(TREEVIEW_SOURCE."treeviewcreate.php"); //Creates phptreeview objects.

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>Url TreeView</title>
<?php $xajax->printJavascript(TREEVIEW_SOURCE."ajax/framework"); //Enables real-time update. ?>

                
<!-- some basic css properties to make it look ok -->
<link href="<?php echo TREEVIEW_SOURCE; ?>css/style.css" rel="stylesheet" type="text/css"/>
</head>
<body>
        
<?php
//IMPORTANT! To be able to see the changes you have made to the code you have to clean the session.
//(By uncomment the line below during one page load). 
// Be sure to comment the line when publishing the treeview, or else the treeview won´t remember the old state throu page loads.
unset($_SESSION["NodesHasBeenAddedUrl"]); 
                
if (isset($_SESSION["NodesHasBeenAddedUrl"]) == false)
{
  $genid=0;
  for( $i=0; $i < count($navtable); $i++ ){
    $menu_name=$navtable[$i][0]['toplinktext'];

    unset($_SESSION[$menu_name]->Nodes);
    
    $node = new TreeNode($genid, $menu_name);     //Create a new node object with id "1" and set name to "Root Folder".
    if ($i==0)  $node->setOpened(true);
    $node->SetClosedImageSource(PEERICONS.$navtable[$i][0]['image']); //This node has no childs, which means it´s always closed. 
    $node->SetOpenedImageSource(PEERICONS.$navtable[$i][0]['image']); //This node has no childs, which means it´s always closed. 
    $_SESSION[$menu_name]->AddNode($node);     //Add "Root Folder" node to treeview.
    $_SESSION[$menu_name]->SetWidthBetweenNodeLayers(6);
    $parent=$genid;

    $genid++;
    
    $submenu_count=0;
    for( $j=0; $j < count($navtable[$i]); $j++ ){
      $submenu_name=$navtable[$i][$j]['linktext'];
      unset($_SESSION[$submenu_name]->Nodes);
      $subnode = new TreeNode($genid, $submenu_name);     //Create a new node object with id "1" and set name to "Root Folder".
      $subnode->SetParentId($parent);                                      //Set "sites" node as parent.
      $subnode->SetExternUrl(PEERSITE.$navtable[$i][$j]['target']);
      $subnode->SetClosedImageSource(PEERICONS.$navtable[$i][$j]['image']); //This node has no childs, which means it´s always closed. 
      $subnode->SetOpenedImageSource(PEERICONS.$navtable[$i][$j]['image']); //This node has no childs, which means it´s always closed. 

      $_SESSION[$menu_name]->AddNode($subnode);     //Add "Root Folder" node to treeview.
      $genid++;
    }
  }
  $_SESSION["NodesHasBeenAddedUrl"] = true;
}
                
//Print the treeview.

foreach($treeviewid as $subtree){
  $_SESSION[$subtree]->PrintTreeView();
}
                
?>
        
<br/><br/><br/><br/>
<a href="index.txt" target="_blank"><b>CLICK HERE TO VIEW CODE AS TXT-FILE</b></a>
        
</body> 
</html>