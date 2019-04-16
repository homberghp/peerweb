<?php
requireCap(CAP_TUTOR);

/**
 * The simple table editor for the tutor
 * @author Pieter van den Hombergh
 * $Id: project_attributes_def.php 1723 2014-01-03 08:34:59Z hom $
 */
require_once("ste.php");

$page = new PageContainer("Project attributes and performance indicators on DB " . $db_name);
$ste = new SimpleTableEditor($dbConn, $page);
$ste->setFormAction(basename(__FILE__))
        ->setRelation('project_attributes_def')
        ->setMenuName('project_attributes')
        ->setKeyColumns(array('project_attributes_def'))
        ->setNameExpression("rtrim(pi_name)")
        ->setListRowTemplate(array('project_attributes_def', 'pi_name', 'author', 'interpretation', 'prj_id'))
        ->setOrderList(array('author', 'pi_name'))
        ->setFormTemplate('../templates/project_attributes_def.html')
        ->show();
?>
