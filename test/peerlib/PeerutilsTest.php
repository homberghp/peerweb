<?php

require_once __DIR__."/testconfig.php";

require_once 'peerutils.php';
use PHPUnit\Framework\TestCase;

/**
 * Description of PeerutilsTest
 *
 * @author Pieter van den Hombergh {@code <pieter.van.den.hombergh@gmail.com>}
 */
class PeerutilsTest extends TestCase{
    //put your code here
    
    public function testConnection(){
        global $dbConn;
        $pstm= $dbConn->query('select now()');
        $this->assertIsString($pstm->fetch()[0]);
    }
 
    public function testHasStudentCap(){
        $this->assertTrue(hasstudentCap(879417, 0, 123));
        
    }
}
