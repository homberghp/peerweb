<fieldset><legend>Grade Uploader</legend>
    <?php if ($showInput) { ?>
        <form name='upload' method="post" action="<?= basename(__FILE__) ?>">
            <p>Choose and <b>event code</b>, typically progress-code -date and then copy and paste your set of grades into the window below.<br/>
                Note that any grade from the same (<b>student, exam event</b>) will overwrite previous grades.</p><br/>
            <label for='event'>Event code</label><?= $event_selector ?><br/>
            <textarea name='grades' cols="80" rows="40"></textarea>
            <br/>
            <input type='submit' name='submit' value='submit'/>
        </form>
    <?php } else { ?>
        <h2>grades found for event <b><?= $event_code ?></b></h2>
        <form name='upload' method="post" action="<?= basename(__FILE__) ?>">

            <?= $rows ?>
            <input type='hidden' name='exam_event' value='<?= $exam_event ?>'/>
            <input type='submit' name='commit' value='commit'/>

        </form>
    <?php } ?>
