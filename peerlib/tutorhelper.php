<?php
/*  $Id: tutorhelper.php 1826 2014-12-27 15:01:13Z hom $ */
require_once 'guessers.php';
require_once 'validators.php';
require_once 'prjMilestoneSelector2.php';
require_once 'ClassSelectorClass.php';
$isTutor = false;
$snummer = 0;
$class_id = 0;
$projectclass = 'project';
$tuhe_prjm_id = 0;
$tuhe_prjtg_id = 1;
$asTutor = false;
if (isSet($_SESSION['tutor_code'])) {
    $isTutor = ($_SESSION['tutor_code'] != '');
}
$as_student = false;
if ($isTutor && isSet($_POST['as_student_request'])) {
    $_SESSION['as_student'] = (isSet($_POST['as_student']));
}
extract($_SESSION);
if (!$isTutor) {
    // hack to prevent spoofing via url
    $_SESSION['snummer'] = $_REQUEST['snummer'] = $judge = $snummer = $peer_id;
} else if ($tutor_code != '') {
    if ($as_student) {
        $_SESSION['snummer'] = $_REQUEST['snummer'] = $judge = $snummer = $peer_id;
    }
    // assume tutor, get tutor data
    //    phpinfo(INFO_VARIABLES);
    $sql = "select * from tutor join student on (userid=snummer) where tutor='$tutor_code'";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        echo('cannot get tutor data:' . $dbConn->ErrorMsg() . ' with ' . $sql);
        stacktrace(1);
        die();
    }
    if (!$resultSet->EOF) {
        extract($resultSet->fields, EXTR_PREFIX_ALL, 'tutor');
    }
}

// store post data in SESSION if tutor
if ($isTutor) {
    $tuhe_prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $tuhe_prjm_id, 'tuhe_prjm_id');
    extract($tuhe_prjSel->getSelectedData(), EXTR_PREFIX_ALL, 'tuhe');
    $_SESSION['tuhe_prjm_id'] = $tuhe_prjm_id;
//   $dbConn->log("$tuhe_prjm_id, $tuhe_prjm_id; ");
//   $dbConn->log($tuhe_prjSel->getQuery());
    if (!isSet($_REQUEST['snummer']) && !isSet($_SESSION['snummer'])) {
        // if no student is known, make a set of guesses
        $_REQUEST['snummer'] = $_SESSION['snummer'] = $snummer = guessStudentForTutor($dbConn, $peer_id);
        $_SESSION['class_id'] = $class_id = guessClassFromStudent($dbConn, $snummer);
        list( $_SESSION['tuhe_prj_id'], $_SESSION['tuhe_milestone'], $_SESSION['tuhe_prjtg_id']) =
                explode(':', $_SESSION['tuhe_prj_id_milestone'] =
                guessProjectFromStudent($dbConn, $snummer));
        echo guessProjectFromStudent($dbConn, $snummer);
        $tuhe_prj_id = $_SESSION['tuhe_prj_id'];
        $tuhe_milestone = $_SESSION['tuhe_milestone'];
    } else if (isSet($_REQUEST['snummer'])) {
        $snummer = $_SESSION['snummer'] = validate($_REQUEST['snummer'], 'snummer', 2032227);
        if (!isInClass($dbConn, $class_id, $snummer)) {
            $_REQUEST['class_id'] = $_SESSION['class_id'] = $class_id = guessClassFromStudent($dbConn, $snummer);
            list( $_SESSION['tuhe_prj_id'], $_SESSION['tuhe_milestone']) =
                    explode(':', $_SESSION['tuhe_prj_id_milestone'] =
                    guessProjectFromStudent($dbConn, $snummer, $tuhe_prj_id, $tuhe_milestone));
        }
    }

    if (isSet($_REQUEST['projectclass'])) { // change event
        $projectclass = $_SESSION['projectclass'] = ($_REQUEST['projectclass'] == 'project') ? 'project' : 'class';
    } else if (!isSet($_SESSION['projectclass'])) {
        // set default to class
        $projectclass = $_SESSION['projectclass'] = 'project';
    }
    //    if ( $projectclass == 'class' ) {
    if (isSet($_REQUEST['class_id'])) {
        $class_id = validate($_REQUEST['class_id'], 'integer', $_SESSION['class_id']);
        if ($class_id != $_SESSION['class_id']) {
            $_SESSION['class_id'] = $class_id;
            if (!isInClass($dbConn, $class_id, $snummer)) {
                $_REQUEST['snummer'] = $_SESSION['snummer'] = $snummer = guessStudentFromClass($dbConn, $class_id);
                list( $_SESSION['tuhe_prj_id'], $_SESSION['tuhe_milestone'], $_SESSION['tuhe_prjtg_id']) =
                        explode(':', $_SESSION['tuhe_prj_id_milestone'] =
                        guessProjectFromStudent($dbConn, $snummer, $tuhe_prj_id, $tuhe_milestone));
            }
        }
    }

    $_SESSION['snummer'] = $_SESSION['judge'] = $snummer;
    $tutor = $tutor_code;

    /* if (! isSet($_SESSION['prjtg_id'])){ */
    /*     $_REQUEST['prjtg_id']=$_SESSION['prjtg_id'] = */
    /*     guessPrjTgForStudent($dbConn,$snummer,$prjm_id); */
    /*     echo "guessed prjtg_id"; */
    /* } */
} // if ($isTutor)

