<fieldset><legend>Grade uploader</legend>
    <?php if ($showInput) { ?>
        <form name='upload' method="post" action="<? $PHP_SELF ?>">
            <p>Choose and <b>event code</b>, typically progress-code -date and then copy and paste your set of grades into the window below.</p><br/>
            <label for='event'>Event code</label><input type='text' id='event' name='event' value='<?= $event ?>' placeholder='PRO1-20131231'/> <br/>
            <textarea name='grades' cols="80" rows="20"></textarea>
            <br/>
            <input type='submit' name='submit' value='submit'/>
        </form>
    <?php } else { ?>
        <h2>grades found for event <b><?= $event ?></b></h2>
        <form name='upload' method="post" action="<? $PHP_SELF ?>">

            <?= $rows ?>
            <input type='submit' name='commit' value='commit'/>

        </form>
    <?php } ?>
