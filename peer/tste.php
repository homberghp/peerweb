<?php
requireCap(CAP_SYSTEM);

//session_start();
require_once("peerutils.php");
require_once("utils.php");
//require_once("nav62.php");
//requireCap(CAP_SYSTEM);
require_once("searchquery2.php");
require_once("ste.php");
//ini_set('error_reporting',2047);
//$style_color=0xC0E0FF;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META HTTP-EQUIV="Expires" CONTENT="0">
<link rel="stylesheet" type="text/css" href="style.php?style_color=<?=$style_color?>">
<script type="text/javascript" language="JavaScript" src="js/helpers.js"></script>
<?php
 //if ($am_user_blocked == 'J') {
 // require_once("blocked_tail.inc");
 // exit;
 //}
global $PHP_SELF;
$navTitle= "Prafda2 testscript ".$PHP_SELF." on DB ".$db_name;
?>
<title>
<?php echo $navTitle; ?>
</title>
</head>
<body>
<?php
  //navstart($navTitle,$PHP_SELF);
$ste = new SimpleTableEditor();
$ste->setFormAction(basename(__FILE__));
$ste->setRelation('student');
$ste->setMenuName('student');
/* Neem de KeyColumns over uit het schema */
$ste->setKeyColumns(array('snummer'));
/* De NameExpression wordt in de <a href....>NameExpression</a> gebruikt */
$ste->setNameExpression("rtrim(achternaam,' ')||', '||rtrim(roepnaam,' ')||' '||rtrim(tussenvoegsel,' ')");
/* sorteervolgorde */
$ste->setOrderList(array('achternaam','roepnaam','tussenvoegsel'));
/* html template file */
$ste->setFormTemplate('student.inc');
/* we halen informatie uit deze relatie (tabel of view) erbij */
$ste->setSupportingRelation('student');
/* die relatie moeten we er wel aan kunnen knopen */
//$ste->setSupportingJoinList(array('PEOPLE_MANAGER'=>'MEDEWERKER_CODE'));
/* Dit zie je in de zoeklijst */
//$ste->setListRowTemplate(array('AFDELING_NUMMER','PEOPLE_MANAGER','FUNCTIE','SOFTWAREHOUSE'));
/* andere knoppen template */

$ste->setButtonTemplate('tste_buttontemplate.inc');
/* extra inputs */
//$splitsWeek='<input type="text" name="Splitsweek" value="" size="8">';
//$ste->addButton(array('Splitsweek'=>$splitsWeek));
/* extraKnop */
//$ste->makeButton(array('name'=>'Splits','value'=>'Splits','accessKey'=>'S'));
/* laat zien */
$ste->processResponse();
/* De volgende regel geeft een voorbeeld hoe je bepaalde velden van waarde kunt veranderen.*/
/* Meestal zul je dit dus conditioneel gebruiken*/
//if($ste->getValue("FUNCTIE")=="CEO") {
//	$ste->setValue("ACHTERNAAM","Stevens");
//}
$ste->generateHTML();
echo '<br>student'.$ste->getValue('snummer');
/* kijk of mijn knop is uitgevoerd */
//if (isSet($_POST['Splits'])){
  // echo '<br>knippen maar';
  //  if ()
//}
?>
<?php
  //navEnd("einde ".$navTitle);
?>
</body>
<!-- $Id: tste.php 439 2010-08-24 08:09:12Z hom $ -->
</html>
