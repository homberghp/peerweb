<?php

/**
 * Use regex to create youtubelink from url with video icon.
 * @param string $url to youtube page.page
 * @param string link text.
 * @param string alt text for image.
 */
function youtubelink($url, $yt_id, $linktext = 'on youtube', $alt = 'youtube img') {
    $link = '';
    if ($url !== '' && $yt_id !='') {
        $link = "<a href='$url' target='_blank' title='watch in new window'><img src='images/ytplay.png' alt='Y' valign='middle' border=0/>&nbsp;&nbsp;<img src='http://i4.ytimg.com/vi/{$yt_id}/default.jpg' "
                . "alt='$alt' halign='center' valign='middle'/>&nbsp;$linktext</a>";
    }
    return $link;
}

?>
