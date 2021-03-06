<?php

use PHPUnit\Framework\TestCase;

require_once 'TemplateWith.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of templateWithTest
 *
 * @author Pieter van den Hombergh {@code <p.vandenhombergh@fontys.nl>}
 */
class TemplateWithTest extends TestCase {

    //put your code here
    public function testOne() {
        $s = 'hello {$item}';
        $expected = 'hello world';
        $needles = array('item' => 'world', 'hello' => 'nothing');
        $result = templateWith($s, $needles);
        $this->assertEquals($expected, $result, 'not equals');
        return $result;
    }
    
    public function testTwo() {
        $s = 'hello {$itom}';
        $expected = 'hello ';
        $needles = array('item' => 'world', 'hello' => 'nothing');
        $result = templateWith($s, $needles);
        $this->assertEquals($expected, $result, 'not equals');
        return $result;
    }

    public function testThree() {
        $s = 'hello \{\$item\}';
        $expected = 'hello {$item}';
        $needles = array('item' => 'world', 'hello' => 'nothing');
        $result = templateWith($s, $needles);
        $this->assertEquals($expected, $result, 'not equals');
        return $result;
    }
    /**
     * @dataProvider provider
     * @param type $str
     * @param type $exp
     * @param type $repl
     */
    public function testSub($str, $exp, $repl){
        $this->assertEquals($exp,templateWith($str,$repl));
        
    }

    public function provider(){
        return [
            ['hello {$world}','hello Schöne Heimat', array('world'=> 'Schöne Heimat')],
            ['hello {$süßes}','hello Schöne Heimat', array('süßes'=> 'Schöne Heimat')],
            ['hello $schatz ','hello Schöne Heimat ', array('schatz'=> 'Schöne Heimat')],
            ['hello $schatz','hello Schöne Heimat', array('schatz'=> 'Schöne Heimat')],
            ['with underscores $schatz_z','with underscores Schöne Heimat', array('schatz_z'=> 'Schöne Heimat')],
            ['one well known pattern is Façade, {$süßes}','one well known pattern is Façade, Liebling', array('süßes'=> 'Liebling')],
            ['dollar ony, utf8 hello $süßes','dollar ony, utf8 hello Schöne Heimat', array('süßes'=> 'Schöne Heimat')],
            ['dollar ony, utf8 hello $süßes2','dollar ony, utf8 hello Schöne Heimat', array('süßes2'=> 'Schöne Heimat')],
            ];
        
        
    }
}
