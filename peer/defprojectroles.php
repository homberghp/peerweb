<?php
requireCap( CAP_TUTOR );
require_once('validators.php');
require_once('querytotable.php');
include_once('navigation2.php');
require_once 'prjMilestoneSelector2.php';
require_once 'project_selector.php';

$prj_id = 1;
$prjtg_id = 1;
$milestone = 1;
$prjm_id = 0;
$defrolenum = 0;
extract( $_SESSION );

$prjSel = new PrjMilestoneSelector2( $dbConn, $peer_id, $prjm_id );
extract( $prjSel->getSelectedData() );
$_SESSION[ 'prj_id' ] = $prj_id;
$_SESSION[ 'prjm_id' ] = $prjm_id;
$_SESSION[ 'milestone' ] = $milestone;


extract( $_SESSION );

$doctype_set = array();
$grp_num = 1;

if ( isSet( $_REQUEST[ 'grp_num' ] ) ) {
    $grp_num = validate( $_REQUEST[ 'grp_num' ], 'grp_num', $grp_num );
}
// test if this owner can update this group
$isGroupTutor = checkGroupTutor( $dbConn, $prjtg_id, $peer_id );
$isTutorOwner = checkTutorOwner( $dbConn, $prj_id, $peer_id );
if ( $isTutorOwner && isSet( $_GET[ 'copyroles' ] ) && isSet( $_GET[ 'roprj_id' ] ) && isSet( $_GET[ 'target_prj_id' ] ) ) {
    $roprj_id = $_GET[ 'roprj_id' ];
    $target_prj_id = $_GET[ 'target_prj_id' ];
    $sql = "insert into project_roles (prj_id,role,rolenum,capabilities,short)"
            . " select {$target_prj_id},role,rolenum,capabilities,short "
            . "from project_roles where prj_id={$roprj_id} and ({$target_prj_id},rolenum) not in (select prj_id,rolenum  from project_roles)";
    echo $sql;
    //exit(1);
    $dbConn->execute( $sql );
}
if ( ($isTutorOwner || $isGroupTutor ) && isSet( $_REQUEST[ 'broles' ] ) && isSet( $_REQUEST[ 'rolenum' ] ) ) {
    $memberset = '\'' . implode( "','", $_REQUEST[ 'sactor' ] ) . '\'';
    $sql = array();
    $sql[] = "delete from student_role where prjm_id=$prjm_id\n" .
            " and snummer in ($memberset);\n";

    for ( $i = 0; $i < count( $_REQUEST[ 'sactor' ] ); $i++ ) {
        $sactor = $_REQUEST[ 'sactor' ][ $i ];
        $rolenum = $_REQUEST[ 'rolenum' ][ $i ];
        $sql[] = "insert into student_role (snummer,rolenum,prjm_id)\n" .
                "\t values($sactor,$rolenum,$prjm_id);\n";
    }
    $affected_rows = $dbConn->executeQueryList( $sql );
    // number of rows could be shown.
}

