<?php
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
				//$_SESSION[$treeid]->SetSelectedNodeId($_GET["nodeid"]);
				$_SESSION[$treeid]->HttpUpdateNodeById($_GET["nodeid"]);
				
			}
		}
	}
?>

