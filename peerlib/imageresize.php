<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Resizes jpeg image to have hax max_side sides
 * @param type $source file path
 * @param type $max_side  pixels size of w or h max.
 * @return new filesize or -1 if not resized.
 */
function imageresize($source, $max_side = 1000) {
    $result = -1;
    // verify it is an image
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $source);
    if ('image/jpeg' == $mime_type) {
        // determine if its dimensions exceeds max_size
        list($width, $height, $type, $attr) = getimagesize($source);
        $maxDim = max($width, $height);
        if ($maxDim > $max_side) {
	  $newWidth = round(($max_side * $width) / $maxDim);
	  $newHeight = round(($max_side * $height) / $maxDim);
            $newSize = $newWidth . 'x' . $newHeight;
            // create new name from source
            $filename_pieces = explode('.', $source);
            $filename_pieces[count($filename_pieces) - 2] .= '-resized';
            $newImgFilename = implode('.', $filename_pieces);

            // do the conversion and move the file
            @`/usr/bin/convert -geometry $newSize $source $newImgFilename`;
            @`mv $newImgFilename $source`;
            $result = filesize($source);
        }
    }
    return $result;
}

?>