if ( $isTutorOwner && isSet( $_REQUEST[ 'setdefrole' ] ) ) {
    $defrolenum = validate( $_REQUEST[ 'defrolenum' ], 'integer', '0' );
    $prjm_id = validate( $_REQUEST[ 'prjm_id' ], 'integer', '0' );
    $prj_id = validate( $_REQUEST[ 'prj_id' ], 'integer', '0' );
    $sql = "insert into student_role (snummer,rolenum,prjm_id)\n"
            . " select snummer,{$defrolenum},prjm_id \n"
            . "from prj_grp join prj_tutor using(prjtg_id) \n"
            . "where prjm_id={$prjm_id} and \n"
            . "exists (select 1 from project_roles where prj_id={$prj_id} and rolenum={$defrolenum})\n"
            . " and snummer not in (select snummer from student_role where prjm_id={$prjm_id})";
    //echo "<pre>{$sql}</pre>";
    $resultSet = $dbConn->execute( $sql );
    if ( $resultSet === false ) {
        die( "<br>Cannot set default role with <pre>$sql</pre>" . $dbConn->ErrorMsg() . "<br>" );
    }
}
if ( $isTutorOwner && isSet( $_REQUEST[ 'baddtype' ] ) &&
        isSet( $_REQUEST[ 'role_short' ] ) &&
        isSet( $_REQUEST[ 'role_description' ] ) ) {
    $description = pg_escape_string( $_REQUEST[ 'role_description' ] );
    $short = pg_escape_string( $_REQUEST[ 'role_short' ] );
    $sql = "select max(rolenum)+1 as nextval from project_roles where prj_id=$prj_id";
    $resultSet = $dbConn->execute( $sql );
    if ( $resultSet === false ) {
        die( "<br>Cannot get sequence next value with <pre>$sql</pre>" . $dbConn->ErrorMsg() . "<br>" );
    }
    $id = 0;
    if ( !$resultSet->EOF && isSet( $resultSet->fields[ 'nextval' ] ) ) {
        $id = $resultSet->fields[ 'nextval' ];
    }
    $sql = "begin work;\n" .
            "insert into project_roles (prj_id,role,rolenum,short) values ($prj_id,'$description',$id,'$short');\n";
    $sql .= 'commit;';
    if ( $db_name == 'peer2' )
        $dbConn->log( "<pre>$sql</pre>" );
    $resultSet = $dbConn->execute( $sql );
    if ( $resultSet === false ) {
        echo "<br>Cannot insert new role with <pre>" . $sql . "</pre> reason " . $dbConn->ErrorMsg() . "<br>";
    }
}
if ( $isTutorOwner &&
        isSet( $_REQUEST[ 'defroles' ] ) &&
        isSet( $_REQUEST[ 'capabilities' ] ) &&
        isSet( $_REQUEST[ 'rolenum' ] ) &&
        isSet( $_REQUEST[ 'role_short' ] ) &&
        isSet( $_REQUEST[ 'role' ] ) ) {
    $sql = "begin work;\n";
    for ( $i = 0; $i < count( $_REQUEST[ 'role' ] ); $i++ ) {
        $rolenum = $_REQUEST[ 'rolenum' ][ $i ];
        $role = substr( pg_escape_string( $_REQUEST[ 'role' ][ $i ] ), 0, 30 );
        $short = substr( $_REQUEST[ 'role_short' ][ $i ], 0, 4 );
        $capabilities = validate( $_REQUEST[ 'capabilities' ][ $i ], 'integer', 0 );
        $sql .= "update project_roles set role='$role',capabilities=$capabilities,short='$short' where prj_id=$prj_id and rolenum=$rolenum;\n";
    }
    $sql .= 'commit;';
    if ( $db_name == 'peer2' )
        $dbConn->log( $sql );
    $resultSet = $dbConn->execute( $sql );
    if ( $resultSet === false ) {
        echo "<br>Cannot update roles types with <pre>" . $sql . "</pre> reason " . $dbConn->ErrorMsg() . "<br>";
    }
}

$sql = "select tutor as tutor_owner from project join tutor on (userid=owner_id) where prj_id=$prj_id";
$resultSet = $dbConn->execute( $sql );
if ( $resultSet === false ) {
    echo "<br>Cannot get tutor owner <pre>" . $sql . "</pre> reason " . $dbConn->ErrorMsg() . "<br>";
}
extract( $resultSet->fields );

$prjSel->setJoin( 'milestone_grp using (prj_id,milestone)' );
//if ($db_name =='peer2') $dbConn->log($prjSel->getQuery());
$prj_id_selector = $prjSel->getSimpleForm();
$copy_form = '';
$sql = "select count(1) as role_count from project_roles where prj_id={$prj_id}";
$resultSet = $dbConn->execute( $sql );
$has_roles = $resultSet->fields[ 'role_count' ] > 0;
if ( !$has_roles ) {
    $copyselector = getProjectSelector( $dbConn, $peer_id, $prj_id, 'roprj_id', ' prj_id in (select distinct prj_id from project_roles)' );
    $copy_form = "<fieldset><legend>copy roles from other project</legend><form id='copyform' method='get'>\n"
            . "{$copyselector}\n"
            . "<input type='hidden' name='target_prj_id' value='{$prj_id}'/>\n"
            . "<input type='submit' name='copyroles' value='Copy Roles from project'/>\n"
            . "</form></fieldset>";
}
$defrollist = "<select name='defrolenum' >\n" .
        getOptionList( $dbConn, "select rolenum as value, role as name from project_roles\n" .
                " where prj_id=$prj_id order by rolenum", $defrolenum ) . "\n</select>\n";
$grpoptionlist = "<select name='grp_num' onchange='submit()'>\n" . getOptionList( $dbConn, "select distinct pt.grp_num||': '||coalesce(alias,'')"
                . "||' / '||tutor||'#'||coalesce(gs.size,0) as name, \n"
                . "pt.grp_num as value from prj_tutor pt \n"
                . " join tutor t on (t.userid=pt.tutor_id)\n"
                . "left join grp_alias using(prjtg_id) \n"
                . "left join grp_size gs on(pt.prjtg_id=gs.prjtg_id) where\n"
                . "prjm_id=$prjm_id order by pt.grp_num", $grp_num ) . "\n</select>\n";
