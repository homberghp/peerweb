<?php

include_once('./peerlib/peerutils.php');
include_once('tutorhelper.php');
require_once('./peerlib/validators.php');
require_once './peerlib/component.php';
require_once 'document_access.php';
require_once 'selector.php';
$hasCapSystem = 1;
$prjm_id = 0;
extract( $_SESSION );

$sortorder = 'desc';
if (isSet($_REQUEST['sortorder'])) {
    if ($_REQUEST['sortorder'] == 'asc') {
        $sortorder = 'asc';
    }
}
$doc_id = 1;
$pp = array();
if (isSet($_REQUEST['doc_id'])) {
    $_SESSION['doc_id'] = $doc_id = validate($_REQUEST['doc_id'], 'doc_id', 1);
}
$pp['doc_id'] = $_SESSION['doc_id'];

$_SESSION['referer'] = $PHP_SELF;
$sql = "SELECT roepnaam as jroepnaam, tussenvoegsel as jtussenvoegsel,achternaam as jachternaam,email1 as jemail1, lang as jlang FROM student WHERE snummer=$peer_id";
$resultSet = $dbConn->Execute($sql);
if ($resultSet === false) {
    die('Error: ' . $dbConn->ErrorMsg() . ' with ' . $sql);
}
extract($resultSet->fields);
$pp = array_merge($pp, $resultSet->fields);
$lang = strtolower($jlang);
$critiquer = $_SESSION['peer_id']; // allways give critique as peerweb user

if (isSet($_POST['bsubmit'])) {
    $doc_id = validate($_POST['doc_id'], 'doc_id', 1);
    $critique_text = pg_escape_string($_REQUEST['critique_text']);
    $sql = "insert into document_critique (doc_id,critiquer,critique_text)\n" .
            "values ($doc_id,$critiquer,'$critique_text')";
    $resultSet = $dbConn->execute($sql);
    if ($resultSet === false) {
        echo 'Error: ' . $dbConn->ErrorMsg() . ' with <br/><pre>' . $sql . '</pre>';
    } else {
        // mail that a critique was added to uploader/author
        $sql = "select roepnaam,tussenvoegsel,achternaam,email1,email2 from student\n" .
                " left join alt_email using(snummer)\n" .
                "join uploads using(snummer) where upload_id=$doc_id";
        $resultSet = $dbConn->execute($sql);
        if ($resultSet === false) {
            echo 'Error: ' . $dbConn->ErrorMsg() . ' with <br/><pre>' . $sql . '</pre>';
        }
        extract($resultSet->fields, EXTR_PREFIX_ALL, 'author');
        $subject = 'You received feedback on one of your uploaded doucments at the peerweb';
        $body = "Dear $author_roepnaam,\n\n" .
                "You received feedback on the document you uploaded to the peerweb.\n" .
                "Have a look at\n$server_url$root_url/upload_critique.php?doc_id=$doc_id\nKind regards,\nThe peerweb service";
        $replyto = $jemail1;
        //	if ($isTutor)
        //  $replyto=$tutor_email;
        $headers = "From: peerweb@fontysvenlo.org\n" .
                "Reply-To: $replyto\n";
        $toAddress = $author_email1;
        domail($toAddress, $subject, $body, $headers);
        if (isSet($author_email2)) {
            $subject = "You received an email at your fontys email address";
            $body = "Dear $author_roepnaam,\n\n" .
                    "You received an email at your fontys email address. Have a look.\n" .
                    "Kind regards\nThe peerweb service";
            $toAddress = $author_email2;
            domail($toAddress, $subject, $body, $headers);
        }
    }
}

