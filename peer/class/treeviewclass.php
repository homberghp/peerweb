<?php

/*
 +------------------------------------------------------------------------+
 | PHP TreeView :: Create your own tree menus                             |
 +------------------------------------------------------------------------+
 | Copyright (c) 2006 Patrik Bengtsson                             |
 +------------------------------------------------------------------------+
 | This file is protected by          |
 | http://www.phpscripts.se/phptreeview/license.php                       |
 | It's not legal to remove these comments.      |
 +------------------------------------------------------------------------+
*/
 
class TreeView {
  /*User values.*/
  var $selectednodeid = "1";
  var $currenthtmlpage = 'peertree.php';//"index.php";
  var $widthbetweennodelayers = 20;
  var $width = 250;
  var $nodeheight = 18;
  var $selectedcolor = "#ebebeb"; 
  var $onmouseovernodecolor = "#d7d7d7"; 
  var $backgroundcolor = "";
  var $cssclass; 
  var $showexpandimage = true;
  var $switchimagesonclick = true;
  var $selectednodebyid = false;   /*To avoid to update selected node by $_GET["nodeid"].*/
  var $hiderootnode = false;
  var $defaultopenedimage; 
  var $defaultclosedimage; 
  var $widthbetweennodeelements = 4;
  var $programmetic_called_set_open_id = "-1";
  var $treeview_string = "";
  var $showOnClickInformation = false;
  var $closeSiblingNodes = true;

  /*Todo:*/
  var $nonodeimages = false;

  var $Nodes = array();/*Whole tree.*/ 
  var $TmpLoadNodes = array();/*Whole tree.*/ 

  /*Application values.*/
  var $printnodes = array();  /*Visible part of tree.*/

  var $loadbasesettings = false;
  var $layer = 0;
  var $treeviewid;

  var $onmouseover_underline = true;
  var $underline = false;
  var $font_color = "black";

  var $special_sign_names = array("å","ä","ö","Å","Ä","Ö","á","ü","Ü","ß","§","€");
  var $special_sign_values = array("&aring;" ,"&auml;", "&ouml;", "&Aring;", "&Auml;", "&Ouml;", "&acute;", "&uuml;", "&Uuml;", "&szlig;", "&sect;", "&euro;");

  function TreeView($id)
  {
    $this->treeviewid = $id;
    $this->defaultopenedimage = TREEVIEW_SOURCE."media/opened.gif";              
    $this->defaultclosedimage = TREEVIEW_SOURCE."media/closed.gif";              
  }

  function AddNode($node)
  {
    $node->objTreeView = &$this;
    //$node->SetName("".$node->GetName());
    $node->SetName($this->ReplaceSpecialSigns("".$node->GetName()));
    $node->onClickInformation = $this->ReplaceSpecialSigns("".$node->onClickInformation);
    $this->Nodes[$node->GetId()] = $node;
    $this->loadbasesettings = true;

    if ($this->showOnClickInformation == false and $node->onClickInformation != "")
      $this->showOnClickInformation = true;
  }

  function ReplaceSpecialSigns($str)
  {
    $index = 0;
    foreach ($this->special_sign_names as $name)
      {
	$str = str_replace($name, $this->special_sign_values[$index], $str);
	$index++;
      }
    return $str;
  }

  function RemoveNode($id)
  {
    if (array_key_exists($id, $this->Nodes) == true) 
      {
	$removekeys = array();
	$removekeys[] = $id;
	$allnodeskeys = array();
	$allnodeskeys = array_keys($this->Nodes);
	$this->FindChildToRemove($this->Nodes[$id], $allnodeskeys, $removekeys);

	foreach ($removekeys as $key)
	  {
	    unset($this->Nodes[$key]);
	    /*echo "Removing key: ".$key."<br/>";*/
	  }
	$this->loadbasesettings = true;
      }
  }

