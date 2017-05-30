<?php
function makeinputfor($name,$value,$hasCap=false,$size=20,$type='text'){
    $result ='';
    if ($hasCap) {
	$result ="<input type='$type' name='$name' value='$value' size='$size'/>\n";
    } else $result="$value";
    return $result;
}
?>
