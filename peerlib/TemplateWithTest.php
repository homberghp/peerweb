<?php

use PHPUnit\Framework\TestCase;

require_once '../web/lib/TemplateWith.php';
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
        $this->assertEquals($expected, $result, 'replaced item');
        return $result;
    }
    
    public function testTwo() {
        $s = 'hello {$atom}';
        $expected = 'hello ';
        $needles = array('item' => 'world', 'hello' => 'nothing');
        $result = templateWith($s, $needles);
        $this->assertEquals($expected, $result, 'not replace atom');
        return $result;
    }

    public function testThree() {
        $s = 'hello \{$item}';
        $expected = 'hello {$item}';
        $needles = array('item' => 'world', 'hello' => 'nothing');
        $result = templateWith($s, $needles);
        $this->assertEquals($expected, $result, 'escaped curly');
        return $result;
    }
    /**
     * @dataProvider provider
     * @param type $str
     * @param type $exp
     * @param type $repl
     */
    public function testSub($msg,$str, $exp, $repl){
        $this->assertEquals($exp,templateWith($str,$repl),$msg);
        
    }
    public function provider(){
        return [
            ['all','hello {$world}','hello Schöne Heimat', array('world'=> 'Schöne Heimat')],
            ['before end','hello {$süßes}!','hello Schöne Heimat!', array('süßes'=> 'Schöne Heimat')],
            ['escaped before end','hello \{$süßes}!','hello {$süßes}!', array('süßes'=> 'Schöne Heimat')],
            ['no dollar','hello {süßes}','hello {süßes}', array('süßes'=> 'Schöne Heimat')],
            ['no curlies','hello $süßes','hello $süßes', array('süßes'=> 'Schöne Heimat')],
            ['no curlies','hello $schatz I have not seen my {}','hello $schatz I have not seen my {}', array('schatz'=> 'Schöne Heimat')],
            ['Nippon', 'こんにちは {$schatz}','こんにちは Schöne Heimat', array('schatz'=> 'Schöne Heimat')],
            ['Nippon key','hello {$恋しい}','hello エクスペンシブ',array('恋しい'=>'エクスペンシブ')],
            ['underscore in key','with underscores {$schatz_z}','with underscores Schöne Heimat', array('schatz_z'=> 'Schöne Heimat')],
            ['start','{$süßes}, one well known pattern is Façade','Liebling, one well known pattern is Façade', array('süßes'=> 'Liebling')],
            ];
        
        
    }
}