  /*Called from treenode class.*/
  function DeselectOtherRadioButtonsWithSameParent($node)
  {
    $allnodeskeys = array();
    $allnodeskeys = array_keys($this->Nodes);

    foreach ($allnodeskeys as $key)
      {
	if ($node->GetParentId() == $this->Nodes[$key]->GetParentId() and $this->Nodes[$key]->GetIsRadioButton() == true and $this->Nodes[$key]->GetId() != $node->GetId())
	  $this->Nodes[$key]->SetRadioButtonIsSelected(false);
      }
  }

  function FindChildToRemove($node, $allnodeskeys, &$removekeys)
  {
    foreach ($allnodeskeys as $key)
      {
	if ($node->GetId() == $this->Nodes[$key]->GetParentId() and $node->GetId() != $this->Nodes[$key]->GetId())
	  {
	    $removekeys[] = $key;
	    $this->FindChildToRemove($this->Nodes[$key], $allnodeskeys, $removekeys);
	  }
      }
  }

  function LoadPrintNodes()
  {
    $allnodeskeys = array();

    //$allnodeskeys = array_keys($this->Nodes);
    $allnodeskeys = array_keys($this->Nodes);

    unset($this->printnodes);

    if (count($allnodeskeys) > 0)
      {
	$this->printnodes[$allnodeskeys[0]] = $this->Nodes[$allnodeskeys[0]];

	if ($this->loadbasesettings == true)
	  {
	    /*When a node has been added or removed.*/
	    $this->layer = 0;
	    $this->LoadBaseSettingsToAllNodes($this->Nodes[$allnodeskeys[0]]);
	    $this->loadbasesettings = false;
	  }

	$this->TmpLoadNodes = $this->Nodes;

	if ($this->printnodes[$allnodeskeys[0]]->GetOpened() == true)
	  $this->FindChild($this->TmpLoadNodes[$allnodeskeys[0]], array_keys($this->TmpLoadNodes));
      }
  }

  function FindChild($node)
  {
    $foundchild_to_current_node = false;

    foreach ($this->TmpLoadNodes as $child)
      {
	if (array_key_exists($child->GetId(), $this->TmpLoadNodes) == true)
	  {
	    if ($node->GetId() == $child->GetParentId() and $node->GetId() != $child->GetId())
	      {
		$this->printnodes[$child->GetId()] = $child;
		$foundchild_to_current_node = true;

		if ($child->GetOpened() == true)
		  $this->FindChild($child);
	      }
	  }
      }

    //To optimize this recursive function.
    if ($foundchild_to_current_node == false)
      unset($this->TmpLoadNodes[$node->GetId()]);
  }

  
  function LoadBaseSettingsToAllNodes($node)
  {
    $foundchild = false;
    
    foreach ($this->Nodes as $child)
      {
	if ($node->GetId() == $child->GetParentId() and $node->GetId() != $child->GetId())
	  {
	    $this->layer++;
	    $this->Nodes[$child->GetId()]->SetWidthFromLeft($this->layer * $this->widthbetweennodelayers);
	    
	    $foundchild = true;
	    
	    if ($this->LoadBaseSettingsToAllNodes($child) == false)
	      $child->SetLastChild(true);
	  }
      }
    
    $this->layer--;
    
    return $foundchild;
  }
  
  function HttpUpdateNodeById($id)
  {
    if (array_key_exists($id, $this->Nodes) == true) 
      {
	if ($this->Nodes[$id]->open_programmed_by_user == false)
	  {
	    /*Avoid to change open state when user has set that value.*/
	    if ($this->Nodes[$id]->GetOpened() == true)
	      {
		if ($this->Nodes[$id]->GetAlwaysOpened() == false)
		  $this->Nodes[$id]->SetOpenedInternally(false);
	      }
	    else
	      {
		if ($this->Nodes[$id]->GetAlwaysClosed() == false)
		  {
		    $this->Nodes[$id]->SetOpenedInternally(true);
		    
		    if ($this->closeSiblingNodes == true)
		      {
			/* close all other nodes with same parent id, to save space */
			$keys = array_keys($this->Nodes);
			foreach ($keys as $key)
			  {
			    if ($this->Nodes[$key]->GetId() != $this->Nodes[$id]->GetId() and $this->Nodes[$key]->GetParentId() == $this->Nodes[$id]->GetParentId())
			      $this->Nodes[$key]->SetOpenedInternally(false);
			  }
		      }
		  }
	      }
	  }
	$this->SelectNodeByIdHttp($id);
      }
  }
  
