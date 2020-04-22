<?php

requireCap( CAP_TUTOR );
require_once('validators.php');
require_once('navigation2.php');
require_once 'prjMilestoneSelector2.php';
require_once 'presencetable.php';
require_once 'CheckTable.class.php';
require_once 'TemplateWith.php';
require_once 'rainbow.php';
requireScribeCap( $peer_id );

class MyRowHeaderBuilder implements RowHeaderBuilder {

    var $rainbow;
    var $grp_num=1;
    public function __construct() {
        $this->rainbow = new RainBow();
    }

    public function build( $valueArray ) {
        extract( $valueArray );
        $bg = $this->rainbow->getCurrent();
        $result = "\t\t<td>$snummer</td>\n" .
                "\t\t<td>$name</td>\n"
                . "\t\t<td style='background-color:{$bg}'>{$grp}</td>\n" .
                "\t\t<td onmouseover=" . '"balloon.showTooltip(event,\'<div><center style=\\\'font-weight:bold;\\\'>' .
                $name . '<br/><img src=\\\'' . $photo .
                '\\\'/></center></div>\')"' . "><img src='$photo' width='24' height='36' /></td>\n";
        if ($grp_num != $this->grp_num){
            $this->rainbow->getNext();
            
        }
        $this->grp_num=$grp_num;
        return $result;
    }

    public function buildHeader( $data ) {
        return "<th>snummer</th><th>Name</th><th>grp</th><th>pict</th>\n";
    }

}

class MyCellBuilder implements TableCellBuilder {

    public function build( $valueArray ) {
        $result = '';
        extract( $valueArray );
        if ( isSet( $valueArray[ 'title' ] ) ) {
            $title = " title='" . $valueArray[ 'title' ] . "' ";
            $class = "class='hasnote notered' ";
        } else {
            $class = $title = '';
        }
        $result .= "\t\t<td $class $title>" . $valueArray[ 'check' ] . "</td>\n";
        return $result;
    }

}

// get group tables for a project
$prj_id = 0;
$prjm_id = 0;
$milestone = 1;
$afko = 'PRJ00';
$description = '';
extract( $_SESSION );
$prjSel = new PrjMilestoneSelector2( $dbConn, $peer_id, $prjm_id );
//$prjSel->setJoin('milestone_grp using (prj_id,milestone) natural join activity_project ');
//$prjSel->setJoin('activity_project using(prj_id) join all_project_scribe using(prj_id) ');
//$prjSel->setWhere(' prjm_id in (select prjm_id from activity) and ' . $peer_id . '=scribe');
extract( $prjSel->getSelectedData() );
$_SESSION[ 'prj_id' ] = $prj_id;
$_SESSION[ 'prjm_id' ] = $prjm_id;
$_SESSION[ 'milestone' ] = $milestone;

$filename = 'presencelist_' . $afko . '-' . date( 'Ymd' ) . '.csv';

$csvout = 'N';
$csvout_checked = '';
if ( isSet( $_REQUEST[ 'csvout' ] ) ) {
    $csvout = $_REQUEST[ 'csvout' ];
    $csvout_checked = ($csvout == 'Y') ? 'checked' : '';
}

//pagehead2("Presence list to $afko $year $description");//,$scripts);

$prj_id_selector = $prjSel->getSelector();
$selection_details = $prjSel->getSelectionDetails();
$sql = "select snummer,roepnaam||coalesce(' '||tussenvoegsel||' ',' ')||achternaam as name,"
        . "datum||'#'||al.act_id||': '||short||' '||description as checktitle,\n"
        . "present as check, note as title,grp_num, agroup as grp,act_id,photo \n"
        . " from act_presence_list2 al join student_email st using(snummer) \n"
        . " join portrait tp using (snummer) \n"
        . " left join absence_reason ar using (act_id,snummer)\n"
        . " where prjm_id=$prjm_id and datum <= now()::date order by grp_num,achternaam,roepnaam,al.act_id\n";

$page = new PageContainer();
include 'js/balloonscript.php';

$page->setTitle( 'Overview of presence during activities' );
$page->addHeadComponent( new Component( "<style type='text/css'>
    *.notered { 
	background-image:url('images/redNote.png');
    }
    *.notegreen { 
	background-image:url('images/greenNote.png');
    }
    *.noteblue { 
	background-image:url('images/blueNote.png');
    }
 </style>" ) );
$page_opening = "Presence list for project $afko $description prjm_id $prjm_id prj_id $prj_id milestone $milestone";
$nav = new Navigation( $tutor_navtable, basename( __FILE__ ), $page_opening );
$page->addBodyComponent( $nav );
$tableBuilder = new CheckTable( $dbConn, new MyRowHeaderBuilder(), new MyCellBuilder() );
$presence_table = $tableBuilder->getTable( $sql, 'snummer' );
//$presence_table = checkTable($dbConn,$sql,0,4,5,6);
$templatefile = '../templates/presenceoverview.html';
$template_text = file_get_contents( $templatefile, true );
$text = '';
if ( $template_text === false ) {
    $text = "<strong>cannot read template file $templatefile</strong>";
} else {
    $text = templateWith( $template_text, get_defined_vars() );
}

$page->addBodyComponent( new Component( $text ) );
$page->addBodyComponent( new Component( '<!-- db_name=' . $db_name . '-->' ) );
$page->show();
?>
