<?php
   require_once 'projectsfor.php';
   require_once 'documentsfor.php';
   require_once 'tasksfor.php';
require_once 'birthdays.php';
?>
<div style='margin:0; padding:0 0.5em 0 0.5em; text-align:left' class='theadright'>
<?php
   projectsFor($dbConn,$snummer);
echo new BirthDaysToDay(); 
documentsFor($dbConn,$snummer);
tasksFor($dbConn,$snummer);
?>
</div>
