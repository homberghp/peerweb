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
 * 2 found $ in {$name} and collecting key
 * 3 from $ as start.
 * 
 */
function templateWith( string $template, array $substitutions ) {
    $state = 0; // forwarding
    $charIn = preg_split( '//u', $template, -1, PREG_SPLIT_NO_EMPTY );
    $charOut = array();
    $count = count( $charIn );
    $key = array();
    $i = 0;
    while ($i < $count) {
        $char = $charIn[ $i ];
        switch ( $char ) {
            case '{': if ( $state === 0 ) {
                    $state = 1;
                }
                break;
            case '}':
                if ( $state === 2 ) {
                    $ks = join( '', $key );
                    if ( array_key_exists( $ks, $substitutions ) ) {
                        $charOut[] = $substitutions[ $ks ];
                    }
                    $key = array();
                    $state = 0;
                }
                break;
            case '$': switch ( $state ) {
                    case 0: $state = 3;
                        break;
                    case 1: $state = 2;
                        break;
                }
            case '\\': if ( $state === 0 ) {
                    $i++;
                    $charOut[] = $charIn[ $i ];
                }
                continue 2;
            default:
                switch ( $state ) {
                    default:
                    case 0: $charOut[] = $char;
                        break;
                    case 2: $key[] = $char;
                        break;
                    case 3: if ( preg_match( '/^\w$/u',$char ) ) {
                            $key[] = $char;
                        } else { // token complete, but save $char.  
                            $ks = join( '', $key );
                            if ( array_key_exists( $ks, $substitutions ) ) {
                                $charOut[] = $substitutions[ $ks ];
                            }
                            $charOut[] = $char;
                            $key = array();
                            $state = 0;
                        }
                        break;
                }
        }
        $i++;
        if ( $i === $count && count( $key ) ) {
            $ks = join( '', $key );
            if ( array_key_exists( $ks, $substitutions ) ) {
                $charOut[] = $substitutions[ $ks ];
            }
        }
    }

    return join( '', $charOut );
}
