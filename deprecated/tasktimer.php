<?php
  /**
   * mini task_timer
   */
requireCap(CAP_SYSTEM);
require_once 'component.php';
$peer_id=$_SESSION['peer_id'];
require_once 'tasktimer.php';
$page = new PageContainer();
$page->setTitle('Your personal task timer');
$task_div= new HtmlContainer('<div id=\'task_timer_id\' class=\'navopening\'>');
ob_start();
taskTimer($_SESSION['peer_id']);
$task_div->addText("<a href='logout.php' title='logout'><img src='".IMAGEROOT."/close_1.png' border='0' alt='logout'/></a>");
$task_div->addText(ob_get_clean());
$page->addBodyComponent($task_div);
$page->show();
?>