  function SetSelectedNodeId($newvalue)
  {
    $this->selectednodeid = $newvalue;
  }
  
  function GetSelectedNodeId()
  {
    return $this->selectednodeid;
  }

  function SelectNodeByIdHttp($nodeid)
  {
    if (array_key_exists($nodeid, $this->Nodes) == true) 
      {
	$this->selectednodeid = $nodeid;
	$this->OpenParentNodesById($nodeid);
      }
  }

  function SelectNodeById($nodeid)
  {
    if (array_key_exists($nodeid, $this->Nodes) == true) 
      {
	$this->selectednodebyid = true; /*To avoid to update selected node by $_GET["nodeid"].*/
	$this->selectednodeid = $nodeid;
	$this->OpenParentNodesById($nodeid);
      }
  }

  function GetCheckedCheckboxNodes()
  {
    $checkboxnodes = array();

    $nodeskeys = array();
    $nodeskeys = array_keys($this->Nodes);

    foreach ($nodeskeys as $key)
      {
	if ($this->Nodes[$key]->GetIsCheckBox() == true && $this->Nodes[$key]->GetCheckBoxIsChecked() == true)
	  $checkboxnodes[] = $this->Nodes[$key];
      }

    return $checkboxnodes;
  }

  function GetSelectedRadioButtonNodes()
  {
    $radiobuttonnodes = array();

    $nodeskeys = array();
    $nodeskeys = array_keys($this->Nodes);

    foreach ($nodeskeys as $key)
      {
	if ($this->Nodes[$key]->GetIsRadioButton() == true && $this->Nodes[$key]->GetRadioButtonIsSelected() == true)
	  $radiobuttonnodes[] = $this->Nodes[$key];
      }

    return $radiobuttonnodes;
  }

  function OpenParentNodesById($nodeid)
  {
    $allnodeskeys = array();
    $allnodeskeys = array_keys($this->Nodes);

    foreach ($allnodeskeys as $key)
      {
	if ($this->Nodes[$nodeid]->GetParentId() == $this->Nodes[$key]->GetId() and $this->Nodes[$nodeid]->GetId() != $this->Nodes[$key]->GetId())
	  {
	    $this->Nodes[$key]->SetOpenedInternally(true);
	    $this->OpenParentNodesById($this->Nodes[$key]->GetId());
	  }
      }
  }

  function PrintTreeView()
  {
    echo "<div id=\"".$this->treeviewid."\">\n";

    $this->CreateTreeView();
    echo $this->treeview_string;

    echo "\n</div>\n";
  }

