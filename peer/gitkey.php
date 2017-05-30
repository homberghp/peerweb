<?php

require_once 'component.php';
require_once('navigation2.inc');
$page = new PageContainer();
$page_opening = "Manage you git keys";
$page->setTitle($page_opening);
$pp = array();
$nav = new Navigation(array(), basename($PHP_SELF), $page_opening);
$page->addBodyComponent($nav);
$pp['purpose'] = 'laptop';
$keydir = '/home/git/sandbox/gitolite-admin/keydir/' . $peer_id;

/*
 * if file uploaded. move it to some git sandbox keydir/<peer_id>/email1@purbose.pub file.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
if (isSet($_POST['btn']) && isSet($_FILES['keyfile']['name'])) {
    $f = $_FILES['keyfile']['name'];
    if (isSet($_POST['purpose'])) {
        if (preg_match('/[a-zA-Z0-9]{1,15}/', $_REQUEST['purpose'])) {
            $pp['purpose'] = $purpose = $_REQUEST['purpose'];
        }
        $keyfilename = $LOGINDATA['snummer'] . '@' . $purpose . '.pub';
        $tmp_file = $_FILES['keyfile']['tmp_name'];
        if (!is_dir($keydir) && !mkdir($keydir, 02775, true)) {
            die('cannot create dir ' . $keydir . '<br/>');
        }
        $dest_file = "{$keydir}/{$keyfilename}";
	  // verify or try to convert key to  acceptable to open-ssh
	$kinfo = @system("/home/git/bin/ensure_open_ssh_key $tmp_file",$retval);
	if ($retval === 0) {
	  if (!move_uploaded_file($tmp_file, $dest_file)) {
            die('cannot write file ' . $dest_file);
	  } else {
	    @system("/home/git/bin/ensure_open_ssh_key $dest_file");
	    @system("echo add key $keyfilename >> /home/git/incron/doit");
	  }
	}
    }
    // processed, goto self, dropping post data.
    header("Location: $PHP_SELF");
    exit(0);
}
if (isSet($_GET['deletefile'])  && (strpos($_GET['deletefile'], '..') === false)) {
    $delfilename = $_GET['deletefile'];
    @system("cd {$keydir}; git rm {$delfilename}");
    @system("echo rm key $delfilename >> /home/git/incron/doit");
    //    unlink($delfilename);
}

function sshkeyDetails($keyFileName) {
    ob_start();
    @system("ssh-keygen -l -f $keyFileName");
    return ob_get_clean();
}

$keyCount = 0;
$keytable = "<table class='simpletable' border='1' style='border-collapse:collapse'>\n<caption>Your current keys</caption>\n"
        . "<tr><th>file name on server</th><th>key timestamp</th><th>ssh key details</th><th>delete</th></tr>\n";
if ($dh = opendir($keydir)) {
    while (($filename = readdir($dh)) !== false) {
        if (preg_match('/\.pub$/', $filename)) {
            $filename;
	    $filepath="{$keydir}/{$filename}";
            $kdetails = sshkeyDetails($filepath);
            $fmtime = date ("Y-m-d H:i",filemtime($filepath));
            $keytable .= "<tr><td>$filename </td><td>$fmtime</td><td style='width:600px:word-wrap:break-word'><pre>{$kdetails}</pre></td>"
                    . "<td><form id='delete' action='$PHP_SELF' method='get'><input type='hidden' name='deletefile' value='$filename'/><input type='submit' name='deletBtn' value='Delete'/></form></td></tr>\n";
            $keyCount++;
        }
    }
    closedir($dh);
}
$keytable .= "\t\t</table>\n";

$pp['keytable'] = ($keyCount > 0) ? $keytable : '';

$page->addHtmlFragment('templates/gitkey.xhtml.php', $pp);
$page->show();


