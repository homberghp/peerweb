<?php

function AjaxUpdateSelectedNode($treeid, $nodeid = -1)
{
	$objResponse = new xajaxResponse();

	if ($_SESSION[$treeid] != null)
	{
		if ($nodeid != -1)
			$_SESSION[$treeid]->HttpUpdateNodeById($nodeid);
		
		$_SESSION[$treeid]->CreateTreeView();
	
		$objResponse->addAssign($treeid, "innerHTML", $_SESSION[$treeid]->treeview_string);
		
		if ($_SESSION[$treeid]->showOnClickInformation == true)
		{
			if (array_key_exists($nodeid, $_SESSION[$treeid]->Nodes) == true)
				$objResponse->addAssign($treeid."OnClickInformation", "innerHTML", $_SESSION[$treeid]->GetOnClickInformation($nodeid));
		}
	}
	
	return $objResponse;
}

function AjaxUpdateNodesCheckBoxValues($treeid, $nodeid)
{
	$objResponse = new xajaxResponse();

	if ($_SESSION[$treeid]->Nodes[$nodeid]->GetCheckBoxIsChecked() == false)
		$_SESSION[$treeid]->Nodes[$nodeid]->SetCheckBoxIsChecked(true);
	else
		$_SESSION[$treeid]->Nodes[$nodeid]->SetCheckBoxIsChecked(false);
	
	return $objResponse;
}


function AjaxUpdateNodesRadioButtonValues($treeid, $nodeid)
{
	$objResponse = new xajaxResponse();

	$nodeskeys = array();
	$nodeskeys = array_keys($_SESSION[$treeid]->Nodes);
	$parentid = $_SESSION[$treeid]->Nodes[$nodeid]->GetParentId();

	foreach ($nodeskeys as $key)
	{
		//Desselect all radiobuttons in same folder.
		if ($_SESSION[$treeid]->Nodes[$key]->GetParentId() == $parentid)
			$_SESSION[$treeid]->Nodes[$key]->SetRadioButtonIsSelected(false);
	}
	
	//Select the right radio button.
	$_SESSION[$treeid]->Nodes[$nodeid]->SetRadioButtonIsSelected(true);

	return $objResponse;
}


?>