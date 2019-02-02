<?php
requireCap(CAP_TUTOR);
include_once('peerutils.php');
include_once('documentfolders2.php');
include_once('navigation2.php');
require_once 'prjMilestoneSelector2.php';
$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
extract($_SESSION);

$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
extract($prjSel->getSelectedData());
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;


$doctype = 1;

if (!isSet($_SESSION['prj_id']) || $_SESSION['prj_id'] == '') {
    // make a gues 
    $sql = "select distinct prjm_id from uploads order by prjm_id desc limit 1\n";
    $resultSet = $dbConn->execute($sql);
    if ($resultSet === false) {
        die('Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql);
    }
    if (!$resultSet->EOF) {
        extract($resultSet->fields);
    }
}
if (isSet($_REQUEST['doctype'])) {
    $_SESSION['doctype'] = $doctype = validate($_REQUEST['doctype'], 'integer', 1);
}
if (!isSet($_SESSION['doctype'])) {
    $sql = "select min(doctype) as doctype from uploads where prjm_id=$prjm_id ";
    $resultSet = $dbConn->Execute($sql);
    if (!$resultSet->EOF) {
        extract($resultSet->fields);
    } else {
        $doctype = 1;
    }
}
$_SESSION['doctype'] = $doctype;

$prjSel->setJoin('has_uploads using (prj_id,milestone)');

$prj_id_selector = $prjSel->getSelector();

$docListSql = "select description||' ('||coalesce(dtc.doc_count,0)||' document'||\n"
        . "(case when coalesce(dtc.doc_count,0) <> 1 then 's' else ''end)||')' as name,doctype as value\n"
        . " from prj_milestone join uploaddocumenttypes using(prj_id) left join \n"
        . "  (select count(upload_id) as doc_count,prjm_id,doctype \n"
        . "  from uploads where prjm_id=$prjm_id group by prjm_id,doctype) dtc\n"
        . " using(prjm_id,doctype)  where prjm_id=$prjm_id";

$docList = "<select name='doctype' onChange='submit()'>\n" .
        getOptionList($dbConn, $docListSql, $doctype) .
        "</select>";

$sql = "select count(*) as doccount from uploads where prjm_id=$prjm_id ";
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
} else if (!$resultSet->EOF) {
    extract($resultSet->fields);
    $zip_filesize = number_format($zip_filesize, 0, ',', '.');
}
?>
<?= $nav->show() ?>
<div id='navmain' style='padding:1em;'>
    <h1>Read uploaded files</h1>
    <fieldset><legend>select project/milestone</legend>
        <form method="get" name="project" action="<?= $PHP_SELF ?>">
            <?= $prj_id_selector ?><input type="submit" value="Get Project"/> <?= $prj_id ?>M<?= $milestone ?>/prjm_id=<?= $prjm_id ?>( <?= $doccount ?> documents).
        </form>
        <form method="get" name="documenttype" action="<?= $PHP_SELF ?>">
            <?= $docList ?><input type="submit" value="Get doc type"/>  (doctype <?= $doctype ?>)
        </form>
    </fieldset>
    <h2>What you can see here</h2>
    <p>You can select documents uploaded by students. 
        The links in the table take you to the critique pages.</p>
    <p>You can also <button onClick='location.href = "zipit.php?prjm_id=<?= $prjm_id ?>&doctype=<?= $doctype ?>"'>zip and download</button> all files  (total uncompressed <?= $zip_filesize ?> bytes).</p>
    <?php
    $sql = "select afko,ut.description as doc_desc,pt.description as description,prj_id,\n"
            . "year,milestone,pt.grp_num,tg.grp_num as viewergrp,\n"
            . "pt.grp_num as authorgrp,rtrim(alias) as alias,rtrim(long_name) as long_name,"
            . "title,rel_file_path,trim(mime_type_long) as mime_type,due,\n"
            . "to_char(uploadts,'YYYY-MM-DD HH24:MI:SS')::text as uploadts,\n"
            . "case when uploadts::date > pd.due then 'late' else 'early' end as late_or_early,\n"
            . "vers,ut.doctype,ut.description as dtdescr,upload_id,\n"
            . "u.snummer, roepnaam,tussenvoegsel,achternaam,student_class.sclass as klas,u.upload_id as doc_id,\n"
            . "doc_count,critique_count as crits,u.rights[0:2]\n,u.filesize "
            . "from uploads u \n"
            . "join all_prj_tutor pt using (prjtg_id,prjm_id)\n"
            . "join uploaddocumenttypes ut using(prj_id,doctype)\n"
            . "join project_deliverables pd on(pd.prjm_id=u.prjm_id and u.doctype=pd.doctype)\n"
            . "left join doctype_upload_group_count dugc on(dugc.prjtg_id=u.prjtg_id and dugc.doctype=u.doctype)\n"
            . "join student_email s on(s.snummer=u.snummer)\n"
            . "join student_class using (class_id)\n"
            . "left join document_critique_count on (upload_id=doc_id)\n"
            . "left join (select distinct prjtg_id,grp_num \n"
            . "   from prj_tutor where tutor_id='$peer_id') tg \n"
            . "on(u.prjtg_id=tg.prjtg_id)\n"
            . "where u.prjm_id = $prjm_id and u.doctype=$doctype\n"
            . "order by pt.grp_num,ut.doctype,achternaam,vers desc,doc_id desc,achternaam,roepnaam ";
//$dbConn->log( $sql );
    if (isSet($doctype)) {
        $resultSet = $dbConn->Execute($sql);
        if ($resultSet === false) {
            die('Error: <pre>' . $dbConn->ErrorMsg() . " with<br/>\n" . $sql . "</pre>\n");
        } else {
            documentFoldersPreExecuted($resultSet, 'doctype=' . $doctype);
        }
    } else {
        echo "No documents avaliable for this project";
    }
    ?>
</div>
<!-- $Id: groupuploads.php 1825 2014-12-27 14:57:05Z hom $ -->
<!-- db_name=<?= $db_name ?> -->
</body>
</html>