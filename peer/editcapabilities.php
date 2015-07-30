<?php
require_once './peerlib/peerutils.inc';
include_once('navigation2.inc');
requireCap(CAP_EDIT_RIGHTS);
require_once 'bitset.php';
require_once 'studentpicker.php';
$newsnummer=$peer_id;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$cap_names = array('CAP_TUTOR', //0 
		   'CAP_MKPROJECT', // 1
		   'CAP_MKCLASSES', //2
		   'CAP_ALTER_STUDENT', //3 
		   'CAP_ALTER_STUDENT_CLASS',//4 
		   'CAP_LOOKUP_STUDENT', //5
		   'CAP_IMPERSONATE_STUDENT', // 6
		   'CAP_TUTOR_OWNER',//7
		   'CAP_RECRUITER',//8 
		   'CAP_STUDENT_ADMIN', //9
		   'CAP_TUTOR_ADMIN',//10
		   'CAP_SUBVERSION',//11
		   'CAP_SHARING',//12
		   'CAP_JAAG',//13
		   'CAP_SYSTEM', //14
		   'CAP_MENU_ADMIN',//15
		   'CAP_EDIT_RIGHTS',//16
		   'CAP_MODULE',//17 module management
		   'CAP_GIT',//18 git use
		   'CAP_SELECT_ALL',//19
		   'CAP20',//20
		   'CAP21',//21
		   'CAP22',//22
		   'CAP23',//23
		   'CAP24',//24
		   'CAP25',//25
		   'CAP26',//25
		   'CAP27',//27
		   'CAP28',//28
		   'CAP29',//29
		   'CAP_BIGFACE',//30 FIBS smoelenboek
		   'CAP31',//31
		   );
$caps = 0;
$dbMsg='';
extract($_SESSION);
//        if (isSet($_REQUEST['capuserid'])) {
//            $capuserid = $_REQUEST['capuserid'];
//        }
$studentPicker = new StudentPicker($dbConn, $newsnummer, 'Search user.');
$studentPicker->setShowAcceptButton(false);
$studentPicker->setPresentQuery("select userid as snummer from passwd");

$newsnummer = $studentPicker->processRequest();
$student_picker_text = $studentPicker->getPicker();

if (isSet($_REQUEST['capability'])) {
  $caps = collectBitSet($_REQUEST['capability']);
 }

if (isSet($_REQUEST['setcap']) && isSet($newsnummer) && $newsnummer != 0) {
  $sql = "update passwd set capabilities=$caps where userid=$newsnummer";
  $resultSet = $dbConn->Execute($sql);
  //            $dbConn->log($sql);
  if ($resultSet === false) {
    $dbMsg = "error cause " + $dbConn->ErrorMsg();
  }
 }

$sql = "select capabilities from passwd where userid=$newsnummer";


$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
  $dbMsg .= "error cause " + $dbConn->ErrorMsg();
 } else {
  $caps = $resultSet->fields['capabilities'];
 }
$cap_widget = mkbitsetFields('capability', $caps, $cap_names);
$col = 0;
$bitset_widget= "<table>\n";
foreach ($cap_widget as $name => $widget) {
  if (($col % 4) == 0) {
    $bitset_widget .= "<tr>\n";
  }
      $bitset_widget .=   "\t${widget}\n";
  $col++;
  if (($col % 4) == 0) {
        $bitset_widget .=  "</tr>\n";
  }
}

$bitset_widget .=   "</table>";

pagehead2('Edit capabilities');
$page_opening="Set the capabilities of a peerweb user.";
$nav=new Navigation($tutor_navtable, basename($PHP_SELF), $page_opening);
echo $nav->show();

include_once 'templates/editcapabilities.xhtml';
?>
</body>
</html>