if (isSet($_POST['doc_update'])) {
    $doc_title = pg_escape_string($_POST['doc_title']); // remove nasty tokens!
    $groupRead = isSet($_POST['groupread']) ? 't' : 'f';
    $projectRead = isSet($_POST['projectread']) ? 't' : 'f';
    $rights = '{' . $groupRead . ',' . $projectRead . '}';
    $doctype = $_POST['doctype'];
    $mime_type = $_POST['mime_type'];
    $new_prjtg_id = $_POST['new_prjtg_id'];
    $sql = "update uploads \n"
            . "set title='$doc_title',"
            . "\t rights='$rights', "
            . "\t doctype=$doctype,"
            . "\t mime_type=substr('$mime_type',1,64),\n "
            . "\t mime_type_long='$mime_type',\n"
            . "\t prjtg_id=$new_prjtg_id\n "
            . "where upload_id=$doc_id";
    $dbConn->log($sql);
    $resultSet = $dbConn->execute($sql);
}
if (isSet($_REQUEST['delete_critique'])) {
    $critique_id = validate($_REQUEST['critique_id'], 'integer', 1);
    $sql = "update document_critique set deleted=true where critique_id=$critique_id";
    $resultSet = $dbConn->execute($sql);
}
// debug hack
//$_GET['doc_id'] = 1;
$page = new PageContainer();
$page->setTitle('Document and feedback viewer');

