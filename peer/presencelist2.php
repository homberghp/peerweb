<?php

requireCap( CAP_TUTOR );
require_once('validators.php');
require_once('navigation2.php');
include 'simplequerytable.php';
require_once 'rainbow.php';
require_once 'selector.php';
require_once 'studentpicker.php';
require_once 'prjMilestoneSelector2.php';
require_once 'TableBuilder.class.php';
requireScribeCap( $peer_id );

$prjm_id = 0;
$act_id = 1;
extract( $_SESSION );
//$dbConn->setSqlAutoLog(true);

$prjSel = new PrjMilestoneSelector2( $dbConn, $peer_id, $prjm_id );
$prjSel->setJoin( ' all_project_scribe aps using(prj_id) ' );
$prjSel->setSubmitOnChange( true );
$prjSel->setWhere( " {$peer_id} =aps.scribe and exists(select 1 from activity where prjm_id=pm.prjm_id)" );
extract( $prjSel->getSelectedData() );
$_SESSION[ 'prj_id' ] = $prj_id;
$_SESSION[ 'act_id' ] = $act_id;
$_SESSION[ 'prjm_id' ] = $prjm_id;
$_SESSION[ 'milestone' ] = $milestone;

if ( isSet( $_REQUEST[ 'act_id' ] ) ) {
    $_SESSION[ 'act_id' ] = $act_id = validate( $_REQUEST[ 'act_id' ], 'integer', $act_id );
} else if ( !defined( $_SESSION[ 'act_id' ] ) ) {
    // get last defined activity for project milestone
    $sql = "select max(act_id) as act_id from activity where prjm_id=$prjm_id";
    $rs = $dbConn->Execute( $sql );
    if ( !$rs->EOF ) {
        extract( $rs->fields );
        $_SESSION[ 'act_id' ] = $act_id;
    }
}
$script = <<<'STYLE'
        <style type='text/css'>
        div.presence{ background-color:rgba(255,0,0,0.6); }
        input[type=radio].p {
            background-color:green; color:green;
            border: 1px solid green;
            height:12px;
            width:12px;
            margin-right: 2px;
            content: " ";
            display:inline-block;
        }
        img.f{float:left; margin-right:5px}
        div.box{border: 1px solid black; padding:1em;margin:0.5em;}
        div.box > button {halign:center;}
        div.g{ 
           display: flex;
           flex-flow: column;
           flex-direction: row;
           flex-wrap: wrap;
           align-content: space-around;
           width:80%
        }
        div.grps{
          display: flex; 
          flex-flow: wrap; 
          flex-direction: row;
          align-content: space-around;
          justify-content: left; 
        }
        </style>
        STYLE;

pagehead2( 'Get presence list', $script );
$page_opening = "Presence list for students attending activities xyz";
$nav = new Navigation( $tutor_navtable, basename( __FILE__ ), $page_opening );
$nav->setInterestMap( $tabInterestCount );
$nav->show();

$sql3 = "select datum||'@'||start_time||', '||' ('||act_id||', #'||coalesce(apc.count,0)||') '||act_type_descr||' '||rtrim(short)" .
        "||'*'||part||': '||rtrim(description) as name, act_id as value," .
        "to_char(datum,'IYYY')||':'||milestone as namegrp\n" .
        " from activity join activity_type using(act_type) join prj_milestone using(prjm_id) " .
        "left join act_part_count apc using(act_id) \n\t" .
        " where prjm_id=$prjm_id\n" .
        "order by namegrp desc,datum desc,start_time asc,part asc";
$actSel = new Selector( $dbConn, 'act_id', $sql3, $act_id );
$actSel->setAuto_submit( true );
$act_id_selector = $actSel->getSelector();

// candidates
$sql4 = <<<"SQL"
        select s.snummer, regexp_replace(s.achternaam,'\s+','&nbsp;') as achternaam, regexp_replace(s.roepnaam,'\s+','&nbsp;') as roepnaam,pt.grp_num,
        '<img src="'||photo||'" width=''32'' height=''auto'' valign=''bottom'' class=''f''/>' as face,
        ap.presence,ar.reason as comment
        from student s
        join  prj_grp pg using(snummer) 
        join prj_tutor pt using(prjtg_id)
        left join (select snummer,presence from activity_participant where act_id={$act_id}) ap using(snummer)
        left join (select snummer,reason   from absence_reason       where act_id={$act_id}) ar using(snummer) 
        natural join portrait
        where prjm_id={$prjm_id}
        
        order by grp_num, achternaam,roepnaam
SQL;
$participants = '';
$resultSet = $dbConn->Execute( $sql4 );

function groupForm( $rowColor, $oldGroup, $prjtg_id, $members ): string {
    return <<<"HTML"
   <fieldset class='group' style='background-color:{$rowColor}'><legend>Group {$oldGroup}</legend>
      <form name='presence'>
      <input type='hidden' name='prjg_id' value='{$prjtg_id}'/>
     <div class='g'> 
      {$members}
      </div>
      <button type='submit' >Submit</button>
      </form><!-- end form {$prjtg_id}-->
   </fieldset>
      
HTML;
}

$rainbow = new RainBow();
if ( $resultSet === false ) {
    print "error fetching participant data with $sql : " . $dbConn->ErrorMsg() . "<br/>\n";
} else {
    $oldGroup = 0;
    $pgcount = 0;
    $gm = '';
    $rowColor = $rainbow->getCurrent();
//    $rowColor='yellow';
    while ( !$resultSet->EOF ) {
        extract( $resultSet->fields );

        if ( $oldGroup != $grp_num && $pgcount ) {
            $participants .= groupForm($rowColor, $oldGroup,$prjtg_id,$gm);
            $rowColor = $rainbow->getNext();
            $gm = '';
            $pgcount = 0;
        }
        $oldGroup = $grp_num;
        $divClass = 'absent';
        $checkX = '';
        if ( $presence == 'P' ) {
            $divClass = 'present';
        } else if ( $presence == 'A' ) {
            $divClass = 'reason';
        } else {
            $checkX = 'checked';
        }
        $checkP = $presence == 'P' ? 'checked' : '';
        $checkA = $presence == 'A' ? 'checked' : '';

        $gm .= <<<"HTML"
                <div class='{$divClass} box'>
                     <div>{$face}&nbsp;{$snummer}<br/>{$roepnaam}</br> {$achternaam}</div>
                     <input type='radio' class='a' name='m_{$snummer}' value='' $checkX/>
                     <input type='radio' class='p' name='m_{$snummer}' value='P' $checkP/>
                     <input type='radio' class='r' name='m_{$snummer}' value='A' $checkA/>
                </div><br/>

HTML;
//        echo $gm;
        $pgcount++;

        $resultSet->moveNext();
    }

    if ( $pgcount ) {
        $participants .=  groupForm($rowColor, $oldGroup,$prjtg_id,$gm);
    }
}



include '../templates/presencelist2.html';
