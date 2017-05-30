<?php
  // session_start();
include_once('./peerlib/peerutils.php');
if (isSet($_POST['peer_id']) && isSet($_POST['peer_pw'])) {
    $peer_id=$_POST['peer_id'];
    $peer_pw=$_POST['peer_pw'];
    if (authenticate($peer_id,$peer_pw)) {
	$_SESSION['auth_user']=$peer_id;
    } else {
	unSet($_SESSION['auth_user']);
    }
}
if (!isSet($_SESSION['auth_user'])) {
     header("Location: login.php");
     exit;
}
pagehead('welcome');
?>
<div id="content">
<fieldset valign="top">
<legend>Welkom <?=$_SESSION['auth_user']?> met wachtwoord <?=$_POST['peer_pw']?></legend>
Hier kun je kiezen tussen 
<ol><li>Het invullen van je formulier voor het beoordelen van je teamgenoten</li>
<li>en het bekijken van de beoordelingen die je van je teamgenoten hebt gekregen. Dit gaat alleen als alle teamgenoten hun formulier hebben ingestuurd.
</li>
<li>Uitloggen</li>
</ol>
</fieldset>
</div>
</body>
</html>
<?php
?>