<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="<?= STYLEFILE ?>">
        <title>Document downloader fails</title>
    </head>
    <body id='body' onload='init' class='<?= BODY_CLASS ?>'>
        <div id="content">
            <?= $thp ?>

            <div class='intro'>
                <form action="close.html">
                    <h1 style='color:white'>Document <?= $doc_id ?> is not available to you, <?= $student_name ?>.
                        <input type="image" src="<?= IMAGEROOT ?>/error.png" onClick='self.close()' alt="Close"></h1>
                </form>
            </div>
            <div class='navleft selected' style='padding:1em;'>
                File "<b><?= $fname ?></b>" is not accessible.<br/>
                This may have several reasons: 
                <ol>
                    <li>The document is simply not on the server.</li>
                    <li>You are not entitled to read the file, because either:
                        <ul>
                            <li>You are not the one that uploaded it <i>or</i></li>
                            <li>You are not tutor <i>or</i></li>
                            <li>You are not member of the project group that this document belongs to <i>or</i></li>
                            <li>You are participating in the project in a different and the document's due date has not yet expired,</li>
                            <li>The tutor selected to prevent group visibility before the due date</li>
                        </ul>
                    </li>
                    in no particular order.
                </ol>
            </div>
        </div>
    </body>
</html>
