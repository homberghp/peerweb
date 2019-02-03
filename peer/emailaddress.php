<?php
/* $Id: emailaddress.php 1792 2014-09-15 11:51:29Z hom $ */
requireCap(CAP_DEFAULT);
require_once('tutorhelper.php');
require_once 'navigation2.php';

function checkEmail($adr) {
    if (preg_match("/^\w+(\w|\-|\.)*\@[a-zA-Z0-9][a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/", $adr)) {
        return true;
    } else {
        echo 'Email address ' . $adr . " has an invalid format.<br/>\n";
    }
    return false;
}

//$snummer=$peer_id; // this page is always personal
$sql = "select snummer,roepnaam,tussenvoegsel,achternaam,email1 \n" .
        "from student_email left join alt_email using(snummer) where snummer=$snummer";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    die('Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql);
}
extract($resultSet->fields);
extract($resultSet->fields, EXTR_PREFIX_ALL, 'stud');

$page_opening = "Personal settings for $roepnaam $tussenvoegsel $achternaam ($snummer)";
$page = new PageContainer();
$page->setTitle('Personal settings');
//$page->addHeadComponent(new HtmlContainer("<script id='tasktimerstarter' type='text/javascript'>"));

$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);

//$nav->addLeftNavText(file_get_contents('news.html'));
ob_start();
tutorHelper($dbConn, $isTutor);
$page->addBodyComponent(new Component(ob_get_clean()));

$page->addBodyComponent($nav);
//ob_start();
//echo "<pre>";print_r($_POST);echo "</pre>";
if (isSet($_POST['lpi_id']) && preg_match('/^LPI\d{9}$/ ', $_POST['lpi_id'])) {

    $sql = "select snummer from lpi_id where snummer=$snummer";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        echo "cannot get lpi_data with $sql, error " . $dbConn->ErrorMsg();
    }
    $lpi_id = $_POST['lpi_id'];
    if ($resultSet->EOF) {
        $sql = "insert into lpi_id (snummer,lpi_id) values($snummer,'$lpi_id')";
    } else {
        $sql = "update lpi_id set lpi_id='$lpi_id' where snummer=$snummer";
    }
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        echo "cannot adapt email address with $sql, error " . $dbConn->ErrorMsg();
    }
}
if (isSet($_POST['github_id']) && preg_match('/^(\w|-)+$/ ', $_POST['github_id'])) {

    $sql = "select snummer from github_id where snummer=$snummer";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        echo "cannot get github data with $sql, error " . $dbConn->ErrorMsg();
    }
    $github_id = $_POST['github_id'];
    if ($resultSet->EOF) {
        $sql = "insert into github_id (snummer,github_id) values($snummer,'$github_id')";
    } else {
        $sql = "update github_id set github_id='$github_id' where snummer=$snummer";
    }
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        echo "cannot adapt github id with $sql, error " . $dbConn->ErrorMsg();
    }
}

