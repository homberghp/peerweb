<?php
include_once('./peerlib/peerutils.php');
require_once('./peerlib/validators.php');
include_once('navigation2.inc');
include './peerlib/simplequerytable.php'; 
require_once 'selector.php';
require_once 'studentpicker.php';
//$dbConn->setSqlAutoLog(true);
requireCap(CAP_TUTOR);
// get group tables for a project
$newsnummer=0;
$act_id=0;
unset($_SESSION['newsnummer']);
extract($_SESSION);
if (isSet($_GET['newsnummer'])) {
    unset($_POST['newsnummer']);
    $_REQUEST['newsnummer']=$newsnummer =validate($_GET['newsnummer'],'snummer','0'); 
    //    $dbConn->log('GET '.$newsnummer);
 } else if (isSet($_POST['newsnummer'])){
    unset($_GET['newsnummer']);
    $_REQUEST['newsnummer']=$newsnummer =validate($_POST['newsnummer'],'snummer','0'); 
    //    $dbConn->log('POST '.$newsnummer);
 } else {
    unset($_POST['newsnummer']);
    unset($_REQUEST['newsnummer']);
    unset($_GET['newsnummer']);
 }

if (isSet($_REQUEST['act_id'])) {
    $_SESSION['act_id']=
    $act_id=validate($_REQUEST['act_id'],'integer',$act_id);
 }
$_SESSION['act_id']=$act_id;
if (isSet($act_id) && ($act_id >= 0)){
    $sql = "select act_id,short,description,datum,part,start_time from activity where act_id=$act_id"; 
    $rs=$dbConn->Execute($sql);
    if (!$rs->EOF){
	extract($rs->fields);
    }
}
$searchname='';
$studentPicker= new StudentPicker($dbConn,$newsnummer,'Search and select participant to add.' );
if (isSet($_REQUEST['searchname'])) {
    #    $dbConn->log($_REQUEST['searchname']);
    if (!preg_match('/;/',$_REQUEST['searchname'])) {
        $searchname=$_REQUEST['searchname'];
        $studentPicker->setSearchString($searchname);
        if (!isSet($_REQUEST['newsnummer'])) {
            $newsnummer=$studentPicker->findStudentNumber();
        }
    } else {
        $searchname='';
    }
    $_SESSION['searchname']=$searchname;
} else {
    $studentPicker->setSearchString($searchname);
}
$_SESSION['searchname']=$searchname;

if ( isSet($_REQUEST['baccept']) && $newsnummer != 0 ) {
    // try to insert this snummer into max prj_grp 
    $sql= "insert into activity_participant (act_id,snummer) values($act_id,$newsnummer)\n";
    $dbConn->Execute($sql);
    //    $dbConn->log($dbConn->ErrorMsg());
 }

if ( isSet($_REQUEST['bdelete']) && $newsnummer != 0 ) {
    // try to insert this snummer into max prj_grp 
    $sql= "delete from activity_participant where snummer=$newsnummer and act_id=$act_id\n";
    $dbConn->Execute($sql);
    //    $dbConn->log($dbConn->ErrorMsg());
 }

$filename='activity'.$short.'-'.date('Ymd').'.csv';

$csvout='N';
$csvout_checked='';
if (isSet($_REQUEST['csvout'])) { 
    $csvout=$_REQUEST['csvout'] ;
    $csvout_checked = ($csvout=='Y')?'checked':'';
 }

$sql1 = "select snummer,".
    "achternaam||rtrim(coalesce(', '||voorvoegsel,'')) as achternaam ,roepnaam,".
    " sclass,sort1,sort2\n ".
    "  from activity_participant join activity using(act_id) join student using(snummer)".
    "  join student_class using(class_id)\n".
    "  where act_id=$act_id order by sort1,sort2,sclass,achternaam,roepnaam"; 

$sql2 = "select snummer,".
    "achternaam||rtrim(coalesce(', '||voorvoegsel,'')) as achternaam ,roepnaam,".
    " sclass,sort1,sort2\n ".
    "  from activity_participant join activity using(act_id) join student using(snummer)".
    "  join student_class using(class_id)\n".
    "  where act_id=$act_id order by sort1,sort2,sclass,achternaam,roepnaam"; 

$rainbow= new RainBow(STARTCOLOR,COLORINCREMENT_RED,COLORINCREMENT_GREEN,COLORINCREMENT_BLUE);
if ($csvout == 'Y') {
    $dbConn->queryToCSV( $sql2, $filename);
    exit(0);
}
pagehead('Get activity participation');
$page_opening="List of students attending activities";
$nav=new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
$nav->setInterestMap($tabInterestCount);
$sql3="select datum||', '||' ('||act_id||', #'||coalesce(apc.count,0)||') '||rtrim(short)||'*'||part||': '||rtrim(description) as name, act_id as value,".
    "substr(datum::text,1,4) as namegrp\n".
    " from activity left join act_part_count apc using(act_id) order by namegrp desc,datum,part";
$prjSel= new Selector($dbConn,'act_id',$sql3,$act_id);
$act_id_selector=$prjSel->getSelector();

?>
<?=$nav->show()?>
<div id='navmain' style='padding:1em;'>
<fieldset><legend>Select project</legend>
<form method="get" name="activity" action="<?=$PHP_SELF;?>">
<?=$act_id_selector?>
csv:
<input type='checkbox' name='csvout' <?=$csvout_checked?> value='Y' />
<input type='submit' name='get' value='Get' />
</form>
</fieldset>

<?php
$studentPicker->setPresentQuery("select snummer,act_id from activity_participant where act_id=$act_id");
$studentPicker->show();
?>
<div align='center'>
<?php
$inputColumns=array(  '4' => array( 'type' => 'H', 'size' => '2'));
?>
<?=queryToTableChecked($dbConn,$sql1,true,3,$rainbow,-1,'','',$inputColumns);?>
</div>
</div>
</body>
</html>
<?php
echo "<!-- dbname=$db_name -->";
?>