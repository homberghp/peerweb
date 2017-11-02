<html>
    <head>
        <title>You vote</title>
        <style type='text/css'>
            .odd {}
            .even{background:#ddf;} 
            form { width: 400px; }
            form table div { float: left; }
            form table div.full { clear: both; }
            form table div label { display: block; }
        </style>
    </head>
    <body>
        <?php
        if (isset($_REQUEST['submit'])) {
            echo "p0 = {$_REQUEST['p0']}</br>";
            echo "p1 = {$_REQUEST['p1']}</br>";
            echo "p2 = {$_REQUEST['p2']}</br>";
            echo "p3 = {$_REQUEST['p3']}</br>";
            echo "p4 = {$_REQUEST['p4']}</br>";
            echo "p5 = {$_REQUEST['p5']}</br>";
        }
        ?>
        <h1>your votes please</h1>
        <form id='votes' action="youvote.php" method='get'>
            <?php
            require_once'youtubelink.php';
            /*
             * To change this template, choose Tools | Templates
             * and open the template in the editor.
             */

// vote on youtube films
            $sql = "select * from grp_detail where prjm_id=516 and grp_name <> 'Attic' order by grp_num";
            $resultSet = $dbConn->Execute($sql);
            if ($resultSet === false) {
                die('cannot get project data:' . $dbConn->ErrorMsg() . ' with <pre>' . $sql . "</pre>\n");
            }
            $count = 0;
            echo "<table style='border-collapse:collapse' border='1'><tr><th>grp</th><th>name</th><th>tutor</th><th colspan='6'>rank<th></tr>\n";
            while (!$resultSet->EOF) {
                extract($resultSet->fields);
                $rowClass = ((++$count) % 2) == 0 ? 'even' : 'odd';
                echo "<tr class='$rowClass'><td>$grp_num</td><td>$grp_name</td><td>$tutor</td>\n";
                for ($i = 0; $i < 6; $i++) {
                    echo "\t\t<th><div><label for='p{$i}_{$prjtg_id}'>{$i}</label><input type='radio' "
                    . " name='p{$i}[]' id='p{$i}_{$prjtg_id}' style='vertical-align: middle' value='$prjtg_id' class='g$prjtg_id'/></div></th>\n";
                }
                echo "<td>" . youtubelink($youtube_link, $yt_id, '') . "</td>\n"
                . "</tr>\n";
                $resultSet->moveNext();
            }
            echo "</table>";
            ?>
            <input type='submit' name='submit'/>
        </form>
    </body>
</html>