  function CreateTreeView()
  {

    $this->selectednodebyid = false;
    $this->HideRootNode();
    $this->LoadPrintNodes();

    if (count($this->printnodes) > 0)
      {
	$printnodeskeys = array();
	$printnodeskeys = array_keys($this->printnodes);

	$this->treeview_string = "<input style=\"display: none;\" type=\"text\" name=\"treeviewid\" value=\"".$this->treeviewid."\"/>";

	$firstloop = true;
	foreach ($printnodeskeys as $key)
	  {
	    if ($this->hiderootnode == true and $firstloop == true) 
	      {
		$firstloop = false;
		continue;
	      }

	    $cssclass = "";
	    $toolTip = $this->printnodes[$key]->GetToolTip();
	    
	    if ($this->printnodes[$key]->GetCssClass() != "")
	      $cssclass = $this->printnodes[$key]->GetCssClass();
	    elseif ($this->cssclass != "")
	      $cssclass = $this->cssclass;

	    
	    if ($cssclass != "")
	      $cssclass = "class=\"".$cssclass."\"";

//	    $this->treeview_string .= "<table ".$cssclass." width=\"".$this->width."\" style=\"height: ".$this->nodeheight.
//	      "px;\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" summary='treeview table'>\n";
	    $this->treeview_string .= "<table ".$cssclass."  style=\"height: ".$this->nodeheight.
	      "px;\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" summary='treeview table'>\n";

	    $backgroundcolor = "";
	    if ($this->selectednodeid == $this->printnodes[$key]->GetId())
	      {
		$backgroundcolor = $this->selectedcolor;
	      }
	    else
	      {
		if ($this->printnodes[$key]->GetBackgroundColor() != "")
		  $backgroundcolor = $this->printnodes[$key]->GetBackgroundColor();
		else
		  $backgroundcolor = $this->backgroundcolor;
	      }

	    $uservar = "";
	    $varnames  = array();
	    $varvalues = array();
	    $varnames  = $this->printnodes[$key]->GetHttpVarNames();
	    $varvalues = $this->printnodes[$key]->GetHttpVarValues();

	    for ($t = 0; $t < count($varnames); $t++)
	      {
		if ($varnames[$t] != "")
		  $uservar .= "&amp;".$varnames[$t]."=".$varvalues[$t];
	      }

	    $ahrefurl = "";
	    $ahrefurlend = "</a>";

	    if ($this->printnodes[$key]->GetExternUrl() != "" and $this->printnodes[$key]->extern_url_new_browser_window == true)
	      {
		$ahrefurl = "<a href=\"".$this->printnodes[$key]->GetExternUrl()."\" target=\"_blank\">";
	      }
	    else if ($uservar == "") 
	      {
		/*Ajax update.*/
		$ahrefurl = "<div style=\"cursor: pointer;\" onclick=\"xajax_AjaxUpdateSelectedNode('".$this->treeviewid."','".
		  $this->printnodes[$key]->GetId()."');\"".(($toolTip!='')?" title='$toolTip'":'').">";
		$ahrefurlend = "</div>";
	      }
	    else
	      {
		$ahrefurl = "<a href=\"".$this->currenthtmlpage."?treeviewid=".$this->treeviewid."&amp;nodeid=".$this->printnodes[$key]->GetId()."".$uservar."\">";
	      }

	    if ($backgroundcolor !=''){
	      $trbkg='bgcolor="'.$backgroundcolor.'"';
	    } else {
	      $trbkg='';
	    }

	    $this->treeview_string .= "\t<tr onmouseover=\"this.style.background='".$this->onmouseovernodecolor.
	      "';\" onmouseout=\"this.style.background='".$backgroundcolor."';\" $trbkg >\n";

	    $widthfromleft = 0;
	    if ($this->hiderootnode == true) 
	      $widthfromleft = $this->printnodes[$key]->GetWidthFromLeft() - $this->widthbetweennodelayers;
	    else
	      $widthfromleft = $this->printnodes[$key]->GetWidthFromLeft();

	    $this->treeview_string .= "\t\t<td>";
	    $this->treeview_string .= "<img src=\"".TREEVIEW_SOURCE."media/empty.gif\" width=\"".($widthfromleft?$widthfromleft:'0')."\" height=\"1\" alt=\"\"/><!-- a  -->";
	    $this->treeview_string .= "</td>\n";

	    if ($this->printnodes[$key]->GetLastChild() == true)
	      {
		if ($this->showexpandimage == true)
		  {
		    $this->treeview_string .= "\t\t<td>";
		    $this->treeview_string .= "<img src=\"".TREEVIEW_SOURCE."media/empty.gif\" width=\"9\" height=\"1\" alt=\"\"/>";
		    $this->treeview_string .= "</td>\n";
		  }
	      }
	    else
	      {
		if ($this->showexpandimage == true)
		  {
		    $addimage = "";
		    if ($this->printnodes[$key]->GetOpened() == true)
		      $addimage = "subtract.gif";
		    else
		      $addimage = "add.gif";

		    $this->treeview_string .= "\t\t<td>";
		    $this->treeview_string .= $ahrefurl."<img src=\"".TREEVIEW_SOURCE."media/".$addimage."\" border=\"0\" alt=\"\"/>".$ahrefurlend;
		    $this->treeview_string .= "</td>\n";
		  }
	      }

	    if ($this->showexpandimage == true)
	      {
		/*Add space between add/subtract image and folder image or name.*/
		$this->treeview_string .= "\t\t<td>";
		$this->treeview_string .= "<img src=\"".TREEVIEW_SOURCE."media/empty.gif\" width=\"".$this->widthbetweennodeelements."\" height=\"1\" alt=\"\"/>";
		$this->treeview_string .= "</td>\n";
	      }


	    if ($this->printnodes[$key]->GetIsCheckBox() == true)
	      {
		$checked = "";
		if ($this->printnodes[$key]->GetCheckBoxIsChecked() == true)
		  $checked = "checked=\"checked\"";

		$this->treeview_string .= "\t\t<td>";
		$this->treeview_string .= "<input onclick=\"xajax_AjaxUpdateNodesCheckBoxValues('".$this->treeviewid.
		  "','".$this->printnodes[$key]->GetId()."');\" type=\"checkbox\" name=\"cbnode".$this->printnodes[$key]->GetId()."\" ".$checked." value=\"\"/>";

		$this->treeview_string .= "</td>\n";
	      }
	    else if ($this->printnodes[$key]->GetIsRadioButton() == true)
	      {
		$checked = "";
		if ($this->printnodes[$key]->GetRadioButtonIsSelected() == true)
		  $checked = "checked=\"checked\"";

		$this->treeview_string .= "\t\t<td>";
		$this->treeview_string .= "<input onclick=\"xajax_AjaxUpdateNodesRadioButtonValues('".$this->treeviewid.
		  "','".$this->printnodes[$key]->GetId()."');\" type=\"radio\" name=\"rbnode".$this->printnodes[$key]->GetParentId().
		  "\" ".$checked." value=\"".$this->printnodes[$key]->GetId()."\"/>";

		$this->treeview_string .= "</td>\n";
	      }
	    else
	      {
		$image = "";

		if ($this->printnodes[$key]->GetLastChild() == true)
		  {
		    if ($this->printnodes[$key]->GetClosedImageSource() != "")
		      $image = $this->printnodes[$key]->GetClosedImageSource();
		    else
		      $image = $_SESSION[$this->treeviewid]->GetDefaultClosedImage();
		  }
		else if ($this->printnodes[$key]->GetOpened() == true)
		  {
		    if ($this->printnodes[$key]->GetOpenedImageSource() != "")
		      $image = $this->printnodes[$key]->GetOpenedImageSource();
		    else
		      $image = $_SESSION[$this->treeviewid]->GetDefaultOpenedImage();
		  }
		else
		  {
		    if ($this->printnodes[$key]->GetClosedImageSource() != "")
		      $image = $this->printnodes[$key]->GetClosedImageSource();
		    else
		      $image = $_SESSION[$this->treeviewid]->GetDefaultClosedImage();
		  }

		$this->treeview_string .= "\t\t<td>";
		if ($image != "")
		  {
		    $this->treeview_string .= $ahrefurl."<img src=\"".$image."\" border=\"0\" alt=\"\"/>".$ahrefurlend;
		  }
		$this->treeview_string .= "</td>\n";
	      }

	    $this->treeview_string .= "\t\t<td>";

	    if ($this->printnodes[$key]->GetIsCheckBox() == false && $this->printnodes[$key]->GetIsRadioButton() == false)
	      {
		/*Add space between image and name.*/
		$this->treeview_string .= "<img src=\"".TREEVIEW_SOURCE."media/empty.gif\" width=\"".($this->widthbetweennodeelements-1)."\" height=\"1\" alt=\"\"/>";
	      }

	    $this->treeview_string .= "</td>\n";



	    $underline_decoration = "none";
	    if ($this->printnodes[$key]->GetUnderline() == true)
	      {
		$underline_decoration = "underline";
	      }
	    else 
	      {
		if ($this->GetUnderline() == true)
		  {
		    $underline_decoration = "underline";
		  }
	      }

	    $underline_javascript = "";
	    if ($this->printnodes[$key]->GetOnMouseOverUnderline() == true)
	      {
		$underline_javascript = "onmouseover=\"this.style.textDecoration = 'underline';\" onmouseout=\"this.style.textDecoration = '".$underline_decoration."';\"";
	      }
	    else 
	      {
		if ($this->GetOnMouseOverUnderline() == true)
		  {
		    $underline_javascript = "onmouseover=\"this.style.textDecoration = 'underline';\" onmouseout=\"this.style.textDecoration = '".$underline_decoration."';\"";
		  }
	      }

	    $font_color = "";
	    if ($this->printnodes[$key]->GetFontColor() != "")
	      {
		$font_color = "color: ".$this->printnodes[$key]->GetFontColor().";";
	      }
	    else 
	      {
		if ($this->GetFontColor() != "")
		  {
		    $font_color = "color: ".$this->font_color.";";
		  }
	      }

	    $main_start_span = "\n\t\t\t<span ".$underline_javascript." style=\"".$font_color."\n\t\t\t\t text-decoration: ".$underline_decoration.";\">";
	    $main_end_span = "</span>\n";


	    $frameurl = "";
	    $externUrlSameBrowser = "";
	    
	    if ($this->printnodes[$key]->GetFrameUrl() != "")
	      {
		$frameurl = "<a href=\"".$this->printnodes[$key]->GetFrameUrl()."\" target=\"".$this->printnodes[$key]->getFrameTarget().
		  "\"".(($toolTip!='')?" title='$toolTip'":'').">";
		$ahrefurlend="</a>\n";
	      }
	    else if ($this->printnodes[$key]->GetExternUrl() != "" and $this->printnodes[$key]->extern_url_new_browser_window == false)
	      {
		$externUrlSameBrowser = "<a href=\"".$this->printnodes[$key]->GetExternUrl()."?treeviewid=".
		  $this->treeviewid."&amp;nodeid=".$this->printnodes[$key]->GetId()."\" target=\"mainframe\"".(($toolTip!='')?" title='$toolTip'":'').">";
	      }

	    if ($externUrlSameBrowser != "")
	      {
		/*Do only open the link when a user clicks on the node text. Or else the expand functionality will not work.*/
		$ahrefurl = $externUrlSameBrowser;
		$ahrefurlend="</a>\n";
	      }
	    else if ($frameurl != "")
	      {
		/*Do only open the frame-link when a user clicks on the node text. Or else the expand functionality will not work.*/
		$ahrefurl = $frameurl;  
		$ahrefurlend="</a>\n";
	      }

	    $this->treeview_string .= "\t\t<td width=\"100%\">";
	    $this->treeview_string .= $ahrefurl."".$main_start_span."".$this->printnodes[$key]->GetDisplayName()."".$main_end_span."".$ahrefurlend;
	    $this->treeview_string .= "</td>\n";

	    $this->treeview_string .= "</tr>\n";
	    $this->treeview_string .= "</table>\n";

	    $firstloop = false;
	  }
      }

    $nodeskeys = array_keys($this->Nodes);
    foreach ($nodeskeys as $key)
      $this->Nodes[$key]->open_programmed_by_user = false;
  }

