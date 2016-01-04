<?php

require_once 'rainbow.inc';
require_once 'pgrowparser.php';

/** PHPExcel */
require_once 'PHPExcel.php';

/** PHPExcel_Writer_Excel2007 */
require_once 'PHPExcel/Cell/AdvancedValueBinder.php';

/** PHPExcel_IOFactory */
// require_once 'PHPExcel/IOFactory.php';
require_once 'PHPExcel/Writer/Excel2007.php';
require_once 'PHPExcel/Writer/Excel5.php';
require_once 'PHPExcel/Writer/CSV.php';

class XLSWriter {

    private $creator = "Fontys Venlo peerweb services";
    private $author = "Pieter van den Hombergh";
    private $title = "excel sheet";
    private $subject = "some peerweb table or view";
    private $description = "Class list extracted from peerweb, generated using PHPExcel student_class by Maarten Balliauw.  http://phpexcel.codeplex.com/";
    private $keywords = "peerweb fontys venlo informatica php";
    private $catagory = "class list";
    private $linkUrl = 'https://www.fontysvenlo.org/peerweb';
    private $linkText = 'https://www.fontysvenlo.org/peerweb';
    private $excelFormat = '';
    private $mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    private $filename = "excel.xlsx";
    private $extension;
    private $dbConn;
    private $rowParser;
    private $rainBow;
    private $autoZebra = false;
    private $colorChangerColumn = -1;
    private $tableHeader;
    private $columnTypes;

    /**
     * Construct a writer for a query.
     * @param type $dbC 
     */
    public function __construct( $dbC ) {
        $this->dbConn = $dbC;
        $this->rainBow = new RainBow();
    }

    /**
     * Set creator. Default is peerweb service.
     * @param type $c 
     */
    public function setCreator( $c ) {
        $this->creator = $c;
        return $this;
    }

    public function setRainBow( $a ) {
        $this->rainBow = $a;
        return $this;
    }

    public function setAuthor( $a ) {
        $this->author = $a;
        return $this;
    }

    public function setTitle( $t ) {
        $this->title = $t;
        return $this;
    }

    public function setDescription( $d ) {
        $this->description = $d;
        return $this;
    }

    public function addKeywords( $k ) {
        $this->keywords .=$k;
        return $this;
    }

    public function setLinkUrl( $u ) {
        $this->linkUrl = $u;
        return $this;
    }

    public function setLinkText( $t ) {
        $this->linkText = $t;
        return $this;
    }

    public function setSubject( $s ) {
        $this->subject = $s;
        return $this;
    }

    public function setFilename( $f ) {
        $this->filename = $f;
        $parts = explode( '.', $this->filename );
        if ( count( $parts ) ) {
            $this->extension = end( $parts );
        }
        return $this;
    }

    public function setExcelFormat( $f ) {
        $this->excelFormat = $f;
        return $this;
    }

    public function setColorChangerColumn( $c ) {
        $this->colorChangerColumn = $c;
        return $this;
    }

    public function setAutoZebra( $az ) {
        $this->autoZebra = $az;
        if ( $this->autoZebra ) {
            $this->rainBow = RainBow::aRGBZebra();
        }
        return $this;
    }

    static function cellCoordinate( $column, $row ) {
        return PHPExcel_Cell::stringFromColumnIndex( $column ) . $row;
    }

    /**
     * Create sheet from query and dump named file to browser.
     * @param $query the query.
     */
    function writeXlsx( $query ) {
        PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_AdvancedValueBinder() );
        $objPHPExcel = new PHPExcel();
        if ( !isSet( $this->rowParser ) ) {
            $this->rowParser = new DefaultRowParser();
        }
        $objPHPExcel->getProperties()->setCreator( $this->creator );
        $objPHPExcel->getProperties()->setLastModifiedBy( $this->author );
        $objPHPExcel->getProperties()->setTitle( $this->title );
        $objPHPExcel->getProperties()->setSubject( $this->subject );
        $objPHPExcel->getProperties()->setDescription( $this->description );
        $objPHPExcel->getProperties()->setKeywords( $this->keywords );
        $objPHPExcel->getProperties()->setCategory( $this->catagory );


