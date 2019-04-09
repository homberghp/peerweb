<?php
require_once('tutorhelper.php');
require_once('documentfolders.php');
require_once'navigation2.php';
$prjm_id = 0;
extract($_SESSION);

$page = new PageContainer();
$page->setTitle('Personal portfolio');
$page_opening = "Welcome to the portfolio of $roepnaam $tussenvoegsel $achternaam ($snummer)";
$nav = new Navigation($tutor_navtable, basename(__FILE__), $page_opening);
$nav->setInterestMap($tabInterestCount);

$nav->addLeftNavText(file_get_contents('news.html'));
ob_start();
tutorHelper($dbConn, $isTutor);
$page->addBodyComponent(new Component(ob_get_clean()));
$page->addBodyComponent($nav);
ob_start();
?>
<div id="content">
    <?php
    $isTutorBool = $isTutor ? 'true' : 'false';
    $sql = "select rtrim(afko) as afko,apt.description as description,prj_id,year,milestone,\n" .
            "grp_num as author_grp_num,long_name,grp_num as viewergrp,title,rel_file_path,\n" .
            "to_char(uploadts,'YYYY-MM-DD HH24:MI')::text as uploadts,pd.due,rtrim(mime_type) as mime_type,\n" .
            "case when uploadts::date > pd.due then 'late' else 'early' end as late_or_early,\n" .
            "vers,ut.doctype,ut.description as dtdescr,upload_id,($peer_id=u.snummer or $peer_id=aud.reader or $isTutorBool) as link,\n" .
            "snummer, roepnaam,tussenvoegsel,achternaam,sclass as sclass,doc_count,critique_count as crits,u.rights[0:2],\n" .
            "filesize from uploads u \n" .
            "join prj_grp using(prjtg_id,snummer)\n" .
            "join all_prj_tutor apt using(prjtg_id,prjm_id)\n" .
            "join student_email using(snummer)\n" .
            "join student_class using (class_id)\n" .
            "join uploaddocumenttypes ut using(prj_id,doctype)\n" .
            "join project_deliverables pd using(prjm_id,doctype)\n" .
            "join student_upload_count suc using(prj_id,milestone,snummer)\n" .
            "left join document_critique_count on (upload_id=doc_id)\n" .
            "left join (select upload_id,prjm_id,reader,reader_role from document_audience where prjm_id=$prjm_id and reader=$peer_id) aud using(upload_id,prjm_id)" .
            "where snummer=$snummer order by afko,ut.doctype,vers desc";
// echo $sql;
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die('Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql);
    }
    if (is_array($resultSet->fields)) {
        extract($resultSet->fields);
    }
    $lang = strtolower($lang);
    $deliverable_count = 0;
    ?>
    <div style='padding:1em;'>
        <?php
        $isTutorBool = $isTutor ? 'true' : 'false';
        $sql = "select rtrim(afko) as afko,apt.description as description,apt.prj_id,year,apt.milestone,\n" .
                "grp_num as author_grp_num,long_name,grp_num as viewergrp,title,rel_file_path,\n" .
                "to_char(uploadts,'YYYY-MM-DD HH24:MI')::text as uploadts,pd.due,rtrim(mime_type) as mime_type,\n" .
                "case when uploadts::date > pd.due then 'late' else 'early' end as late_or_early,\n" .
                "vers,ut.doctype,ut.description as dtdescr,upload_id,($peer_id=u.snummer or $peer_id=aud.reader or $isTutorBool) as link,\n" .
                "u.snummer, roepnaam,tussenvoegsel,achternaam,sclass as sclass,doc_count,critique_count as crits,u.rights[0:2],\n" .
                "filesize from uploads u \n" .
                "join all_prj_tutor apt using(prjtg_id,prjm_id)\n" .
                "join student_email s on(u.snummer=s.snummer)\n" .
                "join student_class using (class_id)\n" .
                "join uploaddocumenttypes ut using(prj_id,doctype)\n" .
                "join project_deliverables pd using(prjm_id,doctype)\n" .
                "join student_upload_count suc on(ut.prj_id=suc.prj_id and apt.milestone=suc.milestone and suc.snummer=u.snummer)\n" .
                "left join document_critique_count on (upload_id=doc_id)\n" .
                "left join (select upload_id,prjm_id,reader,reader_role from document_audience\n" .
                "where prjm_id=$prjm_id and reader=$peer_id) aud using(upload_id,prjm_id)\n" .
                "where u.snummer=$snummer order by afko,ut.doctype,vers desc";
//        echo "<pre>$sql</pre>\n";
        $resultSet = $dbConn->Execute($sql);
        if ($resultSet === false) {
            die('Error: ' . $dbConn->ErrorMsg() . ' with <pre>' . $sql . "</pre>");
        } else if (!$resultSet->EOF) {
            ?>
            <h2 class='normal'>Your portfolio contains</h2>
            <?= documentFoldersPreExecuted($resultSet) ?>
            <?php
        } else {
            ?><h1>Your portfolio is empty</h1><?php
        }
        ?></div>
</div>
<br/>
<!-- $Id: portfolio.php 1825 2014-12-27 14:57:05Z hom $ -->
<!-- db_name=<?= $db_name ?> -->
<?php
$page->addBodyComponent(new Component(ob_get_clean()));
$page->show();
?>
