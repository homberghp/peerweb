<?php
  /**
   * mini task_timer
   */
require_once 'component.inc';
$peer_id=$_SESSION['peer_id'];
require_once 'tasktimer.inc';
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