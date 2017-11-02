<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once 'peerutils.php';
if (isSet($login_tutor)) {
    $homepage = 'tutorhome.php';
} else {
    $homepage = 'home.php';
}
?>
<html>                                                                                                                                                                             
    <head>                                                                                                                                                                         
        <title>PeerWeb Fontys Venlo Peerweb</title>
        <meta http-equiv='Window-target' content='_top'/>
        <link rel='icon' href='<?= IMAGEROOT ?>/favicon.ico' type='image/x-icon' />
        <link rel='Shortcut Icon' type='image/png' href='<?= IMAGEROOT ?>/favicon.png'/>
        <style type='text/css'>
            html, body {  height: 100%;  }
            iframe {height:100%; width:auto;border-collapse:collapse;}
            #resizer {position: absolute; top:0;left:0; width:230px;}
            #peertree {position: absolute; top:21px;left:0; width:230px;}
            #main-wrapper{ position: absolute; top:0; left:230px;right:0; height:100%}
            #main{ width:100%; height:100%;}

        </style>

    </head>
    <body class='<?= BODY_CLASS ?>' >
        <img id='resizer' src='images/peertreemenu.png' id='peertreemenuimg' border="1" onclick="resizeTree()" title="click to resize menu frame"/>
        <iframe id='peertree' frameborder='0' src='peertree.php' style='background-color: #fff;'>navigation</iframe>
        <div id='main-wrapper'>
            <iframe id='main'  frameborder='0' name='mainframe' style='background-color: #fff;z-index: 10;' id='mainframe' src='<?= $homepage ?>' >main</iframe> 
        </div>
    </body>
    <script type='text/javascript'>
            function resizeTree() {
        var fs = document.getElementById('main-wrapper');
            var bt = document.getElementById('resizer');
            if (fs) {
                var l = parseInt(fs.style.left);
                if (l > 30) {
                    fs.style.left = 25 + 'px';
                    bt.style.left = -205 + 'px';
                } else {
                    fs.style.left = 230 + 'px';
                    bt.style.left = 0 + 'px';
                }
            }
        }
    </script>
</html>

