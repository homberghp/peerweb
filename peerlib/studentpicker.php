<?php

require_once 'peerutils.php';
require_once 'validators.php';
require_once 'simplequerytable.php';
require_once 'selector.php';

class StudentPicker {

    private $pickerName;
    private $newsnummer = 0;
    private $searchString = '';
    private $presentQuery;
    private PDO $dbConn;
    private $autoFocus = '';
    private $inputName = 'newsnummer';
    private $showAcceptButton = true;
    private $selectOnFocus = false;
    private $autosubmit = false;

    function __construct( PDO $con, $newsnummer, $name = 'Add student' ) {
        $this->dbConn = $con;
        $this->newsnummer = $newsnummer;
        $this->pickerName = $name;
        if ( isSet( $_REQUEST[ 'searchname' ] ) && !preg_match( '/;/', $_REQUEST[ 'searchname' ] ) ) {
            $this->searchString = validate( $_REQUEST[ 'searchname' ], 'anything', 'xyz' );
        }
    }

    /**
     * present query tests for presence of student.
     * present query must yield a relation that is joinable by snummer.
     */
    function setPresentQuery( $pq ) {
        $this->presentQuery = $pq;
        return $this;
    }

    function setShowAcceptButton( $b ) {
        $this->showAcceptButton = $b;
        return $this;
    }

    function setSearchString( $ss ) {
        $this->searchString = $ss;
        if ( ($ss != '') && ($this->newsnummer == 0) ) {
            $this->newsnummer = $this->findStudentNumber();
        }
    }

    function buildWhereClause() {

        if ( preg_match( '/,/', $this->searchString ) ) {
            list($last, $first) = explode( ',', $this->searchString );
            $last = trim( $last );
            $first = trim( $first );
            $result = "achternaam ~* '^$last.*' and roepnaam ~* '^$first.*' ";
        } else {
            $result = "achternaam ~* '^" . trim( $this->searchString ) . ".*' ";
        }
        return $result;
    }

    function findStudentNumber() {
        $result = 0;
        $sql = "select snummer from student_email where " . $this->buildWhereClause() . " order by achternaam,roepnaam";
        $rs = $this->dbConn->Execute( $sql );
        if ( $rs !== false && !$rs->EOF ) {
            $result = $rs->fields[ 'snummer' ];
        }
        return $result;
    }

    /**
     * echo result html code.
     */
    function show() {
        echo $this->getPicker();
    }

    /**
     * the work horse
     */
    function getPickerWidget() {
        return "<fieldset class='navtop'><legend>$this->pickerName</legend>\n" .
                $this->getPicker() .
                "</fieldset>\n";
    }