if (!isSet($_REQUEST['doc_id'])) {
    echo "<p>To critisize a document you should select a document from the <a href='uploadviewer.php'>documents page</a> " .
    "and click on the critisizeable document</p>";
} else { // a document is selected
    $doc_id = validate($_REQUEST['doc_id'], 'doc_id', 0);
    $sql = "select upload_id,title, to_char(uploadts,'YYYY-MM-DD HH24:MI')::text as uploadts,due,mime_type,\n"
            . "ups.snummer as author,achternaam,tussenvoegsel,roepnaam,apt.prj_id,apt.prjm_id,apt.milestone,apt.afko,apt.description as project_description,\n"
            . "apt.year,apt.grp_num, ups.prjtg_id,rel_file_path, coalesce(apt.alias,'g'||apt.grp_num) as grp_name,\n"
            . " coalesce('g'||apts.grp_num||' '''||apts.alias||'''','g'||apts.grp_num)||' tutor '||apts.tutor as sgrp_name,\n"
//            . "coalesce(apts.alias,'g'||apts.grp_num) as sgrp_name,\n"
            . "vers, pd.doctype,udt.description as documenttype,apt.long_name,ups.rights[0:2] as rights,\n"
            . "getDocAuthors($doc_id) as coauthors,filesize \n"
            . "from (uploads ups join student std using(snummer) \n"
            . "join all_prj_tutor apt using(prjtg_id)) \n"
            . "join (all_prj_tutor join prj_grp using (prjtg_id)) apts on (apts.snummer=ups.snummer and apts.prjm_id =ups.prjm_id)\n"
            . "join uploaddocumenttypes udt on (apt.prj_id=udt.prj_id and ups.doctype=udt.doctype) \n"
            . "join project_deliverables  pd on(apt.prjm_id=pd.prjm_id and ups.doctype=pd.doctype)"
            . " where upload_id=$doc_id";
    $resultSet = $dbConn->execute($sql);
    if ($resultSet === false) {
        die('Error: ' . $dbConn->ErrorMsg() . ' with <br/><pre>' . $sql . '</pre>');
    }
    if (!$resultSet->EOF) {
        extract($resultSet->fields);
        $author = $resultSet->fields['author'];
        $pp = array_merge($pp, $resultSet->fields);
        $rights = substr($rights, 1, strlen($rights) - 2);
        $rights = explode(',', $rights);
        if (isSet($long_name))
            $long_name = ', Group name "' . $long_name . '"';
        $pp['filename'] = $rel_file_path;
        $pp['filepath'] = $upload_path_prefix . '/' . $rel_file_path;
        $pp['title'] = stripslashes($title);
        $refreshUrl = htmlspecialchars("$PHP_SELF?doc_id=$doc_id&sortorder=$sortorder");
        $pp['downloadUrl'] = $root_url . "/downloader/$doc_id/" . $pp['filename'];
        $pp['file_size'] = $filesize;//@filesize($filepath);
        $pp['page_opening'] = "Hello $jroepnaam $jtussenvoegsel $jachternaam " .
                "<span style='font-size:6pt;'>($snummer)</span>, " .
                "this the critique page. ";
        $pp['mime_type_sel'] = $mime_type;
        $pp['grp_name'] = $grp_name;
        $pp['doc_type_sel'] = $documenttype;
        if (($author == $peer_id) || $hasCapSystem) {
            $pp['formstart'] = "<form method='post' action='" . $PHP_SELF . "?doc_id=" . $doc_id . "'>";
            $pp['formend'] = "</form>";
            $pp['titleinput'] = "<input type='text' size='80' name='doc_title' value='$title' />";
            $pp['docbutton'] = "<tr><td>To modify</td>" .
                    "<td><input type='submit' name='doc_update' value='update'/><input type='reset'/></td></tr>";
            $mime_type_selector =
                    new Selector($dbConn, 'mime_type',
                            "select mime_type as name, mime_type as value from upload_mime_types order by name",
                            $mime_type, false); // no auto submit.
            $doc_type_selector =
                    new Selector($dbConn, 'doctype',
                            "select doctype as value, description as name\n"
                            . " from uploaddocumenttypes where prj_id=$prj_id", $doctype, false);
            $pp['grp_sel'] = new Selector($dbConn, 'new_prjtg_id',
                            "select prjtg_id as value, coalesce('g'||grp_num||' '''||alias||'''','g'||grp_num)||' tutor '||tutor as name \n"
                            . "from all_prj_tutor where prjm_id=$prjm_id order by grp_num", $prjtg_id, false);
            $pp['mime_type_sel'] = $mime_type_selector->getSelector();
            $pp['doc_type_sel'] = $doc_type_selector->getSelector();
            $checkenable = '';
        } else {
            $pp['formstart'] = '';
            $pp['formend'] = '';
            $pp['titleinput'] = $title;
            $pp['docbutton'] = '';
            $pp['mime_type_sel'] = $mime_type;
            $pp['doc_type_sel'] = $doctype;
            $checkenable = 'disabled';
        }
        $pp['groupRights'] = "Group <input type='checkbox' name='groupread' value='t' " . (($rights[0] == 't') ? 'checked' : '') . " $checkenable />";
        $pp['projectRights'] = "Module participants<input type='checkbox' name='projectread' value='t' " . (($rights[1] == 't') ? 'checked' : '') . " $checkenable  />";

        $pp['refreshUrl'] = htmlspecialchars("$PHP_SELF?doc_id=$doc_id&sortorder=");
        if ($sortorder == 'desc') {
            $pp['refreshLink'] = "first (current) or <a href='" . $refreshUrl . 'asc' . "'>last</a>";
        } else {
            $pp['refreshLink'] = "<a href='" . $refreshUrl . 'desc' . "'>first</a> or last (current)";
        }
        $sql3 = "select distinct critiquer, roepnaam,tussenvoegsel,achternaam,critique_id,\n" .
                "date_trunc('seconds',ts) as critique_time,critique_text,\n" .
                "date_trunc('seconds',edit_time) as edit_time,\n" .
                "afko,year,grp_num as critiquer_grp,\n" .
                "history_count\n" .
                "from document_critique dcr\n" .
                "left join (select count(id) as history_count, critique_id \n" .
                "           from critique_history group by critique_id) ch using(critique_id)\n" .
                "join student st on (dcr.critiquer=st.snummer)\n" .
                "join uploads u on(dcr.doc_id=u.upload_id)\n" .
                "join all_prj_tutor prj using(prjm_id,prjtg_id) where doc_id=$doc_id and deleted=false \n" .
                "order by critique_id $sortorder";

        $resultSet = $dbConn->execute($sql3);
        if ($resultSet === false) {
            die('Error: cannot get critiquer data with ' . $dbConn->ErrorMsg() . ' with <br/><pre>' . $sql . '</pre>');
        }
        $critique_editor = 'critique_editor.php';
        $critiqueList = "<!-- start critiqueList-->";
        while (!$resultSet->EOF) {
            extract($resultSet->fields);
            $pp = array_merge($pp, $resultSet->fields);
            $pp['critique_text'] = stripslashes($pp['critique_text']);
            $div_head = "<div id='edit_form_" . $critique_id . "' class='critique' >";

            if ($peer_id == $critiquer) {
                $editor_inputs = "<input type='hidden' name='critique_id' value='$critique_id'/>" .
                        "<input type='hidden' name='doc_id' value='$doc_id'/>\n" .
                        "\t<button type='submit' name='delete_critique' value='$critique_id' onClick='return delete_crit($critique_id)'>Delete</button>\n" .
                        "\n\t<button type='submit' onClick='javascript:invoke_editor($critique_id,$doc_id,\"edit\")'>Edit</button>\n";
            } else {
                $editor_inputs = '';
            }
            $form_head = "\n<form id='delete_edit_form${critique_id}' method='get' action='$PHP_SELF'>\n";
            $legend_head = "<legend>Critique $critique_id by $roepnaam $tussenvoegsel $achternaam ($critiquer)</legend>\n" . $editor_inputs . "\n";
            $history_link = ($history_count > 0 ) ? "<a href='critique_history.php?critique_id=$critique_id' target='_blank'>$edit_time</a>" : "$edit_time";
            $critiqueList .="\n$div_head\n"
                    . "$form_head\n"
                    . "<fieldset>\n"
                    . "$legend_head\n"
                    . "  <table class='layout' summary='critiquer data'>\n"
                    . "<!--<tr><td>Critique id</td><th align='left'>$critique_id</th></tr>\n"
                    . "<tr><td>Critiquer</td><th align='left'>$roepnaam $tussenvoegsel $achternaam ($critiquer)</th></tr> -->"
                    . " <tr><td>Group</td><th align='left'>$critiquer_grp($afko $year ) </th></tr>"
                    . "<tr><td>Critique time</td><th align='left'>$critique_time</th></tr>"
                    . "<tr><td>Last edit</td><th align='left'>$history_link</th></tr>"
                    . "</table>\n"
                    . "$critique_text"
                    . "</fieldset>\n</form>\n</div>";
            $resultSet->moveNext();
        }
        $critiqueList .= "<!-- end critiqueList-->";
    }
}

