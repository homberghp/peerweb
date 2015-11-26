<?php

include_once('./peerlib/peerutils.inc');
require_once('./peerlib/validators.inc');
include_once('navigation2.inc');
require_once('conffileeditor2.php');
requireCap(CAP_TUTOR);
require_once 'prjMilestoneSelector2.php';
$pp = array();
$pp['cmd_result'] = '';
$pp['executionResult'] = '';

$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
$year = date('Y');
extract($_SESSION);
$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
extract($prjSel->getSelectedData());
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;

$new_repos_name = strtolower($afko) . 'm' . $milestone;

$tutor = $tutor_code;
if ($db_name == 'peer2') {
    $reposroot = '/home/svnt';
    $url_base = 'svnt';
} else {
    $reposroot = '/home/svn';
    $url_base = 'svn';
}

pagehead('Create Subversion repositories');
$page = new PageContainer();
$pageTitle = "Subversion repositories";
$page->setTitle($pageTitle);

$cmdstring = '';
$pp['repoURL'] = $server_url . '/svn/' . $year . '/' . $new_repos_name . '/';
$twigs = '';
if (isSet($_POST['bcreate'])) {
    if (isSet($_REQUEST['new_repos_name']) && $_REQUEST['new_repos_name']) {
        $new_repos_name = trim($_REQUEST['new_repos_name']);
    }
    $individual = (trim($_REQUEST['repos_individual']) == 'individual') ? 'individual' : 'group';
    $twigs = trim($_REQUEST['twigs']);
    if ($individual == 'individual') {
        $cmdstring = $subversionscriptdir . "/mksvnindividual2.pl --db $db_name "
                . "--projectmilestone $prjm_id --parent $reposroot --name $new_repos_name --url_base $url_base --twigs='$twigs'";
    } else {
        $cmdstring = $subversionscriptdir . "/mksvngroup2.pl --db $db_name "
                . "--projectmilestone $prjm_id --parent $reposroot --name $new_repos_name --url_base $url_base --twigs '$twigs'";
    }
    ob_start();
    $handle = popen($cmdstring, 'r');
    fpassthru($handle);
    pclose($handle);

#  passthru($cmdstring);
    echo "$cmdstring\n";
    $pp['repoURL'] = $server_url . '/' . $url_base . '/' . $year . '/' . $new_repos_name . '/';
    $pp['cmd_result'] = "<fieldset> <legend>Create command result <?=$cmdstring?></legend><pre style='background:white'>"
            . ob_get_clean()
            . "</pre></fieldset>"
            . "<span style='font-size=160%'>The repository will live at <a href='$repoURL' target='_blank'>$repoURL</a></span>\n";
}

if (isSet($_POST['repos_id'])) {
    $_SESSION['repos_id'] = validate($_POST['repos_id'], 'integer', '0');
}
// see if we can find the root repos for this project
if (isSet($_SESSION['repos_id'])) {
    $repos_id = $_SESSION['repos_id'];
    $sql = "select repospath,url_tail,description as repos_description,isroot,id from repositories \n" .
            "\t where prjm_id=$prjm_id and id=$repos_id and isroot=true order by id limit 1";
} else {
    $sql = "select repospath,url_tail,description as repos_description,isroot,id from repositories \n" .
            "\t where prjm_id=$prjm_id  and isroot=true order by id limit 1";
}
//$dbConn->log($sql);
$resultSet = $dbConn->Execute($sql);
if (!$resultSet->EOF) {
    extract($resultSet->fields);
    $authzfile = $url_tail . '/conf/authz';
    $authzfilepath = '/home' . $authzfile;
    if (is_file($authzfilepath)) {
        $_SESSION['mustCommit'] = 1;
        $pp['executionResult'] = ConfFileEditor::save();
        $_SESSION['conf_editor_basedir'] = '/home';
        $_SESSION['fileToEdit'] = $authzfile;
        $pp['repos_id'] = $id;
        $pp['fileeditor'] = new ConfFileEditor($PHP_SELF, 'templates/authzeditor.html');
    }
}
$pp['page'] = $page;


