<?php

include_once('tutorhelper.php');
require_once('validators.php');
require_once('simplequerytable.php');
include_once'navigation2.php';
require_once 'studentPrjMilestoneSelector.php';
require_once 'TemplateWith.php'; 
$doctype = 0;
$version_limit = 2;
$url = $PHP_SELF;
$filesizelimit = 25*1024*1024;
$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
$prjtg_id = 1;
extract($_SESSION);
$prjSel = new StudentMilestoneSelector($dbConn, $snummer, $prjm_id);
$prjSel->setExtraConstraint(" and prjm_id in (select distinct prjm_id from project_deliverables) and valid_until > now()::date ");

$pp = $prjSel->getSelectedData();
extract($pp);
$pp['filesizelimit'] = $filesizelimit;
$pp['uploadMessage'] = '';
$_SESSION['prjtg_id'] = $prjtg_id;
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;
$_SESSION['grp_num'] = $grp_num;


if (!isset($_SESSION['userfile'])) {
    $_SESSION['userfile'] = '';
}
$sql = "select rtrim(afko) as uafko,year as myear,roepnaam as uroepnaam,\n" .
        " rtrim(email1) as uemail1, rtrim(email2) as uemail2, tussenvoegsel as utussenvoegsel,\n" .
        "achternaam as uachternaam,grp_num as ugrp,description as udescription,prjm_id,prjtg_id \n" .
        "from prj_grp join all_prj_tutor using(prjtg_id) join student using(snummer) left join alt_email using(snummer)\n" .
        "where prjm_id=$prjm_id and snummer=$snummer";
$resultSet = $dbConn->Execute($sql);
//echo $sql;
if ($resultSet === false) {
    die('cannot get project data:' . $dbConn->ErrorMsg() . ' with <pre>' . $sql . "</pre>\n");
}
if (!$resultSet->EOF) {
    extract($resultSet->fields);
}

