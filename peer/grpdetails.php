<?php
requireCap(CAP_TUTOR);
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
        return null;
    }
}

if (isSet($_POST['submit'])) {

    $grps = count($_POST['prjtg_id']);
    if ($grps > 0) {
        $sql = <<<'SQL'
insert into grp_alias (prjtg_id,alias,long_name,website,productname,youtube_link,youtube_icon_url)
    values($1,$2,$3,$4,$5,$6,$7)
    on conflict(prjtg_id) do update set 
    (alias,long_name,website,productname,youtube_link,youtube_icon_url)=
    (EXCLUDED.alias,EXCLUDED.long_name,EXCLUDED.website,EXCLUDED.productname,EXCLUDED.youtube_link,EXCLUDED.youtube_icon_url);
SQL;
        $dbConn->Execute("begin work;");
        try {
            $statement = $dbConn->Prepare($sql);

            for ($g = 0; $g < $grps; $g++) {
                $long_name = $_POST['long_name'][$g];
                $alias = $_POST['alias'][$g];
                $website = $_POST['website'][$g];
                $productname = $_POST['productname'][$g];
                $prjtg_id = $_POST['prjtg_id'][$g];
                $youtube_link = $_POST['youtube_link'][$g];
                $youtube_icon_url = $_POST['youtube_icon_url'][$g];
                
                $statement->execute([$prjtg_id, $alias, $long_name, $website, $productname, $youtube_link, $youtube_icon_url]);
            }
            $dbConn->Execute("commit;");
        } catch (Exception $se) {
            $dbConn->Execute("rollback;");
        }
    }
}

$prjSel = new PrjMilestoneSelector2($dbConn, $peer_id, $prjm_id);
$pSel = $prjSel->getWidget();
extract($prjSel->getSelectedData());
$_SESSION['prj_id'] = $prj_id;
$_SESSION['prjm_id'] = $prjm_id;
$_SESSION['milestone'] = $milestone;
$doctype_set = array();
$sql = "select pt.grp_num, pt.prjtg_id, ga.long_name, ga.alias, ga.website, ga.productname,\n"
        . "ga.youtube_link ,ga.youtube_icon_url\n".
        " from prj_tutor pt left join grp_alias ga using(prjtg_id) where prjm_id=$prjm_id order by grp_num";
//echo "<pre>$sql</pre>";
$inputColumns = array(
//    '0' => array('type' => 'N', 'size' => '6'), // grpnum
    '1' => array('type' => 'H', 'size' => '6'), // prjtg_id
    '2' => array('type' => 'T', 'size' => '40'), // long_name
    '3' => array('type' => 'T', 'size' => '15'), // alias
    '4' => array('type' => 'T', 'size' => '64'), // website
    '5' => array('type' => 'T', 'size' => '64'), // product_name
    '6' => array('type' => 'T', 'size' => '64'), // yt link
    '7' => array('type' => 'T', 'size' => '64'), // yt_url
);
$table = getQueryToTableChecked2($dbConn, $sql, false, -1, new RainBow(0x46B4B4, 64, 32, 0), 'document[]', $doctype_set, $inputColumns);

//$table = simpleTableString($dbConn, $sql, "<table id='myTable' class='tablesorter' summary='your requested data'"
//        . " style='empty-cells:show;border-collapse:collapse' border='1'>");
$scripts = '<script type="text/javascript" src="js/jquery.min.js"></script>
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



