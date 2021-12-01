<?php

requireCap(CAP_SYSTEM);
require_once('navigation2.php');
require_once 'prjMilestoneSelector2.php';
require_once 'TemplateWith.php';

requireCap(CAP_TUTOR);
$prjm_id = 0;
$prj_id = 1;
$milestone = 1;
extract($_SESSION);

$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
$prjSel->setWhere(" exists (select * from prj_grp where prjm_id=pm.prjm_id)");
extract($prjSel->getSelectedData());
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;

// unknown project?

$grp_num = 1;
if (isSet($_POST['grp_num'])) {
    $_SESSION['grp_num'] = $grp_num = $_POST['grp_num'];
}
$isTutorOwner = checkTutorOwnerMilestone($dbConn, $prjm_id, $peer_id); // check if this is tutor_owner of this project
if (isSet($_POST['maillist'])) {
    createMaillists($dbConn, $prjm_id);
}

if (isSet($_POST['dup'])) {
    $new_prjm_id = $_POST['dup_prjm_id'];

    if ($new_prjm_id <> '') {
        $sql = "select prj_id,milestone from prj_milestone where prjm_id=$new_prjm_id";
        $pstm = $dbConn->query($sql);
        $row=$pstm->fetch();
        $new_prj_id = $row['prj_id'];
        $new_milestone = $row['milestone'];
        $sql = "BEGIN WORK;\n"
            . "DELETE FROM prj_grp WHERE prjtg_id in \n" 
            . "(select prjtg_id from prj_tutor where prjm_id =$new_prjm_id);\n";
        $sql .= "DELETE FROM prj_tutor WHERE prjm_id=$new_prjm_id;\n";
        $sql .= "INSERT INTO prj_tutor (grp_num,prjm_id,tutor_id,grp_name)\n"
                . " select o.grp_num,n.prjm_id,o.tutor_id,grp_name\n"
                . " from prj_milestone n cross join prj_tutor_builder o  where n.prjm_id=$new_prjm_id and o.prjm_id=$prjm_id;\n";

        $sql .= "INSERT INTO prj_grp (snummer, prj_grp_open,written,prjtg_id)\n"
                . "select snummer,prj_grp_open,written,prjtg_id \n"
                . "from prj_grp_builder2 "
                . "where prjm_id=$new_prjm_id and orig_prjm_id=$prjm_id;\n";

        $sql .= "INSERT into grp_alias (long_name,alias,website,productname,prjtg_id)\n"
                . "  SELECT n.long_name,"
                . "coalesce(o.alias,'g'||n.grp_num::text) as alias,\n"
                . " o.website,o.productname,\n"
                . "  n.prjtg_id from grp_alias_builder o join \n"
                . "   grp_alias_builder n using (grp_num) where o.prjm_id=$prjm_id and n.prjm_id=$new_prjm_id;\n";

        $sql .="insert into project_roles\n" .
                "\t (prj_id,role,rolenum,capabilities,short)\n" .
                "\tselect $new_prj_id,role,rolenum,capabilities,short from project_roles\n" .
                "\twhere prj_id=$prj_id and " .
                "\t\t(($new_prj_id,rolenum) not in (select prj_id,rolenum from project_roles));\n";
        $sql .="INSERT INTO student_role (snummer,rolenum,capabilities,prjm_id)\n" .
                "\tselect  snummer,rolenum,capabilities,$new_prjm_id \n" .
                "from student_role\n" .
                " where prjm_id=$prjm_id;\n";
        $sql .= "commit;\n";
        //	$dbConn->log($sql);
        $pstm = $dbConn->query($sql);
        if ($pstm===false) die("error with {$sql}");
        // go to the dupped group.
        $prjSel->setPrjmId($new_prjm_id);
        extract($prjSel->getSelectedData());
        $_SESSION['prj_id'] = $prj_id;
        $_SESSION['prjm_id'] = $prjm_id;
        $_SESSION['milestone'] = $milestone;
    }
}


$prjm_id_selector = $prjSel->getWidget(); //getSelector();
$isAdmin = hasCap(CAP_SYSTEM) ? 'true' : 'false';
$sqlDup = "select p.afko||': '||p.description||'('||p.year||')'||' m'||pme.milestone||' (#'\n"
        . "||pme.prjm_id||')' as\n"
        . "name,\n\t pme.prjm_id as value"
        . " from project p\n"
//        ."  join tutor t on(userid=owner_id)\n"
        . "join prj_milestone pme on(pme.prj_id=p.prj_id) \n"
        . " where p.prj_id > 0 and '$peer_id'= owner_id and\n"
        . "( not exists (select * from prj_milestone pm \n"
        . "              join prj_tutor pt on (pm.prjm_id=pt.prjm_id) \n"
        . "           join prj_grp pg  on (pt.prjtg_id=pg.prjtg_id) \n"
        . "    where pm.prjm_id=pme.prjm_id))\n"
        . " and p.valid_until > now()::date"
        . " order by p.prj_id desc,pme.milestone";

// $dupSel=new PrjMilestoneSelector2($dbConn,$peer_id,$prjm_id);
// $dupSel->setWhere(" (not exists (select * from prj_grp where prjm_id=pm.prjm_id))" );

$dupPrjList = getOptionList($dbConn, $sqlDup, $prjm_id);

//$dbConn->log($sqlDup);
$sql = "select tutor,tutor_id from prj_tutor pt join tutor t on(t.userid=pt.tutor_id) where prjm_id=$prjm_id and grp_num='$grp_num'";
$pstm = $dbConn->query($sql);
if ($pstm === false) {
    print 'error selecting: ' . $dbConn->errorInfo()[2] . '<br> with ' . $sql . ' <br/>';
}
if (($row=$pstm->fetch())!==false) {
    $tutor = $row['tutor'];
    $tutorid = $row['tutor_id'];
}


$page = new PageContainer();
$page_opening = "Copy project groupsfrom" .
        "<span style='font-size:8pt;'>prj_id $prj_id milestone $milestone prjm_id $prjm_id</span>";
$page->setTitle('Copy project groups');
$nav = new Navigation($tutor_navtable, basename(__FILE__), $page_opening);

extract(getTutorOwnerData2($dbConn, $prjm_id), EXTR_PREFIX_ALL, 'ot');

$page->addBodyComponent($nav);
$templatefile = '../templates/copygroups.html';
$template_text = file_get_contents($templatefile, true);
if ($template_text === false) {
    $page->addBodyComponent(new Component("<strong>cannot read template file $templatefile</strong>"));
} else {
    $text = templateWith($template_text, get_defined_vars());
    $page->addBodyComponent(new Component($text));
}
$page->setBodyTag("<body id='body' class='{$body_class}' >");
$page->show();
?>