//echo "$prj_id $milestone $uafko<br/>\n";
// to prevent double uploads e.g. by reload or browser back button test if this and previous file are the same
if (isSet($_FILES['userfile']['name']) && ( $_FILES['userfile']['name'] != '' ) && ($_SESSION['userfile'] != $_FILES['userfile']) && (($doctype = validate($_POST['doctype'], 'integer', 0)) != 0)) {
    $groupread = (isSet($_POST['groupread'])) ? 't' : 'f';
    $projectread = (isSet($_POST['projectread'])) ? 't' : 'f';
    $groupmail = (isSet($_POST['groupmail'])) ? 't' : 'f';
    $rights = '{' . $groupread . ',' . $projectread . '}';

    // compose alle file attributes and decisions.
    $sql = "select coalesce(max(vers),0)+1 as vers,version_limit from "
            . "project_deliverables left join (select vers,doctype,prjm_id from uploads \n"
            . "where snummer=$snummer and doctype=$doctype and prjm_id=$prjm_id) u\n"
            . "using (doctype,prjm_id) where doctype=$doctype and prjm_id=$prjm_id  and version_limit > 0 group by version_limit";

    $resultSet = $dbConn->Execute($sql);
    $dbConn->log($sql);
    if ($resultSet === false) {
        die('cannot get version data:' . $dbConn->ErrorMsg() . ' with ' . $sql);
    }
    if (!$resultSet->EOF) {
        $vers = $resultSet->fields['vers'];
        $version_limit = $resultSet->fields['version_limit'];
        //    echo "vers = $vers<br/>\n";
    } else {
        $vers = 1;
        $version_limit = 0;
    }
    $version_acceptable = ($vers <= $version_limit);
    $basename = sanitizeFilename($_FILES['userfile']['name']);
    $rel_file_dir = $snummer . '/' . $uafko . '_' . $myear . '_M' . $milestone . '/' . $doctype;

    if ($vers > 1) {
        $filename_pieces = explode('.', $basename);
        if (count($filename_pieces) >= 2) {
            $filename_pieces[count($filename_pieces) - 2] .='_V' . $vers;
            $basename = implode('.', $filename_pieces);
        } else {
            $basename .= '_V' . $vers;
        }
    }

    $rel_file_path = $rel_file_dir . '/' . $basename;
    $user_upload_dir = $upload_path_prefix . '/' . $rel_file_dir;
    $user_upload_path = $user_upload_dir . '/' . $basename;
    $file_size = $_FILES['userfile']['size'];
    $tmp_file = $_FILES['userfile']['tmp_name'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type_long = $mime_type = finfo_file($finfo, $tmp_file);
    $title = pg_escape_string($_POST['title']);
    if ($title == '') {
        $title = basename($_FILES['userfile']['name']);
    }

    $formsubject = "Project \$uafko upload by \$uroepnaam \$utussenvoegsel \$uachternaam (\$snummer)";
    $mailbody = "Dear stakeholder,\n\n"
            . "{$uroepnaam} {$utussenvoegsel}{$uachternaam} (student number $snummer) just "
            . "uploaded the document titled "
            . "'\$title' version \$vers."
            . "for the modules \$uafko, \$udescription.\n"
            . "You can find it as the file at\n"
            . "\$url.\n\n"
            . "You should have received access data to the webserver through your fontys email address.\n"
            . "In case these data are missing, go to the page https://www.fontysvenlo.org/login.php "
            . "and request authorisation data on the lower halve of the page\n"
            . "stating your name and student number.\n\n"
            . "Kind regards, the peer web service";
    if ($version_acceptable && ($file_size < $filesizelimit) & ($file_size > 0)) {

        if (!is_dir($user_upload_dir)) { // create dir including parents
            if (!mkdir($user_upload_dir, 0775, true)) {
                die('cannot create dir ' . $user_upload_dir . '<br/>');
            }
        }

        // for jpeg shrink to acceptable size
        if ('image/jpeg' == $mime_type) {
            list($width, $height, $type, $attr) = getimagesize($tmp_file);
            $maxDim = max($width, $height);
            $maxAllowed = 1000;
            $newWidth = ($maxAllowed * $width) / $maxDim;
            $newHeight = ($maxAllowed * $height) / $maxDim;
            if ($maxDim > $maxAllowed) {
                $newSize = $newWidth . 'x' . $newHeight;
                $newImgFilename = $tmp_file . '-resized';
                @`/usr/bin/convert -geometry $newSize $tmp_file $newImgFilename`;
                @`mv $newImgFilename $tmp_file`;
            }
        }

        if (move_uploaded_file($tmp_file, $user_upload_path)) {
            $_SESSION['userfile'] = $_FILES['userfile']; // remember userfile
            $pp['uploadMessage'] = "File is valid, and was successfully uploaded as $rel_file_path.\n";
            $sql = "select nextval('upload_id_seq')";
            $resultSet = $dbConn->execute($sql);
            if ($resultSet === false) {
                die("<br>$dbUser Cannot get sequence next value with $sql " . $dbConn->ErrorMsg() . "<br>");
            }
            $upload_id = $resultSet->fields['nextval'];
            $url = $server_url . $root_url . '/upload_critique.php?doc_id=' . $upload_id;
            $title = substr($title, 0, 64); // precaution for insert
            $sql = "begin work;\n"
                    . "insert into uploads (snummer,doctype,title,vers,rel_file_path,upload_id,mime_type,rights,"
                    . "prjm_id,prjtg_id,mime_type_long,filesize)\n"
                    . " values ($snummer,$doctype, '$title', $vers,'$rel_file_path',$upload_id,'$mime_type','$rights',"
                    . "$prjm_id,$prjtg_id,'$mime_type_long',$file_size);\n";
            $sql .= "insert into document_author (upload_id,snummer)\n"
                    . " select $upload_id,$snummer \n"
                    . "where ($upload_id,$snummer) not in (select upload_id,snummer from document_author);\n";
            if (isSet($_POST['coauthor'])) {
                $coauthors = implode(",", $_POST['coauthor']);
                $sql .= "insert into document_author (upload_id,snummer)\n"
                        . " select $upload_id,snummer from student \n"
                        . "where snummer in ($coauthors) and ($upload_id,snummer) "
                        ."not in (select upload_id,snummer from document_author);\n";
            }
            $sql .= "commit;";
            $dbConn->log($sql);
            $resultSet = $dbConn->Execute($sql);
            if ($resultSet === false) {
                die('cannot store upload data:' . $dbConn->ErrorMsg() . ' with <pre>' . $sql . "</pre>\n");
            }
            // mail stakeholders: tutor and group members
            $sql = "select distinct rtrim(s.email1,' ') as email1 ,\n"
                    . " tm.email1 as tutor_email from \n"
                    . " student s join prj_grp pg1 using(snummer) join all_prj_tutor apt using(prjtg_id)\n"
                    . "join student tm on(apt.tutor_id=tm.snummer)\n"
                    . "where prjtg_id=$prjtg_id ";
            // unless only student is to be warned/  confirmed.
            if ($groupmail != 't') {
                $sql .= " and s.snummer=$snummer\n";
            }
          
            $resultSet3 = $dbConn->Execute($sql);
            if ($resultSet3 === false) {
                die("<br>Cannot update upload document data with <pre>" . $sql . " reason " . $dbConn->ErrorMsg() . "</pre><br/>");
            }
            $to_emails = array();
            while (!$resultSet3->EOF) {
                extract($resultSet3->fields);
                $to_emails[] = $email1;
                if (! in_array($tutor_email, $to_emails)) {
                    $to_emails[] = $tutor_email;
                }
                $resultSet3->movenext();
            }
            $headers = "From: peerweb@fontysvenlo.org\n" .
                    "Content-Type: text/plain; charset=utf-8\n";
            $reply_to = "Reply-To: peerweb@fontysvenlo.org";

            $toAddress = implode(', ',$to_emails);
            $headers .= $reply_to;
            $message= templateWith($mailbody, get_defined_vars());
            $subject= templateWith($formsubject, get_defined_vars());
            domail($toAddress, $subject, $message, $headers); // no mail on personal docs.
        }
    } else {
        $pp['uploadMessage'] = "<span style='color:red;font-weight:bold;'>Upload failed, "
                . "{$_POST['doctype']} possibly empty file ($user_upload_path) OR Version limit (current version limit is $version_limit) exceeded, file not uploaded</span><br/>\n";
    }
}
//$dbConn->log("2 prj_id=$prj_id, milestone=$milestone\n");
$sql = "select description as name, doctype as value,rights \n" .
        "from uploaddocumenttypes udt join prj_milestone pm using(prj_id) join project_deliverables pd using(prjm_id,doctype)\n" .
        "where pm.prjm_id=$prjm_id and version_limit > 0 order by due asc";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    print "error fetching document types with $sql : " . $dbConn->ErrorMsg() . "<br/>\n";
}
$preload = array('0' => array('name' => '&nbsp;', 'value' => '1:1'));
$pp['doctypeSelect'] = "<select name='doctype'>\n" . getOptionList($dbConn, $sql, $doctype) . "\n</select>\n";
// collect the rights in an array and pass it to javascript.
$resultSet = $dbConn->execute($sql);
$rightsArray = array();
$rr = 0;
while (!$resultSet->EOF) {
    extract($resultSet->fields);
    $rightsArray[$rr] = $rights;
    $rr++;
    $resultSet->moveNext();
}
//$dbConn->log("3 prj_id=$prj_id, milestone=$milestone prj_id_milestone=$prj_id_milestone\n");
$sql = "SELECT distinct prj_id||':'||milestone as value, \n" .
        "afko||': '||description||'('||year::text||')'||' milestone '||milestone as name\n" .
        ", prj_id,milestone,afko,  year as namegrp,year, description,grp_num,prj_grp_open,prj_id \n" .
        "FROM prj_grp join all_prj_tutor using(prjtg_id) join project_deliverables using(prjm_id)\n" .
        " where snummer=$snummer  and valid_until > now()::date order by year desc,afko";