if (false && isSet($_POST['bsubmit_student_data'])) {
    $snummer_student_data = validate($_POST['snummer_student_data'], 'snummer', $snummer);
    $sql = "select * from student_email where snummer=$snummer_student_data";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        echo "cannot fetch student date with $sql, error " . $dbConn->ErrorMsg();
    }
    extract($resultSet->fields, EXTR_PREFIX_ALL, 'old');
    $phone_home = validate($_POST['new_phone_home'], 'phone_number', $old_phone_home);
    $phone_gsm = validate($_POST['new_phone_gsm'], 'phone_number', 0);
    $phone_postaddress = validate(trim($_POST['new_phone_postaddress']), 'phone_number', 0);
    if ($phone_gsm == 0)
        $phone_gsm = 'null';
    else
        $phone_gsm = '\'' . $phone_gsm . '\'';
    if ($phone_postaddress == 0)
        $phone_postaddress = 'null';
    else
        $phone_postaddress = '\'' . $phone_postaddress . '\'';
    $sqlhead = "update student_email set phone_home='{$phone_home}', phone_gsm=${phone_gsm},\n" .
            "phone_postaddress=${phone_postaddress}\n";
    $sqltail = "where snummer={$snummer_student_data};";
    if (isSet($_POST['straat'])) {
        $straat = $_POST['straat'];
        $sqlhead .= ", straat='$straat'\n";
    }
    if (isSet($_POST['plaats'])) {
        $plaats = $_POST['plaats'];
        $sqlhead .= ", plaats='$plaats'\n";
    }
    if (isSet($_POST['huisnr'])) {
        $huisnr = pg_escape_string($_POST['huisnr']);
        $sqlhead .= ", huisnr='$huisnr'\n";
    }
    if (isSet($_POST['pcode'])) {
        $pcode = pg_escape_string($_POST['pcode']);
        $sqlhead .= ", pcode='$pcode'\n";
    }
    if (isSet($_POST['hoofdgrp'])) {
        $hoofdgrp = $_POST['hoofdgrp'];
        $sqlhead .= ", hoofdgrp='$hoofdgrp'\n";
    }
    if (isSet($_POST['email1'])) {
        $email1 = validate($_POST['email1'], 'email', $old_email1);
        $sqlhead .= ", email1='$email1'\n";
    }

    if (isSet($_POST['achternaam'])) {
        $sqlhead .= ", achternaam='" . pg_escape_string($_POST['achternaam']) . "'\n";
    }
    if (isSet($_POST['voorletters'])) {
        $sqlhead .= ", voorletters='" . pg_escape_string($_POST['voorletters']) . "'\n";
    }
    if (isSet($_POST['tussenvoegsel'])) {
        $sqlhead .= ", tussenvoegsel='" . pg_escape_string($_POST['tussenvoegsel']) . "'\n";
    }
    if (isSet($_POST['roepnaam'])) {
        $sqlhead .= ", roepnaam='" . pg_escape_string($_POST['roepnaam']) . "'\n";
    }
    if (isSet($_POST['pcn'])) {
        $sqlhead .= ", pcn='" . validate($_POST['pcn'], 'integer', $snummer) . "'\n";
    }
    if (isSet($_POST['faculty_id'])) {
        $sqlhead .= ", faculty_id='" . $_POST['faculty_id'] . "'\n";
    }
    if (isSet($_POST['lang'])) {
        $sqlhead .= ", lang='" . $_POST['lang'] . "'\n";
    }
    if (isSet($_POST['opl'])) {
        $sqlhead .= ", opl='" . $_POST['opl'] . "'\n";
    }
    if (isSet($_POST['nationaliteit'])) {
        $sqlhead .= ", nationaliteit='" . $_POST['nationaliteit'] . "'\n";
    }
    $sql2 = ";\n";
    if (isSet($_POST['student_class_id'])) {
        $student_class_id = trim($_POST['student_class_id']);
        $sql2 .= "update _email set class_id='$student_class_id' where snummer=$snummer_student_data\n";
    }

    $sql = $sqlhead . $sqltail . $sql2 . ";\ncommit";
    //    $dbConn->log($sql);
    $resultSet = $dbConn->executeCompound($sql);
}

$sql = "select s.snummer,rtrim(s.achternaam) as achternaam,\n" 
    . "s.tussenvoegsel,\n" 
    . "rtrim(s.roepnaam) as roepnaam,\n" 
    . "rtrim(s.straat) as straat,\n" 
    . "s.huisnr,s.pcode,s.plaats,\n" 
    . "s.email1,coalesce(ae.email3,'') as email3, rtrim(s.nationaliteit) as nationaliteit,"
    . "s.hoofdgrp,s.cohort,s.pcn,s.opl,s.sex,s.gebdat,\n" 
    . "rtrim(s.phone_home) as phone_home,rtrim(s.phone_gsm) as phone_gsm,rtrim(s.phone_postaddress) as phone_postaddress, s.lang,\n" 
    . "acd.course_description as tweede_opl,\n" 
    . "s.class_id,\n" 
    . "s.faculty_id,lpi_id,\n" 
    . "slb.achternaam||', '||slb.roepnaam||coalesce(' '||slb.tussenvoegsel,'') as study_coach\n, gi.github_id,stick.stick as stick " 
    . " from student_email s left join alt_email ae using(snummer) \n" 
    . " left join github_id gi on(gi.snummer=s.snummer) "
    . " left join sebi_stick stick  on (s.snummer=stick.snummer) "
    . " left join additional_course_descr acd on(s.snummer=acd.snummer) "
    . "left join lpi_id lid on(s.snummer=lid.snummer) left join student_email slb on(slb.snummer=s.slb) where s.snummer=$snummer";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    echo "cannot read email address with<pre>$sql</pre>, error " . $dbConn->ErrorMsg();
}
extract($resultSet->fields);
$phone_home = trim($phone_home);
$phone_gsm = trim($phone_gsm);
$huisnr = trim($huisnr);
$plaats = trim($plaats);
$pcode = trim($pcode);
$hoofdgrp = trim($hoofdgrp);
$slb = trim($study_coach);
$email1f = $email1 = trim($email1);
$name = $roepnaam . ' ' . $tussenvoegsel . ' ' . $achternaam;
$lpi_id_field = "<input type='text' name='lpi_id' value ='$lpi_id' size='12'/>";
$github_id_field = "<input type='text' name='github_id' value ='$github_id' size='24'/>";
$photo = PHOTOROOT . '/' . $snummer . '.jpg';
$sebi_stick="SEBI{$stick}";
//$dbConn->log($photo);
if (!file_exists('fotos/' . $snummer . '.jpg'))
    $photo = '';
