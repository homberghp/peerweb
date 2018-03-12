<?php
require_once 'ste.php';
require_once 'GroupPhoto.class.php';
require_once 'prjMilestoneSelector2.php';
require_once('querytotable.php');

$title = "Project group details";

$prj_id = 1;
$milestone = 1;
$prjm_id = 0;
extract($_SESSION);

function getOrNull($arr, $g, $n) {
    if (isSet($arr[$n][$g]) && ($arr[$n][$g] !== '')) {
        return '\'' . pg_escape_string($arr[$n][$g]) . '\'';
    } else {
        return 'null';
    }
}

if (isSet($_POST['submit'])) {
    $sql = "begin work;\n"
            . "delete from grp_alias where prjtg_id in (select prjtg_id from prj_tutor where prjm_id={$prjm_id});\n";
    $grps = count($_POST['prjtg_id']);
    /* echo "<pre>"; */
    /* print_r($_POST); */
    /* echo "</pre>"; */

    for ($g = 0; $g < $grps; $g++) {
        $long_name = getOrNull($_POST, $g, 'long_name');
        $alias = getOrNull($_POST, $g, 'alias');
        $website = getOrNull($_POST, $g, 'website');
        $productname = getOrNull($_POST, $g, 'productname');
        $prjtg_id = getOrNull($_POST, $g, 'prjtg_id');
        $youtube_link = getOrNull($_POST, $g, 'youtube_link');
        $youtube_icon_url = getOrNull($_POST, $g, 'youtube_icon_url');
        if ($prjtg_id != 'null') {
            $sql .="insert into grp_alias (prjtg_id,alias,long_name,website,productname,youtube_link,youtube_icon_url)\n"
                    . "\nvalues ($prjtg_id,$alias,$long_name,$website,$productname,$youtube_link,$youtube_icon_url);\n";
        }
    }
    $sql .= "commit;\n";
//    "<pre>$sql</pre>";
    $resultSet = $dbConn->Execute($sql);
    if ($resultSet === false) {
        die("<br>Cannot set grop details with <pre>" . $sql . "</pre> reason " . $dbConn->ErrorMsg() . "<br>");
    }
}
$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
$pSel = $prjSel->getWidget();
extract($prjSel->getSelectedData());
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;
$doctype_set = array();
$sql = "select pt.grp_num,ga.* from prj_tutor pt left join grp_alias ga using(prjtg_id) where prjm_id=$prjm_id order by grp_num";
$inputColumns = array(
    '1' => array('type' => 'T', 'size' => '40'),
    '2' => array('type' => 'T', 'size' => '15'),
    '3' => array('type' => 'T', 'size' => '64'),
    '4' => array('type' => 'N', 'size' => '64'),
    '5' => array('type' => 'H', 'size' => '0'),
    '6' => array('type' => 'T', 'size' => '64'),
    '7' => array('type' => 'T', 'size' => '64'),
);
$table = getQueryToTableChecked2($dbConn, $sql, false, -1, new RainBow(0x46B4B4, 64, 32, 0), 'document[]', $doctype_set, $inputColumns);

//$table = simpleTableString($dbConn, $sql, "<table id='myTable' class='tablesorter' summary='your requested data'"
//        . " style='empty-cells:show;border-collapse:collapse' border='1'>");
$scripts = '<script type="text/javascript" src="js/jquery.js"></script>
    <script src="js/jquery.tablesorter.js"></script>
    <script type="text/javascript">                                         
      $(document).ready(function() {
           $("#myTable").tablesorter({widgets: [\'zebra\']}); 
      });

    </script>
    <link rel=\'stylesheet\' type=\'text/css\' href=\'' . SITEROOT . '/style/tablesorterstyle.css\'/>
';
pagehead2('Get class list', $scripts);
$page_opening = "Group details for project ";
$nav = new Navigation(array(), basename($PHP_SELF), $page_opening);
$nav->show();
?>
<div id='navmain' style='padding:1em;'>
    <?= $pSel ?>
    <form method="post" action="<?= $PHP_SELF; ?>" >
        <?= $table ?>
        <input type='reset' name='reset' value='Reset Form'/>
        <input type='submit' name='submit' value='Submit Form'/>
    </form>
</div>



