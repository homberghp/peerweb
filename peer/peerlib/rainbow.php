<?php

/**
 * @author Pieter van  den Hombergh
 *  $Id: rainbow.php 1723 2014-01-03 08:34:59Z hom $
 */
require_once('matrix.php');

function rgba($r, $g, $b, $a) {
    return dechex((int) (($r & 255) << 24) |
                    (($g & 255) << 16) | (($b & 255) << 8) | (((int) ($a * 65535)) >> 8));
}

class RainBow {

    var $mrot;
    var $vec;
    var $sRed, $sGreen, $sBlue;
    var $red, $green, $blue;
    var $rinc, $ginc, $binc;
    var $list;
    var $xlslist;
    var $index;

    function __construct() {
        $this->list = array(
            'rgba(255,255,176,0.4)',
            'rgba(255,176,255,0.4)',
            'rgba(176,255,255,0.4)',
            'rgba(255,176,176,0.4)',
            'rgba(176,255,176,0.4)',
            'rgba(176,176,255,0.4)',
            'rgba(176,176,176,0.4)',
            'rgba(255,255,255,0.4)',
        );
        $this->xlslist = array(
            '64FFFFB0',
            '64FFB0FF',
            '64B0FFFF',
            '64FFB0B0',
            '64B0FFB0',
            '64B0B0FF',
            '64B0B0B0',
            '64FFFFFF'
        );
        $this->index = 0;
    }

    function getNext() {
        $this->index++;
        $this->index %= count($this->list);
        $result = $this->getCurrent();
        return $result;
    }

    function restart() {
        $this->red = $this->sRed;
        $this->green = $this->sGreen;
        $this->blue = $this->sBlue;
        $this->index = 0; 
        return $this->getCurrent();
    }

    function getCurrent() {
        /* 	$result=dechex(($this->red<< 16)+($this->green<< 8) +$this->blue);
          $result = substr('000000', 0,6-strlen($result)).$result;
          return '#'.$result;
         */
        return $this->list[$this->index];
    }

    /**
     * return current as argb for us in xls writer.
     */
    function getCurrentAsARGBString() {
        return $this->xlslist[$this->index];
    }

    function setStartColors($startColor, $redInc, $greenInc, $blueInc) {
        $this->red = $this->sRed = ($startColor >> 16) & 255;
        $this->green = $this->sGreen = ($startColor >> 8) & 255;
        $this->blue = $this->sBlue = $startColor & 255;
        $this->rinc = $redInc;
        $this->ginc = $greenInc;
        $this->binc = $blueInc;
    }

    function __toString() {
        return 'Rainbow';
    }

    function setList($l){
        $this->list = $l;
    }
    function count(){
        return count($this->list);
        
    }
    
    static function aRGBZebra(){
        $result = new RainBow();
        $result->setList(array('C0C0FF66','FFFFFF66'));
        $result->xlslist=array('64C0C0FF','64FFFFFF');
        return $result;
    }

    static function zebra(){
        $result = new RainBow();
        $result->setList(array('rgba(192,192,255,0.4)','rgba(255,255,255,0.4)'));
        $result->xlslist=array('64C0C0FF','64FFFFFF');
        return $result;
    }
}

?>