if (hasCap(CAP_ALTER_STUDENT)) {

    $achternaam = "<input type='text' name='achternaam' value='$achternaam' size='20'/>";
    $roepnaam = "<input type='text' name='roepnaam' value='$roepnaam' size='20'/>";
    $tussenvoegsel = "<input type='text' name='tussenvoegsel' value=\"$tussenvoegsel\" size='10'/>";
    $voorletters = "<input type='text' name='voorletters' value='$voorletters' size='8'/>";
    $straat = "<input type='text' name='straat' value='$straat' size='20'/>";
    $huisnr = "<input type='text' name='huisnr' value='$huisnr' size='5' />";
    $plaats = "<input type='text' name='plaats' value='$plaats' size='20' />";
    $pcode = "<input type='text' name='pcode' value='$pcode' size='8'/>";
    $pcn = "<input type='text' name='pcn' value='$pcn' size='6'/>";
    $cohort = "<input type='text' name='pcn' value='$cohort' size='4'/>";
    $class_id = "<select name='class_id'>" .
            getOptionListGrouped($dbConn, "select distinct rtrim(faculty_short)||':'||rtrim(sclass) as name,\n" .
                    "c.class_id as value,sclass,faculty_short as namegrp\n" .
                    " from student_class c join class_size cs on(c.class_id=cs.class_id) " .
                    "join faculty f on(c.faculty_id=f.faculty_id) \n" .
                    "order by namegrp,name,value", $class_id) .
            "</select>";
    $faculteit = "<select name='faculty_id'>\n" .
            getOptionList($dbConn, "select distinct faculty_id||': '||rtrim(faculty.full_name) as name,faculty_id as value\n" .
                    " from faculty \n" .
                    "order by name,value ", $faculty_id) .
            "</select>";
    $course_description = "<select name='opl'>" .
            getOptionListGrouped($dbConn, "select distinct rtrim(course_description) as name,\n" .
                    "course as value,\n" .
                    "i.faculty_short as namegrp\n" .
                    " from fontys_course " .
                    "join faculty i using(faculty_id) \n" .
                    "order by namegrp,name,value ", $opl) .
            "</select>";
    $nationaliteit = "<select name='nationaliteit'>" .
            getOptionList($dbConn, "select name,value from nationality", $nationaliteit) .
            "</select>";
    //$hoofdgrp="<input type='text' name='hoofdgrp' value='$hoofdgrp' size='6' />";
    $hoofdgrp = "<select name='hoofdgrp'>" .
            getOptionListGrouped($dbConn, "select distinct rtrim(hoofdgrp) as name,\n" .
                    "rtrim(hoofdgrp) as value,f.faculty_short as namegrp\n" .
                    " from student_email s left join \n" .
                    "student_class c on (hoofdgrp=sclass) " .
                    "join faculty f on(c.faculty_id=f.faculty_id) \n" .
                    "order by namegrp,name,value ", $hoofdgrp) .
            "</select>";
    $email1f = "<input type='text' name='email1' value='$email1' size='50' />";
    $lang = "<select name='lang'>" . getOptionList($dbConn, "select language as name,lang_code as value from uilang", $lang) . "</select>";
}
if (isSet($tweede_opl)) {
    $tweede_opl = "<tr><th align='left'>Tweede opl</th><td>" . $tweede_opl . "</td></tr>\n";
} else
    $tweede_opl = '';
ob_start();
?>
<div style='padding:1em'>
    <fieldset><legend>These email addresses will be used for notifications</legend>
        <form name='email' method='post' action='<?= $PHP_SELF ?>'>
            <table summary='email address'>
                <tr><th colspan='2'>Email addresses:</th></tr>
                <tr><th  align='right'>Fontys email address </th><td><?= $email1 ?></td><td>&nbsp;</td></tr>
                <tr><th  align='right'>Linux Prof Inst. id LPI_ID</th><td><?= $lpi_id_field ?>(Used for Linux Professional Institute certificates LPI-101 etc.)</td></tr>
                <tr><th align='right'>Github ID</th><td><?= $github_id_field ?>(Used for sem 7 ESD course.)</td></tr>
    <tr><th align='right'>Sebi Stick Number</th><td><b><?= $sebi_stick ?></b>&nbsp;(You Exam preparation and exercise stick.)</td></tr>
                <tr><td>&nbsp;</td><td><input type='submit' name='bsubmit' value='update'/></td></tr>
            </table>
        </form>
        <p>The email address you enter here will be used to notify you that an email has arrived on your student email address.
            By entering an empty line here (erasing the current value) you delete that current entry.
        </p>
    </fieldset>
<?php include 'templates/student.html' ?>
</div>
<!-- db_name=<?= $db_name ?> -->
<?php
$page->addBodyComponent(new Component(ob_get_clean()));
$page->show();
?>