$tdattrib = 'class=\'tabledata\' style=\'background:#ffc\'';
$roleTable = "<table class='tabledata' border='1' summary='current roles'>"
        . "<caption>Current student roles</caption>\n"
        . "<tr><th {$tdattrib}>Snumber</th><th {$tdattrib }>Student</th>"
        . "<th {$tdattrib }>Current Role</th><th {$tdattrib}>Cap</th><th {$tdattrib}>New Role</th></tr>";
$sqltut = "select s.snummer,achternaam,roepnaam,tussenvoegsel,\n" .
        "pr.rolenum,role,pr.capabilities as capabilities \n" .
        "from prj_grp pg join student s using(snummer) \n" .
        " join prj_tutor pt on(pg.prjtg_id=pt.prjtg_id)\n" .
        " join prj_milestone pm on(pm.prjm_id=pt.prjm_id)\n" .
        " left join student_role sr on(sr.prjm_id=pt.prjm_id and sr.snummer=pg.snummer)\n" .
        " left join project_roles pr on(sr.rolenum=pr.rolenum and pr.prj_id=pm.prj_id)\n" .
        " where pt.prjm_id=$prjm_id and pt.grp_num=$grp_num\n" .
        " order by achternaam asc,roepnaam asc";
//$dbConn->log($sqltut);
$resultSet = $dbConn->Execute( $sqltut );
if ( $resultSet === false ) {
    die( "<br>Cannot get groups with \"<pre>" . $sqltut . '</pre>", cause ' . $dbConn->ErrorMsg() . "<br>" );
}
//echo "<pre>$sqltut</pre>";
while (!$resultSet->EOF) {
    extract( $resultSet->fields );
    if ( $isGroupTutor || $isTutorOwner ) {
        $roleList = "<select name='rolenum[]' style='background:#FF8'>\n" .
                getOptionList( $dbConn, "select rolenum as value, role as name from project_roles\n" .
                        " where prj_id=$prj_id order by rolenum", $rolenum ) . "\n</select>\n";
    } else {
        $roleList = $role;
    }
    $roleTable .= "\t<tr>\n" .
            "\t\t<td {$tdattrib}>{$snummer}</td>\n" .
            "\t\t<td {$tdattrib}>{$achternaam},{$roepnaam} {$tussenvoegsel}</td>\n" .
            "\t\t<td $tdattrib>{$role}</td>\n" .
            "\t\t<td $tdattrib>{$capabilities}</td>\n" .
            "\t\t<td $tdattrib>{$roleList} <input type='hidden' name='sactor[]' value='{$snummer}'/></td>" .
            "\n\t</tr>\n";
    $resultSet->moveNext();
}
if ( $isGroupTutor || $isTutorOwner ) {
    $submitButton = "<input type='submit' name='broles' value='set roles' />";
} else {
    $submitButton = '&nbsp';
}
$roleTable .= "</table>\n";
$sql = "select rolenum as role_id,role as old_description,short as role_short,role,rolenum,capabilities from project_roles where prj_id=$prj_id order by rolenum";
// echo "<pre>$sql</pre>\n";
$inputColumns = array(
    '2' => array( 'type' => 'T', 'size' => '8' ),
    '3' => array( 'type' => 'T', 'size' => '30' ),
    '4' => array( 'type' => 'H', 'size' => '0' ),
    '5' => array( 'type' => 'N', 'size' => '2' ),
);
$roleDefTable = getQueryToTableChecked2( $dbConn, $sql, false, -1, new RainBow( 0x46B4B4, 64, 32, 0 ), 'document[]', $doctype_set, $inputColumns );
pagehead( 'Define types of roles students can play.' );
$page_opening = "Define the roles the students may assume in a project team.";
$nav = new Navigation( $tutor_navtable, basename( $PHP_SELF ), $page_opening );
$nav->setInterestMap( $tabInterestCount );
$page = new PageContainer();
$page->setTitle( $page_opening );
$page->addBodyComponent( $nav );
$templatefile = 'templates/defprojectrolestop.html';
$template_text = file_get_contents( $templatefile, true );
if ( $template_text === false ) {
    $page->addBodyComponent( "<strong>cannot read template file $templatefile</strong>" );
} else {
    $page->addBodyComponent( templateWith( $template_text, get_defined_vars() ) );
}
if ( $isTutorOwner ) {
    $templatefile = 'templates/defprojectrolesbottom.html';
    $template_text = file_get_contents( $templatefile, true );
    if ( $template_text === false ) {
        $page->addBodyComponent( "<strong>cannot read template file $templatefile</strong>" );
    } else {
        $page->addBodyComponent( templateWith( $template_text, get_defined_vars() ) );
    }
}

$page->show();
