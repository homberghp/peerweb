<?php
include_once('./peerlib/peerutils.inc');
require_once('./peerlib/validators.inc');
include_once('navigation2.inc');
require_once './peerlib/simplequerytable.inc'; 
require_once 'SpreadSheetWriter.php';
$scripts='<script type="text/javascript" src="js/jquery.js"></script>
    <script src="js/jquery.tablesorter.js"></script>
    <script type="text/javascript">                                         
      $(document).ready(function() {
           $("#myTable").tablesorter({widgets: [\'zebra\']}); 
      });

    </script>
    <link rel=\'stylesheet\' type=\'text/css\' href=\''.SITEROOT.'/style/tablesorterstyle.css\'/>
';
$csvout='N';
$csvout_checked='';
if (isSet($_REQUEST['csvout'])) { 
    $csvout=$_REQUEST['csvout'] ;
    $csvout_checked = ($csvout=='Y')?'checked':'';
 }

// <a href='../emailaddress.php?snummer=snummer'>snummer</a>

$fdate=date('Ymd-Hi');
$filename='jaag_buit-'.$fdate;
$sql = "select * from jaag_buit_html";
$sqlcsv= "select * from jaag_buit";
$spreadSheetWriter = new SpreadSheetWriter($dbConn, $sqlcsv);


$spreadSheetWriter->setFilename($filename)
        ->setTitle("Jaag buit $fdate")
        ->setLinkUrl($server_url . $PHP_SELF )
        ->setFilename($filename);

$spreadSheetWriter->processRequest();
$spreadSheetWidget = $spreadSheetWriter->getWidget();

pagehead2('Jaag Buit',$scripts);
$page_opening="Resultaat van jaagactie tot nu toe";
$nav=new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);

?>
<?=$nav->show()?>
<div id='navmain' style='padding:1em;'>
<fieldset><legend>Select output type</legend>
<form method="get" name="project" action="<?=$PHP_SELF;?>">
<input type='submit' name='get' value='Get' />
  Excel file: 
<?= $spreadSheetWidget ?>
</form><br/>
</fieldset>
<div align='center'>
    <?=simpletable($dbConn,$sql,"<table id='myTable' summary='candidates' class='tablesorter' ".
		   "style='empty-cells:show;border-collapse:collapse' border='1'>");?>
</div>
</body>
</script>
</html>
<?php
echo "<!-- dbname=$db_name -->";
?>