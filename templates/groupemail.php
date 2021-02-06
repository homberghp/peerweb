<div id="content">
    <form method='post' name='project' action='<?= basename(__FILE__); ?>'>
        <?= $selWidget ?>
    </form>
    <fieldset><legend>Select Student or via role</legend>
        <form name='mailer' method='post' action='<?= basename(__FILE__) ?>'>
            <table><tr>
                    <td>
                        <p><strong>Note that a copy of that mail is sent to the tutor as well.</strong></p>
                        <?= $eTable ?>
                    </td><td valign='top'>
                        <p><strong>Select the addressees by group</strong></p>
                        <?= $groupTable ?>

			<p><strong>Select the addressees by their role</strong></p>
                        <?= $rTable ?>

                        <p><strong>Select the addressees by class</strong></p>
                        <?= $classTable ?>
                    </td></tr>
            </table>
            <div style='background-color:#eee' >
                <p>In the text below the following substitutions are available: <b>{$name}, {$firstname}, {$email}, {$snummer}, {$grp_num}, and {$grp_name}</b>.</p>
                <label for='formsubject' style='font-size:120%;font-weight:bold'>subject:</label>&nbsp;
                <input type='text' name='formsubject' id='formsubject'  size='120' value='<?= $formsubject ?>'/>
                <textarea class='tinymce' name='mailbody' cols='120' rows='20'><?= $mailbody ?></textarea>
                <input type='hidden' name='prjm_id' value='<?= $prjm_id ?>'/>
                <input type='submit' name='bsubmit' value='Send mail'/>
                <p>Note that you can change the default signature in 'personal data and settings &gt; email signature'</p>
                <!--            </td></tr>
                            </table>-->
            </div>
        </form>
    </fieldset>
</div>
