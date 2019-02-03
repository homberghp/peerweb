<?php
requireCap(CAP_TUTOR);
require_once('peerutils.php');
require_once('documentfolders2.php');
require_once('navigation2.php');
require_once 'prjMilestoneSelector2.php';
$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
$prjtg_id = 1;
extract($_SESSION);

$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
$prjSel->setJoin('has_uploads using (prj_id,milestone)');
extract($prjSel->getSelectedData());
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;

$doctype = 1;

if (isSet($_REQUEST['doctype'])) {
    $_SESSION['doctype'] = $doctype = validate($_REQUEST['doctype'], 'integer', 1);
}
if (!isSet($_SESSION['doctype'])) {
    $sql = "select min(doctype) as doctype from uploads where prjm_id=$prjm_id";
    $resultSet = $dbConn->Execute($sql);
    if (!$resultSet->EOF) {
        extract($resultSet->fields);
    } else {
        $doctype = 1;
    }
}
$_SESSION['doctype'] = $doctype;


$sql = "select count(*) as doccount from uploads where prjm_id=$prjm_id";
$resultSet = $dbConn->Execute($sql);
$doccount = 0;
if ($resultSet === false) {
    echo('Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql);
} else {
    extract($resultSet->fields);
}
pagehead('Upload viewer for tutors');
$page_opening = "Documents handed in by a group";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$sql = "select sum(filesize) as zip_filesize from uploads where prjm_id=$prjm_id group by prjm_id";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    echo('Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql);
} else if (!$resultSet->EOF){
    extract($resultSet->fields);
    $zip_filesize = number_format($zip_filesize, 0, ',', '.');
}
?>
<?= $nav->show() ?>
<div id='navmain' style='padding:1em;'>
    <h1>Read uploaded files</h1>
    <fieldset><legend>select project/milestone</legend>
        <form method="get" name="project" action="<?= $PHP_SELF ?>">
            <?= $prjSel->getSelector() ?><input type="submit" value="Get Project"/>
            <?= $prj_id ?>M<?= $milestone ?>/prjm_id=<?= $prjm_id ?>( <?= $doccount ?> documents).
        </form>

    </fieldset>
    <h2>What you can see here</h2>
    <p>You can select documents uploaded by students. 
        The links in the table take you to the critique pages.</p>
    <p>You can also <button onClick='location.href = "zipit.php?prjm_id=<?= $prjm_id ?>"'>zip and download</button> all files (total uncompressed <?= $zip_filesize ?> bytes).</p>
    <?php
    $sql = "select afko,ut.description as doc_desc,apt.description as description,\n"
            . "prj_id,year,milestone,apt.grp_num,tutor_grp.grp_num as viewergrp,\n"
            . "apt.grp_num as authorgrp,rtrim(alias) as alias,rtrim(long_name) as long_name,\n"
            . "rtrim(title) as title,\n"
            . "rel_file_path,mime_type,due,\n"
            . "to_char(uploadts,'YYYY-MM-DD HH24:MI:SS')::text as uploadts,\n"
            . "case when uploadts::date > pd.due then 'late' else 'early' end as late_or_early,\n"
            . "vers,ut.doctype,ut.description as dtdescr,upload_id,\n"
            . "snummer, roepnaam,tussenvoegsel,achternaam,student_class.sclass as klas,u.upload_id as doc_id,\n"
            . "doc_count,critique_count as crits,u.rights[0:2],filesize\n "
            . "from uploads u\n"
            . "join all_prj_tutor apt using (prjtg_id,prjm_id)\n"
            . "join uploaddocumenttypes ut using(prj_id,doctype)\n"
            . "join project_deliverables pd using(prjm_id,doctype)\n"
            . "join upload_group_count using(prjtg_id)\n"
            . "join student_email using(snummer) \n"
            . "join student_class using (class_id)\n"
            . "left join document_critique_count on (upload_id=doc_id)\n"
            . "left join (select prjtg_id,grp_num  from prj_tutor where prjtg_id=$prjtg_id and tutor_id='$peer_id') tutor_grp\n"
            . " using(prjtg_id)\n"
            . "where prjm_id = $prjm_id "
            . "order by apt.grp_num,ut.doctype,achternaam,vers desc,doc_id desc,achternaam,roepnaam ";
//$dbConn->log( $sql );
    if (isSet($doctype)) {
        $resultSet = $dbConn->Execute($sql);
        if ($resultSet === false) {
            die('Error: ' . $dbConn->ErrorMsg() . ' with <pre>' . $sql . "</pre>\n");
        } else {
            documentFoldersPreExecuted($resultSet, 'doctype=' . $doctype);
        }
    } else {
        echo "No documents avaliable for this project";
    }
    ?>
</div>
<!-- $Id: groupuploadsall.php 1825 2014-12-27 14:57:05Z hom $ -->
<!-- db_name=<?= $db_name ?> -->
</body>
</html>