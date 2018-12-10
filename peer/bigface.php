<?php
requireCap(CAP_SYSTEM);

$maxcol = 6;
$maxrow = 3;
$pagesep = '';
$dbConn = pg_connect( "dbname=peer2 port=5432 user=bigface password=bigface" );
if ( !$dbConn ) {
    echo "Connection failed error occured.\n";
    exit;
}

$sql = "select achternaam,roepnaam,tussenvoegsel, nickname,office_phone,\n"
        . "team,building,room,display_name,image\n"
        . "from bigface_view\n"
        . "order by achternaam,roepnaam\n";
$rs = pg_query( $dbConn, $sql );
if ( !$rs ) {
    echo "query failed\n";
    echo $sql;
    exit;
}
$result = ''; //"<div >\n";
$row = $col = 0;
while ( $record = pg_fetch_assoc( $rs ) ) {
    extract( $record );
    if ( $row == 0 && $col == 0 ) {
        $result .="<li>\n<table class='overview' description='like a facebook'>"
                . "<colgroup><col width='12%'/><col  width='12%'/><col width='12%'/>"
                . "<col width='12%'/><col width='12%'/><col width='12%'/></colgroup>\n";
    }
    If ( $col == 0 ) {
        $result .= "\t<tr>\n";
    }
    $result .= "\t\t<td class='p1' align='center'>$image<br/>"
            . "<span class='displayname'>$display_name<br/>room $building &nbsp;$room<br/>tel $office_phone</td>\n";
    $col++;
    if ( ($col % $maxcol) == 0 ) {
        $result .="\t<tr>\n";
        $row++;
        if ( ($row % $maxrow) == 0 ) {
            $result .="</table>\n</li>\n";
            $row = 0;
        } else {
            $result .="\t<tr>\n";
        }
        $col = 0;
    }
}
if ( ($col % $maxcol) != 0 ) {
    while ( ($col % $maxcol) != 0 ) {
        $result .="\t\t<td>&nbsp</td>\n";
        $col++;
    }
    $result .="\n</tr>\n";
}
if ( ($row % $maxrow) == 0 ) {
    $result .="</table>\n</li>\n";
}
?>
<!DOCTYPE html>
<html>
    <head>
        <script src="jquery-min.js"></script>
        <script src="bjqs-min.js"></script>
        <link type="text/css" rel="Stylesheet" href="bjqs.css" />
        <style type='text/css'>
            body{background:#006; font-family:verdana; }
            .displayname {font-weight:bold; color:white;}
            table {height:100%; width:100%; }
            img {width:200px; height:auto;border-radius:15px;}
            .p1 { padding: 0 40px 0 40px;}
            div.main {display:table-cell; vertical-align:middle; width:100%; }
        </style>
    </head>
    <body style="height:100%">
    <center>
        <div class="topbar" height="200px">
        </div>
        <div class='main' id='bjqs'>
            <ul class='bjq22s' id='bjqs2'>
                <?= $result ?>
            </ul>
        </div>
    </center>
    <script language='text/javascript'>
        jQuery(document).ready(function($) {
            $('#bjqs').bjqs({
                'height' : 1000,
                'width' : 1600,
                'responsive' : true
            });
        });
    </script>
</body>
</html>