$page_opening = '';
$sql = "select * from student where snummer=$snummer";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    echo "cannot get student data with <pre>$sql</pre>\n Error <pre>" . $dbConn->ErrorMsg() . "</pre>";
} else if (!$resultSet->EOF) {
    extract($resultSet->fields);
}
$lang = strtolower($lang);
/*
 * create selector for tutor
 */

function tutorHelper($dbConn, $isTutor) {
    global $projectclass;
    if ($isTutor) {
        if (isSet($_SESSION['projectclass']) && ($_SESSION['projectclass'] == 'project' ))
            tutorHelper2($dbConn, $isTutor);
        else
            tutorHelper1($dbConn, $isTutor);
    } else {
    }
}

function tutorHelperLayout($midForm, $rightForm) {
    global $projectclass;
    global $PHP_SELF;
    global $as_student;
    global $tutor_roepnaam, $tutor_achternaam, $tutor_tussenvoegsel;
    $classchecked = ($projectclass == 'class') ? 'checked' : '';
    $projectchecked = ($projectclass == 'project') ? 'checked' : '';
    if (!hasCap(CAP_IMPERSONATE_STUDENT))
        return;
    if ($as_student) {
        $leftForm = $midForm = $rightForm = '&nbsp;';
    } else {
        $leftForm = "<form method='get' style='display:inline' name='scp' action='$PHP_SELF'>\n" .
                "<label for='projectclass1'>by class</label><input type='radio' id='projectclass1' name='projectclass' value='class' $classchecked onchange='submit()' title='to a select student by class'/>\n" .
                "<label for='projectclass2'>by project</label><input type='radio' id='projectclass2' name='projectclass' value='project' $projectchecked  onchange='submit()' title='to select a student by project'/>\n" .
                "</form>\n";
    }
    ?>
    <!-- tutorhelper start -->
    <div class='tutorhelper' style='border-width:3px; border-color: white; width:100%;'>
        <div class='layout navopening' width='100%' summary='tutor helper'>
            <form method='post' width='100%' name='tutor_wants_as_student' action='<?= $PHP_SELF; ?>'>
                Welcome tutor <?= $tutor_roepnaam ?> 
                <?= $tutor_tussenvoegsel ?> <?= $tutor_achternaam ?>
                <input align='center' type='checkbox' id='as_student' name='as_student' value='yes' <?= (($as_student == 'yes') ? 'checked' : '') ?> 
                       onChange='submit()' title='If you want student functionality for yourself'/>
                <label for='as_student'>as student</label><input type='hidden' name='as_student_request' value='unimportant'/></form>
            <?php if (!$as_student) { ?>
            <div class='nix'  style='width: 100%;'>
                <div class='nix' style='display:inline'>
                        <?= $leftForm ?>
                    </div>
                    
                    <div class='nix' style='display:inline'>
                        <?= $midForm ?>
                    </div>
                    <div class='nix' style='display:inline'>
                        <?= $rightForm ?>
                    </div>
            </div><?php } ?>
        </div></div>
        <!-- tutorhelper end -->
        <?php
    }

