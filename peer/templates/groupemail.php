<div id="content">
    <form method='post' name='project' action='<?= $PHP_SELF; ?>'>
        <?= $selWidget ?>
    </form>
    <fieldset><legend>Select student or via role</legend>
        <form name='mailer' method='post' action='<?= $PHP_SELF ?>'>
            <table><tr>
                    <td>
                        <p><strong>Note that a copy of that mail is sent to the tutor as well.</strong></p>
                        <?= $eTable ?>
                    </td><td valign='top'>
                        <p><strong>Select the addressees by their role</strong></p>

                        <?=$rTable?>
                        
                        <p><strong>Select the addressees by class</strong></p>
                        <?=$classTable?>
                    </td></tr>
            </table>
            <table border='1'>
                <tr>
                    <td valign='top' colspan='2'>
                        subject:&nbsp;<input type='text' name='formsubject' size='80' value='<?= $formsubject ?>'/>
                    </td>
                </tr>
                <tr><th valign='top' align='left'  colspan='2'>Body text:</th></tr>
                <tr><td valign='top' colspan='2'>
                        <textarea name='mailbody' cols='120' rows='20'><?= $mailbody ?></textarea></td></tr>
                <tr><td colspan='2'><input type='hidden' name='prjm_id' value='<?= $prjm_id ?>'/>
                        <input type='submit' name='bsubmit' value='Send mail'/></td>
                </tr>
            </table>
            <p>Note that you can change the default signature in 'personal data and settings &gt; email signature'</p>
            </td></tr>
            </table>
        </form>
    </fieldset>

</div>