  function PrintOnClickInformation()
  {
    echo "<div id=\"".$this->treeviewid."OnClickInformation\">";
    echo $this->GetOnClickInformation($this->selectednodeid);
    echo "</div>\n";
  }

  function GetOnClickInformation($nodeid)
  {
    if (array_key_exists($nodeid, $this->Nodes) == true)
      {
	return "".$this->Nodes[$nodeid]->onClickInformation;
      }
    return "";
  }

  function GetShowExpandImage()
  {
    return $this->showexpandimage;
  }   

  function SetShowExpandImage($newvalue)
  {
    $this->showexpandimage = $newvalue;
  }   

  function GetCssClass()
  {
    return $this->cssclass;
  }   

  function SetCssClass($newvalue)
  {
    $this->cssclass = $newvalue;
  }

  function GetCurrentPhpPage()
  {
    return $this->currenthtmlpage;
  }   

  function SetCurrentPhpPage($newvalue)
  {
    $this->currenthtmlpage = $newvalue;
  }

  function GetWidthBetweenNodeLayers()
  {
    return $this->widthbetweennodelayers;
  }   

  function SetWidthBetweenNodeLayers($newvalue)
  {
    $this->widthbetweennodelayers = $newvalue;
  }

  function GetWidth()
  {
    return $this->width;
  }   

