<?php
//
// +------------------------------------------------------------------------+
// | PHPTreeView.com :: Create your own tree menus                          |
// +------------------------------------------------------------------------+
// | Copyright (c) 2006 Patrik Bengtsson                           			|
// +------------------------------------------------------------------------+
// | This file is protected by        										|
// | http://www.phptreeview.com/index.php?treeviewid=1&nodeid=6&siteid=5    |
// | It´s not legal to remove these comments.    							|
// +------------------------------------------------------------------------+

class TreeNode
{
  var $id;                
  var $parentid;                
  var $name;
  var $displayName;
  var $onClickInformation;
  var $open;                  
  var $open_programmed_by_user = false;                  
  var $opened_image_source;              
  var $closed_image_source;              
  var $http_var_names = array();                  
  var $http_var_values = array();                  
  var $change_image_on_click;
  var $ischeckbox; 
  var $checkboxischecked; 
  var $isradiobutton; 
  var $radiobuttonisselected; 
	
  var $width_from_left;
  var $last_child = false;
  var $cssclass; 
	
  var $frame_url = "";
  var $frame_target = "";
  var $toolTip="";
  var $extern_url = "";
  var $backgroundcolor = "";
  var $objTreeView = null;
   
  var $alwaysclosed = false;
  var $alwaysopened = false;
	
  var $onmouseover_underline = true;
  var $underline = false;
  var $font_color = "";
   
  function TreeNode($id, $name = "No name", $onClickInformation = "",$displayName="A nice Menu")
  {
    $this->id = $id;                
    $this->parentid = -1;                
    $this->name = $name;
    $this->onClickInformation = $onClickInformation;
    $this->open = false;                  
   		
    $this->opened_image_source = "";              
    $this->closed_image_source = "";              
   		
    $this->ischeckbox = false;
    $this->checkboxischecked = false;
    $this->isradiobutton = false;
    $this->radiobuttonisselected = false;
    $this->displayName=$displayName;
  }
	
  function GetId()
  {
    return $this->id;
  }	   

  function SetParentId($newvalue)
  {
    $this->parentid = $newvalue;
  }	   

  function GetParentId()
  {
    return $this->parentid;
  }	   

  function SetBackgroundColor($newvalue)
  {
    $this->backgroundcolor = $newvalue;
  }	   
	
  function GetBackgroundColor()
  {
    return $this->backgroundcolor;
  }	   
	
  function SetName($newvalue)
  {
    $this->name = $newvalue;
  }	   

  function GetDisplayName(){
    return $this->displayName;
  }

  function SetDisplayName($name){
    $this->displayName = $name;
  }

  function GetName()
  {
    return $this->name;
  }	   

  function SetOpened($newvalue)
  {
    $this->open = $newvalue;
    $this->open_programmed_by_user = true;                  
  }	   

  function SetOpenedInternally($newvalue)
  {
    $this->open = $newvalue;
    $this->open_programmed_by_user = false;                  
  }	   

  function GetOpened()
  {
    return $this->open;
  }	   

  function SetOpenedImageSource($newvalue)
  {
    $this->opened_image_source = $newvalue;
  }	   

  function GetOpenedImageSource()
  {
    return $this->opened_image_source;
  }	   

  function SetClosedImageSource($newvalue)
  {
    $this->closed_image_source = $newvalue;
  }	   

  function GetClosedImageSource()
  {
    return $this->closed_image_source;
  }	   

  function AddHttpVariable($name, $value)
  {
    $this->http_var_names[] = $name;
    $this->http_var_values[] = $value;
  }	   

  /* Remove these functions in the long run, START */
  function SetHttpVarNames($newvalue)
  {
    $this->http_var_names = $newvalue;
  }	   

  function GetHttpVarNames()
  {
    return $this->http_var_names;
  }	   

  function SetHttpVarValues($newvalue)
  {
    $this->http_var_values = $newvalue;
  }	   

  function GetHttpVarValues()
  {
    return $this->http_var_values;
  }	   
  /* Remove these functions in the long run, END */

  function SetWidthFromLeft($newvalue)
  {
    $this->width_from_left = $newvalue;
  }	   

  function GetWidthFromLeft()
  {
    return $this->width_from_left;
  }	   

  function SetLastChild($newvalue)
  {
    $this->last_child = $newvalue;
  }	   

  function GetLastChild()
  {
    return $this->last_child;
  }

  function GetCssClass()
  {
    return $this->cssclass;
  }	   

  function SetCssClass($newvalue)
  {
    $this->cssclass = $newvalue;
  }	   

  function GetExternUrl()
  {
    return $this->extern_url;
  }	   

  function SetExternUrl($newvalue, $new_browser_window = true)
  {
    $this->extern_url = $newvalue;
    $this->extern_url_new_browser_window = $new_browser_window;
  }	   

  function GetIsCheckBox()
  {
    return $this->ischeckbox;
  }	   

  function SetIsCheckBox($newvalue)
  {
    if ($newvalue == true)
      $this->isradiobutton = false;

    $this->ischeckbox = $newvalue;
  }	   

  function GetCheckBoxIsChecked()
  {
    return $this->checkboxischecked;
  }	   

  function SetCheckBoxIsChecked($newvalue)
  {
    $this->checkboxischecked = $newvalue;
  }

  function GetIsRadioButton()
  {
    return $this->isradiobutton;
  }	   

  function SetIsRadioButton($newvalue)
  {
    if ($newvalue == true)
      $this->ischeckbox = false;

    $this->isradiobutton = $newvalue;
  }

  function GetRadioButtonIsSelected()
  {
    return $this->radiobuttonisselected;
  }	   

  function SetRadioButtonIsSelected($newvalue)
  {
    $this->radiobuttonisselected = $newvalue;

    if ($this->objTreeView != null and $newvalue == true)
      $this->objTreeView->DeselectOtherRadioButtonsWithSameParent($this);	
  }

  function GetAlwaysOpened()
  {
    return $this->alwaysopened;
  }	   

  function SetAlwaysOpened($newvalue)
  {
    $this->SetOpened($newvalue);
    $this->alwaysopened = $newvalue;
  }

  function GetAlwaysClosed()
  {
    return $this->alwaysclosed;
  }	   

  function SetAlwaysClosed($newvalue)
  {
    $this->SetOpened($newvalue);
    $this->alwaysclosed = $newvalue;
  }
	
  function SetFrameUrl($url, $frame)
  {
    $this->frame_url = $url;
    $this->frame_target = $frame;
  }

  function GetFrameUrl(){
    return $this->frame_url;
  }

  function GetFrameTarget(){
    return $this->frame_target;
  }
	
  function SetOnMouseOverUnderline($onmouseover_underline)
  {
    $this->onmouseover_underline = $onmouseover_underline;
  }
	
  function GetOnMouseOverUnderline()
  {
    return $this->onmouseover_underline;
  }
	
  function SetUnderline($underline)
  {
    $this->underline = $underline;
  }
	
  function GetUnderline()
  {
    return $this->underline;
  }

  function SetFontColor($font_color)
  {
    $this->font_color = $font_color;
  }
	
  function GetFontColor()
  {
    return $this->font_color;
  }
  
  function SetToolTip($tip){
    $this->toolTip=$tip;
  }

  function GetToolTip(){
    return $this->toolTip;
  }
}
?>