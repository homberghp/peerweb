<?php
requireCap(CAP_SYSTEM);
require_once('navigation2.php');
require_once 'simplequerytable.php';
require_once 'querytotable.php';
require_once 'validators.php';
$getAll = isSet($_POST['get']) ? 'checked' : '';
$newhoofdgrp = '';
$newclass_id = $oldclass_id = 1;
extract($_SESSION);
if (isSet($_REQUEST['csvout'])) {
    $class_id = $_REQUEST['class_id'];
    $sql = "select trim(sclass) as classname from student_class where class_id=$class_id";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die("<br>Cannot get class name  with " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>");
    }
    $classname = $resultSet->fields['classname'];
    $sql = "select * from student_email where  class_id='$class_id' order by achternaam,roepnaam";
    $filename = $classname . date('-Y-M-d') . '.csv';
    $dbConn->queryToCSV($sql, $filename, ',', true);
    exit(0);
}
$scripts = '<script type="text/javascript" src="jquery/jquery.js"></script>
    <script src="jquery/jquery.tablesorter.js"></script>
    <script type="text/javascript">                                         
      $(document).ready(function() {
           $("#myTable").tablesorter({widgets: [\'zebra\']}); 
      });

    </script>
    <link rel=\'stylesheet\' type=\'text/css\' href=\'' . SITEROOT . '/style/tablesorterstyle.css\'/>
';
pagehead2('Class administration', $scripts);
if (isSet($_REQUEST['oldclass_id'])) {
    $_SESSION['oldclass_id'] = $oldclass_id = $_REQUEST['oldclass_id'];
}

if (isSet($_POST['newhoofdgrp'])) {
    //$newhoofdgrp= preg_replace('/\W+/g','',$_POST['newhoofdgrp']);
    $_SESSION['newhoofdgrp'] = $newhoofdgrp = $_POST['newhoofdgrp'];
}
if (isSet($_POST['sethoofdgrp']) && isSet($newhoofdgrp) && isSet($_POST['studenten'])) {
    $memberset = '\'' . implode("','", $_POST['studenten']) . '\'';
    $sql = "update student_email set hoofdgrp='$newhoofdgrp' " .
            "where snummer in ($memberset)";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die("<br>Cannot update student  with " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>");
    }
}
$class_sql_old =
        "select distinct student_class.sclass||'#'||class_id||' (#'||coalesce(student_count,0)||')'  as name,\n" .
        "class_id as value, " .
        " rtrim(faculty_short) as namegrp,faculty_short\n" .
        " from student_class " .
        "join faculty  using(faculty_id) \n" .
        " join class_size using(class_id) \n" .
        "order by namegrp,name";
$oldClassOptionsList = getOptionListGrouped($dbConn, $class_sql_old, $oldclass_id);

$page_opening = "Move students between student_class.";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);

//echo "<pre>\n";print_r($_REQUEST); echo"</pre>\n";
$nav->show();
?>
<div id='navmain' style='padding:1em;'>
    <p>Normaly this pages is used once or twice a year to assign students to new student_class.
        The other case would be when you move a student out of a class when he or she leaves school.</p>
    <fieldset><legend>Choose students</legend>
        <form method="post" name="participants" action="<?= $PHP_SELF ?>">
            <table class='layout' style='border-width:0;'>
                <tr><th colspan='3'>Old class</th><th colspan='2'>New class</th></tr>
                <tr><td>
                        <select name='oldclass_id'>
                            <?= $oldClassOptionsList ?>
                        </select></td>
                    <td><input type='submit' name='get' value='getAll' title='Get all students selected'/></td>
                    <td><input type='submit' name='getnone' value='getNone' title='Get all students unselected'/></td>
                    <td><input type="reset" name="reset" value="Reset" title="reset form"/></td>
                    <td>
                        <select name='newslb'>
                            <?= $newSLBList ?>
                        </select>
                        <input type="submit" name="sethoofdgrp" value="set slb" title="update hoofdgrp"/></td></tr>
            </table>
            <hr/>

            <?php
            $sql = "SELECT '<input type=\"checkbox\"  name=\"studenten[]\" value=\"'||st.snummer||'\" $getAll/>' as chk,"
                    . "'<a href=\'student_admin.php?snummer='||snummer||'\'>'||st.snummer||'</a>' as snummer,"
                    . "achternaam||', '||roepnaam||coalesce(' '||tussenvoegsel,'') as naam,pcn,"
                    . "sclass as klas,hoofdgrp,cohort,course_short sprogr,studieplan_short as splan,lang,sex,gebdat,"
                    . " land,plaats,pcode\n"
                    . " from student_email st "
                    . "join student_class cl using(class_id)\n"
                    . "natural join studieplan \n"
                    . "left join fontys_course fc on(st.opl=fc.course)\n"
                    . "where class_id='$oldclass_id' "
                    . "order by hoofdgrp,opl,sclass asc,achternaam,roepnaam";
            simpletable($dbConn, $sql, "<table id='myTable' class='tablesorter' summary='your requested data'"
                    . " style='empty-cells:show;border-collapse:collapse' border='1'>");
            ?>
        </form>
    </fieldset>
    <fieldset><legend>Get class list</legend>
        <form method="get" name="classlist" action="<?= $PHP_SELF ?>">
            <table class='layout'>
                <tr><td>Class</td><td>
                        <select name='class_id'>
                            <?= $oldClassOptionsList ?>
                        </select></td><td><button name='csvout' value='Y'>Get CSV (Excel)</button></td>
                </tr>
            </table>
        </form>
    </fieldset>
</div>
</body>
<?php echo "<!-- db_name=" . $db_name . "-->" ?>
</html>
