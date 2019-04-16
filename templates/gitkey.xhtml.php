<div class="main" style='padding:2em'>
    <fieldset ><legend>Manage keys</legend>
        <div style='width:750px'>
            <p>You appear to have the rights to use git in this server. This
                page allows you to manage your git ssh keys.</p>
            <p>This server uses ssh as the primary git access protocol. To be able to use ssh, you 
                must create a public/private key pair and hand the public key to the server using this web page</p>
            <h3>Key and purpose</h3>
            <p>You may have multiple key files, one for each purpose, for instance if you use different computers or OS-es (think dual boot computers
                or virtual machines) to
                <i>git push</i> from.<br/>
                To distinguish the purposes you can (and should) add a purpose name, which defaults to 'laptop'. You may only
                use names made of the characters [A-Za-z0-9-_] and a name length of 1 to 15.</p>
            <p><strong>Do not upload key files with different  purpose but the same content.</strong><br/>
                If you use the same key pair on all your computers and OS-es, one will suffice.</p>
            <p>All keys will be mapped to your peerweb <code>email1</code> field as git username (<code style="color:#008;font-family:courier;font-weight: bold;"><?= $LOGINDATA['email1'] ?></code>).
                This is the username under which git records your contributions.
                
            </p>
            <p>It is a good practice to add a comment to the key files as a reminder to where they come from. The comment will be visible as the last field but one in the ssh key details column.</p>
            <p>A workable reference to openssh ssh-keygen can be found at: <a href='http://en.wikipedia.org/wiki/Ssh-keygen' target='_blank'>Wikipedia on ssh-keygen</a></p>
   <strong><p>Make sure the key you upload is in open-ssh pub key format, which you can verify by calling (open-ssh) <br/>
<span class='journalbox'>ssh-keygen -l -f "filename.pub"</span> on it. If the file is not in that format, the script will make one attempt to convert it from 'putty ssh-key' format to open-ssh and on failure drop the uploaded file silently.</p></strong> 
        </div>
        <?= $keytable ?>
        <form enctype="multipart/form-data" action='<?= basename(__FILE__) ?>' method="post">

            <input type="hidden" name="MAX_FILE_SIZE" value="768" />

            <table border='0'>
                <tr><th><label for ='purpose' >Purpose of key</label></th>
                    <td><input name='purpose' id='purpose' type='text' required="true" width='15' pattern='[A-Za-z0-9-_]{1,15}'  value='<?= $purpose ?>' placeholder="laptop"/></td></tr>
                <tr><th><label for ='keyfile' style='width:10em'>Local key file to upload:</label></th>
                    <td><input name="keyfile" type="file" id='keyfile'  accept=".pub" style='width:64em'/></td></tr>
                <tr><th><label for ='btn' style='width:10em'>Press to </label></th>
                    <td><input type="submit" name='btn' id='btn' value="Upload Key File" /></td></tr>
            </table>
        </form>
    </fieldset>
</div>