    function getPicker() {
        global $PHP_SELF;
        $selall = $this->selectOnFocus ? "onFocus='this.select()'" : "";
        $auto = $this->autosubmit ? "onkeydown='if (event.keyCode == 13) document.getElementById(\"baccept\").click()'" : '';
        $result = '<!-- Start output StudentPicker \$Id\$ -->' . "\n";
        $result .= "<form id='addStudent' method='post' name='addStudent' action='$PHP_SELF'>\n" .
                "<table summary='student data'>\n\t<tr>\n\t\t<th align='right'>Peerweb user number</th>\n" .
                "\t\t<td>\n" .
                "\t\t\t<input type='text' id='{$this->inputName}' name='{$this->inputName}' size= '8' value='{$this->newsnummer}' {$this->autoFocus} {$selall} {$auto}/>\n" .
                "\t\t\t<input type='submit' name='bsubmit' value='get'/>(integer, between 1 and 8 digits)\n" .
                "\t\t</td>\n" .
                "\t</tr>\n";
        if ( $this->newsnummer != 0 ) {
            $sql = "select distinct roepnaam,tussenvoegsel,voorletters,achternaam,email1,hoofdgrp,cl.sclass as sclass,"
                    . "course_description,"
                    . "foo.snummer as foo_snummer\n"
                    . "from student_email \n"
                    . "join student_class cl using(class_id) left join fontys_course on(opl=course)\n"
                    . "left join ($this->presentQuery) as foo using(snummer)\n"
                    . " where snummer=$this->newsnummer order by sclass";
            $pstm = $this->dbConn->query( $sql );
//            $this->dbConn->log($sql);
            if ( $pstm === false ) {
                $result .= "error with $sql, cause " . $this->dbConn->errorInfo()[ 2 ];
            }

            if ( ($row = $pstm->fetch()) !== false ) {
                extract( $row );
                $acceptbutton = ($this->newsnummer == $foo_snummer) ? "<span style='color:#080'>Student is in project</span><input type='submit'" .
                        " name='bdelete' value='Delete'/>" : "<input type='submit' id='baccept' name='baccept' value='accept' accesskey='A'/>";

                $result .= "\t<tr>" .
                        "\t\t<th align='right'>Name</th>\n" .
                        "\t\t<td>$roepnaam ($voorletters) $tussenvoegsel $achternaam</td>\n" .
                        "\t</tr>\n" .
                        "\t<tr><th align='right'>email</th><td>$email1</td></tr>\n" .
                        "\t<tr><th align='right'>class</th><td>$sclass</td></tr>\n" .
                        "\t<tr><th align='right'>(major) course</th><td>$course_description</td></tr>\n";
                if ( $this->showAcceptButton ) {
                    $result .= "\t<tr><th align='right'>This is ok</th><td>$acceptbutton</td></tr>\n";
                }
            }
        }

        if ( $this->presentQuery != '' ) {
            $sql = 'select count(*) as listsize from (' . $this->presentQuery . ') as foo';
            $rs = $this->dbConn->query( $sql );
            if ( $rs === false ) {
                $result .= "error with $sql, cause " . $this->dbConn->errorInfo()[2];
            } else {
                $result .= "\t<tr><th>current list size</th><td>" . $rs->fetch()[ 'listsize' ] . "&nbsp;participants</td></tr>\n";
            }
        }

        $result .= "</table>\n" .
                "</form>\n" .
                "<form method='post' name='search' action='$PHP_SELF'>\n" .
                "<p>To search a student, enter lastename and optionally first, separated by a comma: ([last][,[first]])<br/>" .
                " as in \"<span style='font-family:courier;'>Fitzgerald,Ella</span>\" or" .
                " \"<span style='font-family:courier;'>Fitz,E</span>\", " .
                "which might both yield the same person. Note that you should not enter any quotes.</p>\n" .
                "<table summary='Search data'>\n" .
                "\t<tr>\n" .
                "\t\t<th>Last name</th>\n" .
                "\t\t<td><input type='text' name='searchname' value='$this->searchString'/></td>\n" .
                "\t\t<td><input type='submit' value='Search'/></td>\n" .
                "\t</tr>\n" .
                "</table>\n" .
                "</form>\n";

        if ( $this->searchString != '' ) {
            $searchsql = "select '<a href=''$PHP_SELF?newsnummer='||snummer||'''>'||snummer||'</a>' as snummer,\n"
                    . " achternaam,roepnaam,tussenvoegsel,voorletters,email1,cl.sclass as sclass"
                    . " from student_email "
                    . " join student_class cl using(class_id)\n"
                    . " where " . $this->buildWhereClause() . " order by achternaam,roepnaam";
            #        $this->dbConn->log($searchsql);

            $result .= simpleTableString( $this->dbConn, $searchsql
                    , "<table summary='students found' border='1' style='border-collapse:collapse'>" );
        }

        $result .= '<!-- End output StudentPicker $Id: studentpicker.php 1853 2015-07-25 14:17:12Z hom $ -->';

        return $result;
    }

// end function getPicker

    public function __toString() {
        return $this->getPickerWidget(); // "studentpicker";
    }

    public function setInputName( $s ) {
        $this->inputName = $s;
    }

    public function processRequest() {
        $result = 0;
        if ( isSet( $_GET[ 'newsnummer' ] ) ) {
            unset( $_POST[ 'newsnummer' ] );
            $_REQUEST[ 'newsnummer' ] = $this->newsnummer = validate( $_GET[ 'newsnummer' ], 'integer', '0' );
            //    $dbConn->log('GET '.$newsnummer);
        } else if ( isSet( $_POST[ 'newsnummer' ] ) ) {
            unset( $_GET[ 'newsnummer' ] );
            $_REQUEST[ 'newsnummer' ] = $this->newsnummer = validate( $_POST[ 'newsnummer' ], 'integer', '0' );
            //    $dbConn->log('POST '.$newsnummer);
        } else {
            unset( $_POST[ 'newsnummer' ] );
            unset( $_REQUEST[ 'newsnummer' ] );
            unset( $_GET[ 'newsnummer' ] );
        }

        if ( isSet( $_REQUEST[ 'searchname' ] ) ) {
            if ( !preg_match( '/;/', $_REQUEST[ 'searchname' ] ) ) {
                $searchname = $_REQUEST[ 'searchname' ];
                $this->setSearchString( $searchname );
                if ( !isSet( $_REQUEST[ 'newsnummer' ] ) ) {
                    $this->newsnummer = $this->findStudentNumber();
                    $this->dbConn->log( $this->newsnummer );
                }
            } else {
                $searchname = '';
            }
            $_SESSION[ 'searchname' ] = $searchname;
        } else {
            if ( isSet( $_SESSION[ 'searchname' ] ) ) {
                $this->setSearchString( $_SESSION[ 'searchname' ] );
            }
        }
        return $this->newsnummer;
    }

    public function getAutoFocus() {
        return $this->autoFocus;
    }

    public function setAutoFocus( $autoFocus ) {
        $this->autoFocus = $autoFocus;
        return $this;
    }

}

// end studentpicker
?>