        global $ADODB_FETCH_MODE;
        $ADODB_FETCH_MODE = ADODB_FETCH_NUM;
        $resultSet = $this->dbConn->Execute( $query );
        if ( $resultSet === false ) {
            die( "<br>Cannot get spreadsheet data with <pre>" . $query . "</pre> reason " .
                    $this->dbConn->ErrorMsg() . "<br>" );
        }
        //echo $query;
        //$colcount = $resultSet->FieldCount();
        // start writing in 3rd row, top isf for title and link.
        $row = 3;
        $this->tableHeader = $this->rowParser->parseToTableHeader( $resultSet );
        $headCount = count( $this->tableHeader );
        $headerStyles = array(
            'font' => array(
                'bold' => true,
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
            'borders' => array(
                'allborders' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                ),
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'rotation' => 0,
                'color' => array(
                    'argb' => 'FFC0C0C0',
                ),
            ),
        );
        for ( $i = 0; $i < $headCount; $i++ ) {
            $name = $this->tableHeader[ $i ];
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow( $i, $row, $name );
            $coor = XLSWriter::cellCoordinate( $i, $row );
            $objPHPExcel->getActiveSheet()->getStyle( $coor )->applyFromArray( $headerStyles );
        }
        $row++;
        // get types
        $this->columnTypes = $this->rowParser->parseTypes( $resultSet );
        $XlsTypes = array();
        //error_log( 'there are '.count($this->columnTypes).' types from db =' . print_r( $this->columnTypes, true ), 0 );
        for ( $i = 0; $i < count( $this->columnTypes ); $i++ ) {
            $ftype = PHPExcel_Cell_DataType::TYPE_NUMERIC;
            switch ( $this->columnTypes[ $i ] ) {
                case 'char':
                case 'bpchar':
                case 'varchar':
                case 'text':
                case 'date':
                    $ftype = PHPExcel_Cell_DataType::TYPE_STRING;
                    break;
                case 'int2':
                case 'int4':
                case 'int8':
                case '_numeric':
                case 'numeric':
                case 'float8':
                    $ftype = PHPExcel_Cell_DataType::TYPE_NUMERIC;
                    break;
                default:
                    $ftype = PHPExcel_Cell_DataType::TYPE_STRING;
                    error_log( 'found unknown type =' . $this->columnTypes[ $i ], 0 );
                    break;
            }
            $XlsTypes[] = $ftype;
        }
        $cellStyleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                ),
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'rotation' => 0,
                'color' => array(
                    'argb' => 'FF0000',
                ),
            ),
        );
        $oldValue = '';
        while ( !$resultSet->EOF ) {
            $rowData = $this->rowParser->parse( $resultSet );

            $headCount = count( $this->tableHeader ); //$resultSet->FieldCount();
            $changeColor = false;
            if ( $this->colorChangerColumn >= 0 ) {
                if ( $oldValue != $rowData[ $this->colorChangerColumn ] ) {
                    $changeColor = true;
                    $oldValue = $rowData[ $this->colorChangerColumn ];
                }
            } else if ( $this->autoZebra ) {
                $changeColor = true;
            }
            if ( $changeColor ) {
                $cellStyleArray[ 'fill' ][ 'color' ][ 'argb' ] = $this->rainBow->getCurrentAsARGBString();
                $this->rainBow->getNext();
            }
            for ( $i = 0; $i < $headCount; $i++ ) {
                $value = $rowData[ $i ];
                $coor = XLSWriter::cellCoordinate( $i, $row );
                $xlstype = defined( $XlsTypes[ $i ] ) ? $XlsTypes[ $i ] : PHPExcel_Cell_DataType::TYPE_STRING;
                $objPHPExcel->getActiveSheet()
                        ->setCellValueExplicit(
                                $coor, $value, $xlstype );
                if ( $this->columnTypes[ $i ] == 'date' ) {
                    $objPHPExcel->getActiveSheet()->getStyle( $coor )
                            ->getNumberFormat()
                            ->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2 );
                } else if ( $this->columnTypes[ $i ] == 'time' ) {
                    $objPHPExcel->getActiveSheet()->getStyle( $coor )
                            ->getNumberFormat()
                            ->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_DATE_TIME8 );
                }


                $objPHPExcel->getActiveSheet()->getStyle( $coor )->applyFromArray( $cellStyleArray );
            }
            $row++;
            $resultSet->moveNext();
        }

        $row = 1;
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(
                0, $row, $this->linkText );
        $objPHPExcel->getActiveSheet()->getCell( 'A' . $row )
                ->getHyperlink()->setUrl( $this->linkUrl );
        $row++;
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(
                0, $row, $this->title );


        $objPHPExcel->getActiveSheet()->getStyle( 'A' . $row )->applyFromArray( $headerStyles );
        $objPHPExcel->getActiveSheet()->getStyle( 'A1' )->applyFromArray( $headerStyles );
        $rightCell1 = XLSWriter::cellCoordinate( min( $headCount - 1, 10 ), $row );

        $objPHPExcel->getActiveSheet()->mergeCells( 'A' . $row . ':' . $rightCell1 );
        $rightCell2 = XLSWriter::cellCoordinate( min( $headCount - 1, 10 ), 1 );
        $objPHPExcel->getActiveSheet()->mergeCells( 'A1:' . $rightCell2 );


        // set format
        $objPHPExcel->getActiveSheet()
                ->getPageSetup()->setOrientation( PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE );
        $objPHPExcel->getActiveSheet()->getPageSetup()->setPaperSize( PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4 );

        $objPHPExcel->getActiveSheet()->getPageSetup()->setFitToWidth( 1 );
        $objPHPExcel->getActiveSheet()->getPageSetup()->setFitToHeight( 0 );


        for ( $i = 'A', $j = 0; $i <= 'Z' && $j < $headCount; $i++, $j++ ) {
            $objPHPExcel->getActiveSheet()->getColumnDimension( $i )->setAutoSize( true );
//            $objPHPExcel->getActiveSheet()->getStyle($i . '2')->applyFromArray($styleArray);
        }

        switch ( $this->excelFormat ) {
            case 'Excel2007':
                $objWriter = new PHPExcel_Writer_Excel2007( $objPHPExcel );
                $this->mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                break;
            case 'Excel5':
                $objWriter = new PHPExcel_Writer_Excel5( $objPHPExcel );
                $this->mimeType = 'application/vnd.ms-excel';
                break;
            default:
                $objWriter = new PHPExcel_Writer_CSV( $objPHPExcel );
                $this->mimeType = 'text/comma-separated-values';
                break;
        }

        $tempFile = tempnam( '/tmp/', 'PHPEXCEL' ); // '/tmp/'.$filename;
        $objWriter->save( $tempFile );

        $fp = @fopen( $tempFile, 'r' );
        if ( $fp != false ) {

            header( "Content-type: " . $this->mimeType );
            header( "Pragma: public" );
            header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
            header( "Content-Length: " . filesize( $tempFile ) );
            header( "Content-Disposition: attachment; filename=\"$this->filename\"" );

            fpassthru( $fp );
            fclose( $fp );
            $objPHPExcel->disconnectWorksheets();
            unset( $objPHPExcel );

            unlink( $tempFile );

            exit( 0 );
        } else {
            echo "cannot copy file $tempFile to out stream\n";
        }
    }

    function setRowParser( $p ) {
        $this->rowParser = $p;
        return $this;
    }

}

?>