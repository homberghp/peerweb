<?php
requireCap(CAP_SYSTEM);
require_once('validators.php');
include_once('navigation2.php');
require_once 'simplequerytable.php';
require_once 'prjMilestoneSelector2.php';
require_once 'SpreadSheetWriter.php';

requireCap( CAP_TUTOR );

$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
extract( $_SESSION );
$prjSel = new PrjMilestoneSelector2( $dbConn, $peer_id, $prjm_id );
extract( $prjSel->getSelectedData() );
$_SESSION[ 'prj_id' ] = $prj_id;
$_SESSION[ 'prjm_id' ] = $prjm_id;
$_SESSION[ 'milestone' ] = $milestone;
//$dbConn->log("$tutor_code $prjm_id, $prj_id, $milestone");


$filename = 'examlist_' . $afko . '-' . date( 'Ymd' );
$sql = "select distinct snummer as id,achternaam as surname,roepnaam||coalesce(' '||tussenvoegsel,'') as name,trim(sclass) as sclass,voorletters\n"
        . " ,'EN' as lang,prjm_id,trim(coalesce(alias,'g'||grp_num)) as alias, md5(prjm_id::text||prj_grp.snummer::text || now()) AS token, \n"
        . " cohort,email1,course_short as education, case when prjm_id=422 then 1 else 0 end as lo"
        . "  from student s join prj_grp using(snummer) \n"
        . " join all_prj_tutor using(prjtg_id)\n"
        . " join student_class using(class_id) left join fontys_course fc on(s.opl=fc.course)"
        . " where prjm_id in (422,$prjm_id) order by lo,surname,name";

$spreadSheetWriter = new SpreadSheetWriter( $dbConn, $sql );
$title = "exam list";
$spreadSheetWriter->setFilename( $filename )
        ->setLinkUrl( $server_url . $PHP_SELF . '?prjm_id=' . $prjm_id )
        ->setTitle( $title )
        ->setAutoZebra( true );


$spreadSheetWriter->processRequest();

$spreadSheetWidget = $spreadSheetWriter->getWidget();

// <a href='../emailaddress.php?snummer=snummer'>snummer</a>


$rainbow = new RainBow( STARTCOLOR, COLORINCREMENT_RED, COLORINCREMENT_GREEN, COLORINCREMENT_BLUE );
pagehead( 'Get exam list' );
$page_opening = "Exam list for project $afko $description (prj_id $prj_id milestone $milestone prjm_id $prjm_id)";
$nav = new Navigation( $tutor_navtable, basename( $PHP_SELF ), $page_opening );
$nav->setInterestMap( $tabInterestCount );

$prjSel->setJoin( 'milestone_grp using (prj_id,milestone)' );
$prj_id_selector = $prjSel->getSelector();
?>
<?= $nav->show() ?>
<div id='navmain' style='padding:1em;'>
    <fieldset><legend>Select project</legend>
        <form method="get" name="project" action="<?= $PHP_SELF; ?>">
            <?= $prj_id_selector ?>
            <input type='submit' name='get' value='Get' />
            <?= $spreadSheetWidget ?>
            <?= $prjSel->getSelectionDetails() ?>
        </form>
        <br/>
        <p>If you select <b>comma ',' separated</b> file in the menu, this page will produce a file fit for Auto Multiple Choice use, a.k.a. a Latex exam.</p>
</div>
</fieldset>
<div align='left' style='margin:0 10ex 0 10ex'>

    <?= simpletable( $dbConn, $sql, "<table summary='candidates' style='border-collapse:collapse' border='1'>" ); ?>
</div>
</body>
</html>
<?php
echo "<!-- dbname=$db_name -->";
?>