//$dbConn->log($sql);
$pp['prjList'] = $prjSel->getSelector();
// get some default prj_id
if (!isSet($prj_id) || $prj_id == 0) {
    $sql = "select prj_id,milestone,prjm_id,prjtg_id from all_prj_tutor join prj_grp using(prjtg_id) where snummer=$snummer limit 1";
    $resultSet = $dbConn->execute($sql);
    if ($resultSet === false) {
        echo ( "<br>Cannot get prj_id, milestone value with <pre>$sql</pre> cause: " . $dbConn->ErrorMsg() . "<br>");
    }
    if (!$resultSet->EOF) {
        extract($resultSet->fields);
        $_SESSION['prj_id'] = $prj_id;
        $_SESSION['milestone'] = $milestone;
        $_SESSION['prjm_id'] = $prjm_id;
        $_SESSION['prjtg_id'] = $prjtg_id;
    }
}
$sql = "select afko as abbriviation,description,year,comment from project where prj_id=$prj_id";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    die('Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql);
}
if (!$resultSet->EOF)
    extract($resultSet->fields);
$page = new PageContainer();
$page->setTitle('Peer (re)viewable portfolio');
$page_opening = "Welcome to the upload page of $roepnaam $tussenvoegsel $achternaam ($snummer)";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);

$nav->addLeftNavText(file_get_contents('news.html'));
ob_start();
tutorHelper($dbConn, $isTutor);
$page->addBodyComponent(new Component(ob_get_clean()));
$page->addBodyComponent($nav);
$ob_start = ob_start();

$sql = "SELECT roepnaam, tussenvoegsel,achternaam,lang FROM student WHERE snummer=$snummer";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    die('Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql);
}
extract($resultSet->fields);
$lang = strtolower($lang);
if (!$prjtg_id) {
    $prjtg_id = 1;
}
$sql = "select udt.description,url as url_link,version_limit as max_document_count,\n"
        . "due as due_date,coalesce(up_count,0) as uploaded\n"
        . " from project_deliverables \n"
        . " join all_prj_tutor using(prjm_id) \n"
        . "join uploaddocumenttypes udt using(prj_id,doctype)\n"
        . "join (select prjtg_id from prj_grp where snummer=$snummer ) pg using(prjtg_id)\n"
        . "left join (select count(*) as up_count, prjm_id,doctype\n"
        . " from uploads where prjtg_id=$prjtg_id group by prjm_id,doctype) au using(prjm_id,doctype)\n"
        . "where prjm_id=$prjm_id and (valid_until > now()::date) and version_limit > 0";
