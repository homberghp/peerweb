<?php

require_once __DIR__ . "/testconfig.php";

require_once 'peerutils.php';

use PHPUnit\Framework\TestCase;

/**
 * Description of PeerutilsTest
 *
 * @author Pieter van den Hombergh {@code <pieter.van.den.hombergh@gmail.com>}
 */
class PeerutilsTest extends TestCase {

    //put your code here

    public function testConnection() {
        global $dbConn;
        $pstm = $dbConn->query( 'select now()' );
        $this->assertIsString( $pstm->fetch()[ 0 ] );
    }

    public function testHasStudentCapFalse() {
        $this->assertFalse( hasstudentCap( 879417, 0, 123 ) );
    }

    public function testHasStudentCapTrue() {
        // mini 
        $this->assertTrue( hasstudentCap( 2065297, 2, 56 ) );
    }

    public function testHasStudentCap2False() {
        $this->assertFalse( hasstudentCap2( 879417, 0, 123 ) );
    }

    public function testHasStudentCap2True() {
        // mini 
        $this->assertTrue( hasstudentCap( 2065297, 2, 56 ) );
    }

    public function testGetOptionList() {
        global $dbConn;
        $prjm_id = 56;
        $query = "select grp_num||' '||coalesce(grp_name,'g'||grp_num)||': '||achternaam||', '||roepnaam||' '" .
                "||coalesce(tussenvoegsel,'')||' ('||faculty.faculty_short||':'" .
                "||tutor.tutor||';'||tutor.userid||')' as name,\n" .
                " grp_num as value" .
                " from prj_tutor join tutor on(tutor.userid=prj_tutor.tutor_id)\n" .
                " join student_email on (userid=snummer)\n" .
                " join faculty on (faculty.faculty_id=tutor.faculty_id)\n" .
                " natural left join grp_alias \n " .
                " where prjm_id=$prjm_id order by grp_num";
        $ol = getOptionList( $dbConn, $query );
        //fwrite( STDERR, $ol );
        $this->assertStringContainsString( 'Titulaer', $ol );
    }

    public function testGetOptionListGrouped() {
        global $dbConn;
        $query = <<<'SQL'
                select trim(course_short)||':'||trim(course_description)||'('||course||')' as name,
                  course as value,
                  faculty_short as namegrp
                from fontys_course fc natural join faculty f
                order by namegrp,name
                SQL;
        $ol = getOptionListGrouped( $dbConn, $query, '' );
        $this->assertStringContainsString( 'TenL', $ol );
    }

    public function testQueryToTableSimple() {
        global $dbConn;
        $query = 'select * from tutor';
        $rb = new RainBow();
        $ol = getQueryToTableChecked( $dbConn, $query, 0, -1, $rb, -1, '' );
        $this->assertStringContainsString( 'HOM', $ol );
    }
    
    public function testPrepareQuery(){
        global $dbConn;
        $query = 'select * from tutor';
        $resultString='';
        $pstm= prepareQuery($dbConn, $query, $resultString);
        
        $this->assertEquals(0, strlen($resultString));
        
    }

}