  function SetWidth($newvalue)
  {
    $this->width = $newvalue;
  }

  function GetNodeHeight()
  {
    return $this->nodeheight;
  }   

  function SetNodeHeight($newvalue)
  {
    $this->nodeheight = $newvalue;
  }

  function GetSelectedNodeBackgroundColor()
  {
    return $this->selectedcolor;
  }   

  function SetSelectedNodeBackgroundColor($newvalue)
  {
    $this->selectedcolor = $newvalue;
  }

  function GetOnMouseOverNodeColor()
  {
    return $this->onmouseovernodecolor;
  }   

  function SetOnMouseOverNodeColor($newvalue)
  {
    $this->onmouseovernodecolor = $newvalue;
  }

  function GetBackgroundColor()
  {
    return $this->backgroundcolor;
  }   

  function SetBackgroundColor($newvalue)
  {
    $this->backgroundcolor = $newvalue;
  }

  function GetSwitchImagesOnClick()
  {
    return $this->switchimagesonclick;
  }   

  function SetSwitchImagesOnClick($newvalue)
  {
    $this->switchimagesonclick = $newvalue;
  }

  function GetHideRootNode()
  {
    return $this->hiderootnode;
  }   

  function HideRootNode()
  {
    if ($this->hiderootnode == true)
      {
	$allnodeskeys = array();
	$allnodeskeys = array_keys($this->Nodes);

	if (count($allnodeskeys) > 0)
	  {
	    $this->Nodes[$allnodeskeys[0]]->SetOpenedInternally(true);
	  }
	else
	  return false;
      }
    return true;
  }

  function SetHideRootNode($newvalue)
  {
    $this->hiderootnode = $newvalue;
  }

  function SetDefaultOpenedImage($image_source)
  {
    $this->defaultopenedimage = $image_source;
  }

  function GetDefaultOpenedImage()
  {
    return $this->defaultopenedimage;
  }   

  function SetDefaultClosedImage($image_source)
  {
    $this->defaultclosedimage = $image_source;
  }

  function GetDefaultClosedImage()
  {
    return $this->defaultclosedimage;
  }

  function SetOnMouseOverUnderline($onmouseover_underline)
  {
    $this->onmouseover_underline = $onmouseover_underline;
  }

  function GetOnMouseOverUnderline()
  {
    return $this->onmouseover_underline;
  }

  function SetFontColor($font_color)
  {
    $this->font_color = $font_color;
  }

  function GetFontColor()
  {
    return $this->font_color;
  }

  function SetUnderline($underline)
  {
    $this->underline = $underline;
  }

  function GetUnderline()
  {
    return $this->underline;
  }

  function SetCloseSiblingNodes($close)
  {
    $this->closeSiblingNodes = $close;
  }

  function GetCloseSiblingNodes()
  {
    return $this->closeSiblingNodes;
  }

}
?>