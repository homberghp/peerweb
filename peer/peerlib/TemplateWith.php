<?php

/**
 * Template with a string and simple map.
 * @param string $template 
 * @param array $substitutions map of substitutions.
 * @return string with substitutions applied. 
 */
function templateWith(string $template, array $substitutions) {
    $state = 0; // forwarding
    $charIn = preg_split('//u', $template, -1, PREG_SPLIT_NO_EMPTY);
    $charOut = array();
    $count = count($charIn);
    $key = array();
    $i = 0;
    while ($i < $count) {
        $char = $charIn[$i];
        switch ($char) {
            case '{': if ($state === 0) {
                    $state = 1;
                }
                break;
            case '}':
                if ($state === 2) {
                    $ks = join('', $key);
                    if (array_key_exists($ks, $substitutions)) {
                        $charOut[] = $substitutions[$ks];
                    }
                    $key = array();
                    $state = 0;
                }
                break;
            case '$': if ($state === 1) {
                    $state = 2;
                }
                break;
            case '\\': if ($state === 0) {
                    $i++;
                    $charOut[] = $charIn[$i];
                }
                continue;
            default:
                switch ($state) {
                    default:
                    case 0: $charOut[] = $char;
                        break;
                    case 2: $key[] = $char;
                        break;
                }
        }
        $i++;
    }

    return join('', $charOut);
}