$resultSet = $dbConn->Execute($sql);
$dbConn->log($sql);
if ($resultSet === false) {
    die('cannot get project data:' . $dbConn->ErrorMsg() . ' with <pre>' . $sql . "</pre>");
}
$pp['doc_table'] = "<table border='1' style='border-collapse:collapse'>\n"
        . "<tr><th>Description</th><th title='individual upload count'>max count</th><th title='Uploaded by $uemail1'>uploaded</th><th>Due date</th></tr>";
while (!$resultSet->EOF) {
    extract($resultSet->fields);
    if (isSet($url_link) && $url_link !== '') {
        $description = "<a href='$url_link' target='_blank'>" . $description . "</a>";
    }
    $pp['doc_table'] .= "\t<tr>\n" .
            "\t\t<td>$description</td>\n" .
            "\t\t<td align='right'>$max_document_count</td>\n" .
            "\t\t<td align='right'>$uploaded</td>\n" .
            "\t\t<td>$due_date</td>\n" .
            "\t</tr>\n";
    $resultSet->moveNext();
}
$pp['doc_table'] .= "</table>\n";
$sql = "select count(*) as pd_count from project_deliverables join all_prj_tutor using(prjm_id)\n" .
        "join (select distinct prjtg_id from prj_grp where snummer=$snummer) pg using(prjtg_id)\n" .
        " where prjm_id=$prjm_id\n" .
        " and  (valid_until > now()::date)";
//	    echo '<pre>'.$sql."</pre>\n";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    echo ('Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql);
    stacktrace(1);
    //		die();
}
$pp['pd_count'] = $resultSet->fields['pd_count'];

$pp['coauthor_table'] = "";
if ($prj_id > 1) { // no coauthors for personal project with id==1
    $sql = "select snummer as co, achternaam, roepnaam,coalesce(tussenvoegsel,'')\n" .
            " from student natural join prj_grp \n" .
            " where prjtg_id=$prjtg_id order by achternaam, roepnaam";
    //echo $sql;
    $resultSet = $dbConn->execute($sql);
    if ($resultSet === false) {
        die('Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql);
        $pp['coauthor_table'] = "";
        if ($prj_id > 1) { // no coauthors for personal project with id==1
            $sql = "select snummer as co, achternaam, roepnaam,coalesce(tussenvoegsel,'')\n" .
                    " from student natural join prj_grp \n" .
                    " where prjtg_id=$prjtg_id order by achternaam, roepnaam";
            //echo $sql;
            $resultSet = $dbConn->execute($sql);
            if ($resultSet === false) {
                die('Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql);
            }
            $pp['coauthor_table'] .= "<table border='1' style='background:rgba(255,255,255,0.3);border-collapse:collapse'>\n"
                    . "\t<caption>Co authors of document</caption>\n"
                    . "\t\t<tr><th>snummer</th><th>name</th><th>is co author</th></tr>\n";
            while (!$resultSet->EOF) {
                extract($resultSet->fields);
                $checked = ($snummer == $co) ? 'checked' : '';
                $pp['coauthor_table'] .="<tr><td>$co</td><td>$roepnaam $tussenvoegsel $achternaam</td>\n\t<td><input type='checkbox' name='coauthor[]' value='$co' $checked/></td></tr>\n";
                $resultSet->moveNext();
            }
            $pp['coauthor_table'] .= "<table>\n";
        }
    }
    $pp['coauthor_table'] .= "<table border='1' style='background:rgba(255,255,255,0.3);border-collapse:collapse'>\n"
            . "\t<caption>Co authors of document</caption>\n"
            . "\t\t<tr><th>snummer</th><th>name</th><th>is co author</th></tr>\n";
    while (!$resultSet->EOF) {
        extract($resultSet->fields);
        $checked = ($snummer == $co) ? 'checked' : '';
        $pp['coauthor_table'] .="<tr><td>$co</td><td>$roepnaam $tussenvoegsel $achternaam</td>\n"
                . "\t<td><input type='checkbox' name='coauthor[]' value='$co' $checked/></td></tr>\n";
        $resultSet->moveNext();
    }
    $pp['coauthor_table'] .= "<table>\n";
}
$pp['prjSel'] = $prjSel;
$page->addHtmlFragment('templates/upload.html', $pp);

$page->addBodyComponent(new Component(ob_get_clean()));
//$page->addBodyComponent($dbConn->getLogHtml());
$page->show();
?>
