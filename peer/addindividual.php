<?php
require_once('./peerlib/peerutils.inc');
require_once('./peerlib/validators.inc');
include_once('navigation2.inc');
require_once 'studentpicker.php';
require_once'prjMilestoneSelector2.php';
requireCap( CAP_TUTOR );
$prjm_id = $prj_id = $milestone = 1;
$newsnummer = 0;
unset( $_SESSION['newsnummer'] );
extract( $_SESSION );
$prjSel = new PrjMilestoneSelector2( $dbConn, $peer_id, $prjm_id );
extract( $prjSel->getSelectedData() );

$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;

if ( isSet( $_GET['newsnummer'] ) ) {
    unset( $_POST['newsnummer'] );
    $_REQUEST['newsnummer'] = $newsnummer = validate( $_GET['newsnummer'],
            'integer', '0' );
    //    $dbConn->log('GET '.$newsnummer);
} else if ( isSet( $_POST['newsnummer'] ) ) {
    unset( $_GET['newsnummer'] );
    $_REQUEST['newsnummer'] = $newsnummer = validate( $_POST['newsnummer'],
            'integer', '0' );
    //    $dbConn->log('POST '.$newsnummer);
} else {
    unset( $_POST['newsnummer'] );
    unset( $_REQUEST['newsnummer'] );
    unset( $_GET['newsnummer'] );
}
$searchname = '';
$studentPicker = new StudentPicker( $dbConn, $newsnummer, 'Search and select participant to add.' );
if ( isSet( $_REQUEST['searchname'] ) ) {
    if ( !preg_match( '/;/', $_REQUEST['searchname'] ) ) {
        $searchname = $_REQUEST['searchname'];
        $studentPicker->setSearchString( $searchname );
        if ( !isSet( $_REQUEST['newsnummer'] ) ) {
            $newsnummer = $studentPicker->findStudentNumber();
        }
    } else {
        $searchname = '';
    }
    $_SESSION['searchname'] = $searchname;
} else {
    $_SESSION['searchname'] = $searchname;
    $studentPicker->setSearchString( $_SESSION['searchname'] );
}

$_SESSION['searchname'] = $searchname;

if ( !(isSet( $prj_id ) && isSet( $milestone )) ) {
    $sql = "select max(prjm_id) as prjm_id,milestone from prj_milestone where milestone=1 group by milestone limit 1";
    $resultSet = $dbConn->execute( $sql );
    if ( $resultSet === false ) {
        die( "<br>Cannot get prj_id milestone " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>" );
    }
    extract( $resultSet->fields );
}

// test if this owner can update this project
$isTutorOwner = checkTutorOwner( $dbConn, $prj_id, $peer_id );
if ( ($isTutorOwner || $isGroupTutor ) && isSet( $_REQUEST['baccept'] ) && $newsnummer != 0 ) {
    // try to insert this snummer into max prj_grp 
    $sql = "begin work;"
            . " insert into prj_grp (snummer,prj_grp_open,prjtg_id) \n"
            . "  select snummer,false,prjtg_id\n"
            . " from (select snummer from student where snummer=$newsnummer and snummer not in \n"
            . " (select snummer from prj_grp join prj_tutor using(prjtg_id) \n"
            . "   where prjm_id=$prjm_id )) st \n"
            . "  cross join (select prjtg_id from prj_tutor pt where pt.prjm_id=$prjm_id and \n"
            . "grp_num =(select max(grp_num) from prj_tutor where prjm_id=$prjm_id)) pgt;\n"
            . "update prj_tutor set prj_tutor_open=false \n"
            . "where grp_num=(select max(grp_num) from prj_tutor where prjm_id=$prjm_id)\n"
            . "   and prjm_id=$prjm_id;\n"
            . "update prj_grp set prj_grp_open=false where prjtg_id =\n"
            . "(select prjtg_id from prj_tutor where \n"
            . "  grp_num=(select max(grp_num) from prj_tutor \n"
            . "   where prjm_id=$prjm_id) and prjm_id=$prjm_id);\n"
            . "commit;";

    $resultSet = $dbConn->Execute( $sql );
    if ( $resultSet === 0 ) {
        $dbConn->log( $dbConn->ErrorMsg() );
        $dbConn->Execute( "abort" );
    }
}

if ( ($isTutorOwner || $isGroupTutor ) && isSet( $_REQUEST['bdelete'] ) && $newsnummer != 0 ) {
    // try to insert this snummer into max prj_grp 
    $sql = "delete from prj_grp pg where snummer=$newsnummer \n" .
            "and prjtg_id in (select prjtg_id from prj_tutor where prjm_id=$prjm_id)";
    $dbConn->Execute( $sql );
    //    $dbConn->log($dbConn->ErrorMsg());
}

$studentPicker->setPresentQuery( "select snummer from prj_grp join prj_tutor using(prjtg_id) where prjm_id=$prjm_id" );
$sql = "select distinct snummer,achternaam,roepnaam,voorvoegsel,pt.grp_num, alias as group,sclass \n" .
        " from prj_grp pg join prj_tutor pt using(prjtg_id) join student using(snummer) \n" .
        "join student_class using(class_id)\n" .
        " left join grp_alias using(prjtg_id)" .
        " where pt.prjm_id=$prjm_id \n" .
        "  order by grp_num desc,achternaam,roepnaam";
$rainbow = new RainBow();
$memberTable = getQueryToTableChecked( $dbConn, $sql, true, 4, $rainbow, -1, '',
        '' );

pagehead( 'Add individual student.' );
$page_opening = "Add individual student to a project. <span style='font-size:6pt;'>prj_id $prj_id milestone $milestone </span>";
$nav = new Navigation( $tutor_navtable, basename( $PHP_SELF ), $page_opening );
$nav->setInterestMap( $tabInterestCount );
$nav->show();
$prjSelectWidget = $prjSel->getWidget();
include_once'templates/addindividual.html';
?>
<!-- db_name=<?= $db_name ?> -->
<!-- $Id: addindividual.php 1725 2014-01-16 08:39:59Z hom $ -->
</body>
</html>