// class helper
    function tutorHelper1($dbConn, $isTutor) {
        global $projectclass;
        global $PHP_SELF, $_SESSION;
        global $as_student;
        global $tutor_roepnaam, $tutor_achternaam, $tutor_tussenvoegsel;
        $class = $_SESSION['class_id'];
        $snummer = $_SESSION['snummer'];
        if ($isTutor && hasCap(CAP_IMPERSONATE_STUDENT)) {
            $class_id = $_SESSION['class_id'];
            $classSel = new ClassSelectorClass($dbConn,$class_id);
            $snummer = $_SESSION['snummer'];
            $sql = "select achternaam||', '||roepnaam||coalesce(' '||tussenvoegsel,'') as name,\n"
                    . "snummer as value, c.class_id as namegrp\n"
                    . "from student s "
                    . " left join student_class c using(class_id) \n"
                    . " join faculty f on(f.faculty_id=s.faculty_id)\n"
                    . "where c.class_id='$class_id' order by name";
            $judgeSelector = "<select name='snummer' onchange='submit()' title='student in this class'>\n" .
                    getOptionListGrouped($dbConn, $sql, $snummer) . "</select>";
            $classSelector = $classSel->addConstraint('sort1 < 10 and student_count <>0')
                    ->setSelectorName('class_id')
                    ->getSelector();
            $classchecked = ($projectclass == 'class') ? 'checked' : '';
            $projectchecked = ($projectclass == 'project') ? 'checked' : '';
            $leftForm = "<form method='get' name='class_id' style='display:inline' action='$PHP_SELF'>\nClass: $classSelector&nbsp;" .
                    "<input type='submit' value='get' title='press me if you have only one class'/>\n</form>\n";
            $rightForm = "<form class='navopening' style='display:inline' method='get' name='student' action='$PHP_SELF'>Student&nbsp;$judgeSelector<input type='submit' name='getstudent' value='Get student'/>\n</form>\n";
        } else {
            $leftForm = $rightForm = '&nbsp;';
        }
        tutorHelperLayout($leftForm, $rightForm);
    }

    /* tutorhelper1 */
    /*
     * selector, this time based on projects
     */

    function tutorHelper2($dbConn, $isTutor) {
        global $projectclass;
        global $PHP_SELF;
        global $_SESSION;
        global $as_student;
        global $tutor, $tutor_roepnaam, $tutor_achternaam, $tutor_tussenvoegsel, $tuhe_prjm_id, $peer_id, $tuhe_prjSel;
        if ($isTutor && hasCap(CAP_IMPERSONATE_STUDENT)) {
            //    if ($isTutor) {
            if (!isSet($_SESSION['class_id'])) {
                $sql = "select class_id,min(snummer) as snummer from student_class_v\n" .
                        "\twhere class_id=(select min(class_id) as class_id from student_class_v)\n" .
                        " group by class_id limit 1";
                $resultSet = $dbConn->Execute($sql);
                if ($resultSet === false) {
                    die('cannot get student,class data:' . $dbConn->ErrorMsg() . ' with ' . $sql);
                }

                extract($resultSet->fields);
                $_SESSION['class_id'] = $class_id;
                $_SESSION['judge'] = $_SESSION['snummer'] = $snummer;
            } else {
                $class_id = $_SESSION['class_id'];
                $snummer = $_SESSION['snummer'];
            }
            $sql = "select s.achternaam||', '||s.roepnaam||coalesce(' '||s.tussenvoegsel,'')||coalesce(' : '||pr.short,'')  as name,\n" .
                    "s.snummer as value, 'g'||pt.grp_num||coalesce(': '||ga.alias,'')||' / '||t.tutor as namegrp\n" .
                    "from student s natural join prj_grp pg \n" .
                    " join prj_tutor pt using(prjtg_id)\n" .
                    " join tutor t on(userid=tutor_id)\n" .
                    "join prj_milestone using(prjm_id)\n " .
                    " left join grp_alias ga using(prjtg_id)\n" .
                    " left join student_role sr using(prjm_id,snummer) left join project_roles pr using(prj_id,rolenum)\n" .
                    "where prjm_id='$tuhe_prjm_id' order by grp_num,name";
            $judgeSelector = "<select name='snummer' onchange='submit()' title='select a student in this project'>\n" .
                    getOptionListGrouped($dbConn, $sql, $snummer) . "</select>";
            $isAdmin = hasCap(CAP_SYSTEM) ? 'true' : 'false';
            $projectSelector = $tuhe_prjSel->getSelector();
            $classchecked = ($projectclass == 'class') ? 'checked' : '';
            $projectchecked = ($projectclass == 'project') ? 'checked' : '';
            $leftForm = "<form method='get' style='display:inline' name='tuhe_prjm_id_mil' action='$PHP_SELF'>Project $projectSelector&nbsp;"
                    . "<input type='submit' value='get' title='press me if you have only one project'/>\n</form>\n";
            $rightForm = "<form method='get' style='display:inline' name='student' action='$PHP_SELF'>Student&nbsp;:$judgeSelector <input type='submit' name='getstudent' value='Get student'/>\n</form>\n";
        } else {
            $leftForm = $rightForm = '&nbps';
        }
        tutorHelperLayout($leftForm, $rightForm);
    }

    /* tutorhelper2 */

    function tutorhelperplaceholder($dbConn, $isTutor) {
        global $_SESSION;
        global $student_name;
        global $student_class_id;
        if (isSet($_SESSION['snummer']))
            $snummer = $_SESSION['snummer'];
        else
            $snummer = 0;
        $sql = "select rtrim(roepnaam)||' '||rtrim(coalesce(tussenvoegsel,''))||' '||achternaam as name, class_id as class\n" .
                "from student where snummer=$snummer";
        $resultSet = $dbConn->Execute($sql);
        if ($resultSet === false) {
            die('cannot get student,class data:' . $dbConn->ErrorMsg() . ' with ' . $sql);
        }
        if (!$resultSet->EOF) {
            extract($resultSet->fields, EXTR_PREFIX_ALL, 'student');
        } else {
            $student_class_id = $student_name = 'Unknown';
        }
        ?>
        <div class='nav' style='background:#eee;'>
            <table align='center' border='0'>
                <tr>
                    <th><i>You are watching this page for student</i> <span style='color:#070'><?= $student_name ?></span> </th>
                    <th><i>Class</i> <span style='color:#070'><?= $student_class_id ?></span></th>
                </tr></table>
        </div>
        <?php
    }

    /* tutorhelperplaceholder */
    ?>