$pp['critiqueList'] = $critiqueList;
if (authorized_document($critiquer, $doc_id)) {
    $fragment = 'templates/upload_critique.html';
} else {
    $fragment = 'templates/upload_critique_noaccess.html';
}
$page->addHtmlFragment($fragment, $pp);
$page->addHeadText('
        <script type="text/javascript">
          function bye(){
            opener.focus();
            opener.location.href="$referer?doc_id=$doc_id";
            opener.location.reload();
            self.close();
          }
          function delete_crit( crit ) {
            var Check = confirm("Do you want to delete this qritique?");
            if (Check == true) return true; else false;
          }
          function invoke_editor( crit,doc_id,cmd ) {
            window.open(\'critique_editor.php?critique_id=\'+crit+\'&cmd=\'+cmd+\'&doc_id=\'+doc_id, \'_blank\', \'width=800,height=670,scrollbars\');
          }
          function invoke_editor_new( doc_id ) {
            window.open(\'critique_editor.php?critique_id=new&doc_id=\'+doc_id,\'_blank\',\'width=800,height=670,scrollbars\');
          }
          function bye(){
            opener.focus();
            opener.location.href="<?= $referer ?>?doc_id=<?= $doc_id ?>";
            opener.location.reload();
            self.close();
          }
        </script>
      ');


$page->show();
?>
