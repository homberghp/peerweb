<?php

/**
 * Template with a string and simple map.
 * @param string $template 
 * @param array $substitutions map of substitutions.
 * @return string with substitutions applied. 
 * 
 * Implementation notes: There is a little state machine with the following states:
 * 0 initial and passing characters
 * 1 found starting { in {$name}.
 * 2 found $ in {$name} and collecting key. stops at }
 * 3 escape char seen
 * 
 */
function templateWith(string $template, array $substitutions) {
    $state = 0; // forwarding
    $charIn = preg_split('//u', $template, -1, PREG_SPLIT_NO_EMPTY);
    $charOut = array();
    $count = count($charIn);
    $key = array();
    $i = 0;
    while ($i < $count) {
        con:
        $char = $charIn[$i];
        switch ($state) {
            case 0:
                switch ($char) {
                    case '\\':
                        $state = 3;
                        break;
                    case '{':
                        $state = 1;
                        break;
                    default:
                        $charOut[] = $charIn[$i];
                        break;
                }break;
            case 1:
                switch ($char) {
                    case '$':
                        $state = 2;
                        break;
                    default: $charOut[] = '{' . $char;
                        break;
                }
                break;
            case 2:
                switch ($char) {
                    case '}': // key complete 
                        $ks = join('', $key);
                        if (array_key_exists($ks, $substitutions)) {
                            $charOut[] = $substitutions[$ks];
                        }
                        $key = [];
                        $state = 0;
                        break;
                    default:
                        $key[] = $char;
                        break;
                }
                break;
            case 3:
                $charOut[] = $charIn[$i];
                break;
        }
        $i++;
    }

    return join('', $charOut);
}