$sql = "select repospath,grp_num,description as repos_description," .
        "url_tail,isroot,id,last_commit from repositories \n" .
        "\t where prjm_id=$prjm_id order by repospath";
$resultSet = $dbConn->Execute($sql);
$reposTable = '';
if (!$resultSet->EOF) {
    $reposTable .= "<fieldset><legend>Available repositories</legend>"
            . "<table>\n"
            . "<tr><th>Id</th><td>grp</td><th>Path</th><th>description</th><th>revisions</th><th>last commit</th><th>Edit authorizations</th></tr>\n";

    while (!$resultSet->EOF) {
        extract($resultSet->fields);
        $editControl = '&nbsp;';
        $url = $server_url . $url_tail;
        if ($isroot == 't') {
            $editControl = "\t<form method='post' name='editauthz' action='$PHP_SELF'>\n"
                    . "\t\t<input type='submit' value='Edit autzh' name='edit_authz'  "
                    . "title='edit authorization for repo or repo group'/>\n"
                    . "\t\t<input type='hidden' name='repos_id' value='$id'/>\n\t</form>";
        }
        $youngest = `/usr/bin/svnlook youngest $repospath`;
        $reposTable .= "\t<tr><td>$id</td><td>$grp_num</td>\n\t<td><a href='$url'>$url_tail</a></td>\n"
                . "\t<td>$repos_description</td>\n"
                . "<td>$youngest</td>"
                . "<td>{$last_commit}</td>"
                . "\t<td>$editControl</td>\n</tr>\n";
        $resultSet->moveNext();
    }
    $reposTable .="</table>\n</fieldset>\n";
}
$pp['reposTable'] = $reposTable;
$groups = array();
// get tutors and scribes
$sql = "select distinct 'tutor' as alias,snummer from svn_tutor_snummer\n"
        . " natural join prj_milestone where prjm_id=$prjm_id order by snummer";
$resultSet = $dbConn->Execute($sql);
if ($resultSet !== false) {
    while (!$resultSet->EOF) {
        extract($resultSet->fields);
        $groups[$alias][] = $snummer;
        $resultSet->moveNext();
    }
}
// get scribes
$groups['auditor'] = '';
$sql = "select distinct 'auditor' as alias,scribe as snummer \n"
        . "from project_scribe where prj_id=$prj_id and scribe not in (select userid from tutor)";
$resultSet = $dbConn->Execute($sql);
if ($resultSet !== false) {
    while (!$resultSet->EOF) {
        extract($resultSet->fields);
        $groups[$alias][] = $snummer;
        $resultSet->moveNext();
    }
}
// get students
$sql = "select grp_name, snummer from prj_tutor \n"
  ."join prj_grp using (prjtg_id)  "
        . " where prjm_id=$prjm_id order by grp_name,snummer";
$resultSet = $dbConn->Execute($sql);
if ($resultSet !== false) {
    while (!$resultSet->EOF) {
        extract($resultSet->fields);
        $groups[$grp_name][] = $snummer;
        $resultSet->moveNext();
    }
}
$pp['grpLists'] = '';
$all=array();
foreach ($groups as $grp => $list) {
    //echo implode(' ',$list);
    if (isSet($list) && is_array($list)) {
        $grpStr = join(',', $list);
        $all[] = join(',',$list);
    } else {
        $grpStr = '';
    }
    $pp['grpLists'] .= "<span>$grp=$grpStr</span><br/>\n";
}
$allMembers = join(',',$all);
$pp['grpLists'] .="<span>all={$allMembers}</span><br/>\n";
$pp['afko_lc'] = strtolower($afko);
$prjSel->setSubmitOnChange(true);
$pp['prj_id_selector'] = $prjSel->getWidget();
$page_opening = "Subversion repositories for project $afko: $description (prj_id: $prj_id, milestone:$milestone)";
$nav = new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$page->addBodyComponent($nav);
$page->addHtmlFragment('templates/subversionrepostop.html', $pp);
if (isSet($pp['fileeditor'])) {
    $pp['fileeditor']->getWidgetForPage($page, $pp);
}
$page->addHtmlFragment('templates/subversionreposbottom.html', $pp);
$page->show();

