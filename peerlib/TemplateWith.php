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
function templateWith( string $charIn, array $substitutions ): string {
    $state = 'A'; // forwarding
    $charOut = '';
    $count = strlen( $charIn );
    $key = '';
    $keyStart = -1;
    $keyLen = 0;
    $start = $len = $i = 0;
    while ( $i < $count ) {
        $char = $charIn[ $i ];
        switch ( $state ) {
            case 'A': // pass thru
                switch ( $char ) {
                    case '\\':
                        $state = 'D';
                        $charOut .= substr( $charIn, $start, $len );
                        $start = $i + 1;
                        $len = 0;
                        break;
                    case '{':
                        $state = 'B';
                        break;
                    default:
                        $len++;
                        break;
                }
                break;
            case 'B': // seen { 
                switch ( $char ) {
                    case '$':
                        $state = 'C';
                        $keyStart = $i + 1;
                        $keyLen = 0;
                        break;
                    default:
                        $charOut .= substr( $charIn, $start, $len ) . '{' . $char;
                        $start = $i + 1;
                        $len = 0;
                        $state = 'A';
                        break;
                }
                break;
            case 'C': // seen { and $, collecting key until }
                switch ( $char ) {
                    case '}': // key complete 
                        $charOut .= substr( $charIn, $start, $len );
                        $key = substr( $charIn, $keyStart, $keyLen );
                        if ( array_key_exists( $key, $substitutions ) ) {
                            $charOut .= $substitutions[ $key ];
                        }
                        $start = $i + 1;
                        $len = 0;
                        $key = '';
                        $state = 'A';
                        break;
                    default: // collect into key
                        $keyLen++;
                        break;
                }
                break;
            case 'D': // seen escape
                $charOut .= $char;
                $start = $i + 1;
                $len = 0;
                break;
        }
        $i++;
    }
    $charOut .= substr( $charIn, $start, $len );
    return $charOut;
}
