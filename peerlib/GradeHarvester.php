<?php

/**
 * Harvest a set of grades from a textarea.
 * Usefull to bulk-import student grades through copy and paste.
 * Returns an array snummer -> grade.
 * 
 * Input pattern for grades (10|digit([.,]digit)?) which should fit 0, 0.0 .. 9.9 and 10.
 * 
 * 
 */

function harvest($input) {
    $result = array();
    $pattern = '^(\d{7})\s.+?\s(10|\d(\.\d)?)$/';
    $lines=explode("\n", str_replace(array("\n", "\r\n"), "\n", $input));

    foreach ($lines as $line) {
        $matches = array();
        if (preg_match('/^(\d{6,7}).*?\s(10|\d([\.,]\d)?)$/', $line,$matches)){
            $snummer = $matches[1];
            $grade = preg_replace('/,/','.',$matches[2]);
            $result[$snummer] = $grade;
        }
    }
    return $result;
}