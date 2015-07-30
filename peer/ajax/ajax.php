<?php

	// Instantiate the xajax object. No parameters defaults requestURI to this page, method to POST, and debug to off
	//if (isset($xajax) == false)
	//$xajax = new xajax(); 
	
	//$xajax->debugOn(); // Uncomment this line to turn debugging on
	//$xajax->outputEntitiesOn(); 
	
	// Specify the PHP functions to wrap. The JavaScript wrappers will be named xajax_functionname
	$xajax->registerFunction("AjaxUpdateSelectedNode");
	$xajax->registerFunction("AjaxUpdateNodesCheckBoxValues");
	$xajax->registerFunction("AjaxUpdateNodesRadioButtonValues");
	
	// Process any requests.  Because our requestURI is the same as our html page,
	// this must be called before any headers or HTML output have been sent
	//$xajax->processRequests();

?>