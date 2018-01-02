<?php
//Define where you have placed the phptreeview folder.

define( 'PEERICONS', IMAGEROOT . '/' );
define( 'PEERSITE', $root_url . '/' );

include 'navtable.php';


$xajax = new xajax();
include(TREEVIEW_SOURCE . "ajax/ajax.php"); //Enables real-time update. Must be called before any headers or HTML output have been sent.
$xajax->processRequests();

//Define identify name(s) to your treeview(s); Add more comma separated names to create more than one treeview. The treeview names must always be unique. You can´t even use the same treeview names on different php sites. 

require_once'treebuilder.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html>
  <head>
    <title>Url TreeView</title>
    <?php $xajax->printJavascript( TREEVIEW_SOURCE . "ajax/framework" ); //Enables real-time update.  ?>

    <!-- some basic css properties to make it look ok -->
    <link href="<?php echo TREEVIEW_SOURCE; ?>css/style.css" rel="stylesheet" type="text/css"/>
  </head>
  <body class='<?= BODY_CLASS ?>' style='<?=BODY_BACKGROUND?>; padding:0;margin:0;z-index:-1'>
    <?php
//IMPORTANT! To be able to see the changes you have made to the code you have to clean the session.
//(By uncomment the line below during one page load). 
// Be sure to comment the line when publishing the treeview, or else the treeview won´t remember the old state throu page loads.
//unset($_SESSION["NodesHasBeenAddedUrl"]); 
    $peerTree = new TreeBuilder( $dbConn, $navtable, $tabInterestCount );
    $peerTree->buildTrees();
//Print the treeview.
    $peerTree->printTrees();

    $tdClass = 'navleft';
    $logoutlink = $root_url . '/logout.php';
    ?>
<div align='center' style='margin:5px 0 5px 0;' class='<?= $tdClass ?>'>Userid <?= $_SESSION['peer_id'] ?>,<?=$_SESSION['tutor_code']?>,<?= $_SESSION['userCap'] ?>    <a href='<?= $logoutlink ?>' title='click me to logout' target='_top'>
        <img src='<?= IMAGEROOT ?>/close_1.png' alt='logout' border='0'/>
      </a>
    </div>

    <div align='center'>
      <a href='tablecard.php' target='mainframe'><img border='0' 
                                                      title='Make your own tablecard' 
                                                      style='margin: 4px 0 4px 0;' alt='Make your own tablecard' src='images/pietje.png'/></a>
      <!--Some 'commercials'-->
      <a href='http://www.mozilla.org/products/firefox/' target='mainframe'>
        <img src='<?= IMAGEROOT ?>/product-firefox.png' border='0'
             alt='best viewed with mozilla or mozilla-firefox'
             title='best viewed with mozilla firefox'/>
      </a>
    </div>
    <div align='center' class='<?= $tdClass ?>'>
      <a href='http://www.cacert.org' title='free ssl ceritificates' 
         target='mainframe'><img align='bottom' src='images/small-ssl-security.png' border='0' alt='ca cert free certificate'/></a>
    </div>
        <hr/><div style='text-align:center' >
        <?=phpversion()?><br/><?=$_SERVER['SERVER_SOFTWARE']?>
	</div>
    </div>
  </body> 
</html>