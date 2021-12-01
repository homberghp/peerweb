<?php

require_once 'rainbow.php';
require_once 'pgrowparser.php';

use PhpOffice\PhpSpreadsheet;
use PhpOffice\PhpSpreadsheet\Cell;
use PhpOffice\PhpSpreadsheet\Style;
use PhpOffice\PhpSpreadsheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer;
use PhpOffice\PhpSpreadsheet\Calculation;

class XLSWriter {

    private $creator = "Fontys Venlo peerweb services";
    private $author = "Pieter van den Hombergh";
    private $title = "excel sheet";
    private $subject = "some peerweb table or view";
    private $description = "Class list extracted from peerweb, generated using \PhpOffice\PhpSpreadsheet\Spreadsheet student_class by Maarten Balliauw.  http://phpexcel.codeplex.com/";
    private $keywords = "peerweb fontys venlo informatica php";
    private $catagory = "class list";
    private $linkUrl = 'https://peerweb.fontysvenlo.org';
    private $linkText = 'https://peerweb.fontysvenlo.org';
    private $excelFormat = '';
    private $mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    private $filename = "excel.xlsx";
    private string $extension;
    private $dbConn;
    private $rowParser;
    private $rainBow;
    private $autoZebra = false;
    private $colorChangerColumn = -1;
    private $tableHeader;
    private $columnTypes;

    /**
     * weights : array used as weights in number fields
     * @var type 
     */
    private $weights = null;
    private $firstWeightColumn = -1;
    private $weightedSumsColumn = -1;
    private $weigthsRow = -1; // computed

    /**
     * Construct a writer for a query.
     * @param type $dbC  connection
     */
    public function __construct( PDO $dbC ) {
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

    /**
     *  Coloring rows for e.g. groups.
     * @param type $a
     * @return $this
     */
    public function setRainBow( $a ) {
        $this->rainBow = $a;
        return $this;
    }

    /**
     * Set office doc property.
     */
    public function setAuthor( $a ) {
        $this->author = $a;
        return $this;
    }

    /**
     * Set office doc property.
     */
    public function setTitle( $t ) {
        $this->title = $t;
        return $this;
    }

    /**
     * Set office doc property.
     */
    public function setDescription( $d ) {
        $this->description = $d;
        return $this;
    }

    /**
     * Set office doc property.
     */
    public function addKeywords( $k ) {
        $this->keywords .= $k;
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

    /**
     *  Output filename, extract extension.
     * @param type $f
     * @return $this
     */
    public function setFilename( string $f ) {
        $this->filename = $f;
        $parts = explode( '.', $this->filename );
        if ( count( $parts ) ) {
            $this->extension = end( $parts );
        }
        return $this;
    }

    /**
     * Select the excel variant.
     * @param type $f
     * @return $this
     */
    public function setExcelFormat( $f ) {
        $this->excelFormat = $f;
        return $this;
    }

    /**
     * Which column will trigger colour change.
     * @param type $c
     * @return $this
     */
    public function setColorChangerColumn( $c ) {
        $this->colorChangerColumn = $c;
        return $this;
    }

    /**
     * Do the rows alternate in background color?
     * @param type $az
     * @return $this
     */
    public function setAutoZebra( $az ) {
        $this->autoZebra = $az;
        if ( $this->autoZebra ) {
            $this->rainBow = RainBow::aRGBZebra();
        }
        return $this;
    }

    /**
     * Set the weights for a weighted table.
     * @param type $w weights array
     * @return \XLSWriter
     */
    public function setWeights( $w ) {
        $this->weights = $w;
        return $this;
    }

    /**
     * Set the column, x based of the first column of the weighted data.
     * @param type $c
     * @return \XLSWriter
     */
    public function setFirstWeightColumn( $c ) {
        $this->firstWeightColumn = $c;
        return $this;
    }

    /**
     * Record the column that adds up weights.
     * @param type $weightedSumsColumn
     * @return $this
     */
    public function setWeightedSumsColumn( $weightedSumsColumn ) {
        $this->weightedSumsColumn = $weightedSumsColumn;
        return $this;
    }

    /**
     * get the excel coordinate of a row and column.
     * @param type $column
     * @param type $row
     * @return type
     */
    static function cellCoordinate( $column, $row ) {
        return Cell\Coordinate::stringFromColumnIndex( $column ) . $row;
    }

    /**
     * Helper to compute coordinate string from rows and column with row coordinate absolute.
     * @param int $column , one based (A==1)
     * @param int $row, one based 
     * @return type string
     */
    private static function cellCoordinateAbsoluteRow( $column, $row ) {
        return Cell\Coordinate::stringFromColumnIndex( $column ) . '$' . $row;
    }

    /**
     * Helper to compute coordinate string from rows and column with row AND column coordinate absolute.
     * @param int $column , one based (A==1)
     * @param int $row, one based 
     * @return type string
     */
    private static function cellCoordinateAbsolute( $column, $row ) {
        return '$' . Cell\Coordinate::stringFromColumnIndex( $column ) . '$' . $row;
    }

    /**
     * Create sheet from query and dump named file to browser.
     * @param $query the query.
     */
    function writeXlsx( string $query ): void {
        Cell\Cell::setValueBinder( new Cell\AdvancedValueBinder() );
        $phpExcelInstance = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        if ( !isSet( $this->rowParser ) ) {
            $this->rowParser = new DefaultRowParser();
        }

        // set office document properties
        $phpExcelInstance->getProperties()->setCreator( $this->creator );
        $phpExcelInstance->getProperties()->setLastModifiedBy( $this->author );
        $phpExcelInstance->getProperties()->setTitle( $this->title );
        $phpExcelInstance->getProperties()->setSubject( $this->subject );
        $phpExcelInstance->getProperties()->setDescription( $this->description );
        $phpExcelInstance->getProperties()->setKeywords( $this->keywords );
        $phpExcelInstance->getProperties()->setCategory( $this->catagory );

//        global $ADODB_FETCH_MODE;
        $sth = $this->dbConn->query( $query );
        if ( $sth === false ) {
            die( "<br>Cannot get spreadsheet data with <pre>{$query}</pre> reason {$this->dbConn->errorInfo()[ 2 ]}<br>" );
        }
        // start writing in 3rd row, top isffor title and link.
        $row = 3;

        $this->tableHeader = $this->rowParser->parseToTableHeader( $sth );
        $headCount = count( $this->tableHeader );
        $headerStyles = [
            'font' => [
                'bold' => true,
                'color' => [
                    'argb' => 'FF000000',
                ]
            ],
            'alignment' => [
                'horizontal' => Style\Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Style\Border::BORDER_THIN,
                ],
            ],
            'fill' => [
                'fillType' => Style\Fill::FILL_SOLID,
                'rotation' => 0,
                'color' => [
                    'argb' => 'FFC0C0C0',
                ],
            ],
        ];
        for ( $i = 0; $i < $headCount; $i++ ) {
            $name = $this->tableHeader[ $i ];
            $coor = XLSWriter::cellCoordinate( $i + 1, $row );
            $phpExcelInstance->getActiveSheet()->getStyle( $coor )->applyFromArray( $headerStyles );
            $phpExcelInstance->getActiveSheet()->getCell( $coor )->setValue( $name );
        }
        $row++;
        // get types
        $this->columnTypes = $this->rowParser->parseTypes( $sth );
        $XlsTypes = array();
        for ( $i = 0; $i < count( $this->columnTypes ); $i++ ) {
            $ftype = Cell\DataType::TYPE_NUMERIC;
            switch ( $this->columnTypes[ $i ] ) {
                case 'char':
                case 'bpchar':
                case 'varchar':
                case 'text':
                case 'date':
                    $ftype = Cell\DataType::TYPE_STRING;
                    break;
                case 'int2':
                case 'int4':
                case 'int8':
                case '_numeric':
                case 'numeric':
                case 'float8':
                    $ftype = Cell\DataType::TYPE_NUMERIC;
                    break;
                default:
                    $ftype = Cell\DataType::TYPE_STRING;
                    break;
            }
            $XlsTypes[] = $ftype;
        }
        $cellStyleArray = [
            'font' => [
                'bold' => false,
            ]
            ,
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Style\Border::BORDER_THIN,
                ],
            ],
            'fill' => [
                'fillType' => Style\Fill::FILL_SOLID,
                'rotation' => 0,
                'startColor' => [
                    'argb' => 'FF000000',
                ],
            ],
        ];
        $oldValue = '';

        if ( $this->firstWeightColumn > 0 ) {// add weights row
            $this->weigthsRow = $row;
            $coor = XLSWriter::cellCoordinate( $this->firstWeightColumn - 1, $row );
            $phpExcelInstance->getActiveSheet()
                    ->setCellValue(
                            $coor, 'Weights', Cell\DataType::TYPE_STRING );
            $phpExcelInstance->getActiveSheet()->getStyle( $coor )->applyFromArray( $headerStyles );
            $weightSum = 0;
            $w = 0;
            $weightLast = count( $this->weights ) - 1;
            for (; $w < count( $this->weights ); $w++ ) {
                $coor = XLSWriter::cellCoordinate( $this->firstWeightColumn + $w, $row );
                $weightSum += $this->weights[ $w ];
                $phpExcelInstance->getActiveSheet()
                        ->setCellValue(
                                $coor, $this->weights[ $w ], Cell\DataType::TYPE_NUMERIC );
                $phpExcelInstance->getActiveSheet()->getStyle( $coor )->applyFromArray( $headerStyles );
            }
            $coor = XLSWriter::cellCoordinate( $this->weightedSumsColumn, $row );
            $wBegin = XLSWriter::cellCoordinate( $this->firstWeightColumn, $row );
            $wEnd = XLSWriter::cellCoordinate( $this->firstWeightColumn + $weightLast, $row );
            $formula = "=SUM($wBegin:$wEnd)";
            $phpExcelInstance->getActiveSheet()
                    ->setCellValue(
                            $coor, $formula, Cell\DataType::TYPE_FORMULA );
            $phpExcelInstance->getActiveSheet()->getStyle( $coor )->applyFromArray( $headerStyles );
            $coor = XLSWriter::cellCoordinate( $this->weightedSumsColumn, $row - 1 );
            $phpExcelInstance->getActiveSheet()
                    ->setCellValue(
                            $coor, 'Total WT', Cell\DataType::TYPE_STRING );
            $phpExcelInstance->getActiveSheet()->getStyle( $coor )->applyFromArray( $headerStyles );
            $row++;
        }

        while ( ($rowData=$this->rowParser->parse( $sth )) !== false ) {
//            $rowData = $this->rowParser->parse( $sth );

            $headCount = count( $this->tableHeader );
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
            $i = 0;
            for (; $i < $headCount; $i++ ) {
                $value = $rowData[ $i ];
                $coor = XLSWriter::cellCoordinate( $i + 1, $row );
                $xlstype = isSet( $XlsTypes[ $i ] ) ? $XlsTypes[ $i ] : Cell\DataType::TYPE_STRING;
                $cell = $phpExcelInstance->getActiveSheet()
                        ->getCellByColumnAndRow( $i + 1, $row );

                $cell->setValue( $value, $xlstype );

                if ( $this->columnTypes[ $i ] == 'date' ) {
                    $phpExcelInstance->getActiveSheet()->getStyle( $coor )
                            ->getNumberFormat()
                            ->setFormatCode( Style\NumberFormat::FORMAT_DATE_YYYYMMDD2 );
                } else if ( $this->columnTypes[ $i ] == 'time' ) {
                    $phpExcelInstance->getActiveSheet()->getStyle( $coor )
                            ->getNumberFormat()
                            ->setFormatCode( Style\NumberFormat::FORMAT_DATE_TIME8 );
                }


                $phpExcelInstance->getActiveSheet()->getStyle( $coor )
                        ->applyFromArray( $cellStyleArray );
            }
            if ( $this->weightedSumsColumn >= 0 ) {
                $weightLast = count( $this->weights ) - 1;
                $coor = XLSWriter::cellCoordinate( $this->weightedSumsColumn, $row );
                $wBegin = XLSWriter::cellCoordinateAbsoluteRow( $this->firstWeightColumn, $this->weigthsRow );
                $wEnd = XLSWriter::cellCoordinateAbsoluteRow( $this->firstWeightColumn + $weightLast, $this->weigthsRow );
                $rBegin = XLSWriter::cellCoordinate( $this->firstWeightColumn, $row );
                $rEnd = XLSWriter::cellCoordinate( $this->firstWeightColumn + $weightLast, $row );
                $wSumCoor = XLSWriter::cellCoordinateAbsolute( $this->weightedSumsColumn, $this->weigthsRow );
                $formula = "=SUMPRODUCT({$wBegin}:{$wEnd},{$rBegin}:{$rEnd})/$wSumCoor";
                $phpExcelInstance->getActiveSheet()
                        ->setCellValueExplicit(
                                $coor, $formula, Cell\DataType::TYPE_FORMULA );
                $phpExcelInstance->getActiveSheet()->getStyle( $coor )->applyFromArray( $cellStyleArray );
            }
            $row++;
//            $resultSet->moveNext();
        }

        $row = 1;
        $phpExcelInstance->getActiveSheet()->getCell( 'A2' )->setValue( $this->linkText );
        $row++;
        $phpExcelInstance->getActiveSheet()->getCell( 'A1' )->setValue( $this->title );

        $phpExcelInstance->getActiveSheet()->getStyle( 'A' . $row )->applyFromArray( $headerStyles );
        $phpExcelInstance->getActiveSheet()->getStyle( 'A1' )->applyFromArray( $headerStyles );
        $rightCell1 = XLSWriter::cellCoordinate( $headCount, $row );

        $phpExcelInstance->getActiveSheet()->mergeCells( 'A' . $row . ':' . $rightCell1 );
        $rightCell2 = XLSWriter::cellCoordinate( $headCount, 1 );
        $phpExcelInstance->getActiveSheet()->mergeCells( 'A1:' . $rightCell2 );

        // set format
        $phpExcelInstance->getActiveSheet()
                ->getPageSetup()->setOrientation( Worksheet\PageSetup::ORIENTATION_LANDSCAPE );
        $phpExcelInstance->getActiveSheet()->getPageSetup()->setPaperSize( Worksheet\PageSetup::PAPERSIZE_A4 );

        $phpExcelInstance->getActiveSheet()->getPageSetup()->setFitToWidth( 1 );
        $phpExcelInstance->getActiveSheet()->getPageSetup()->setFitToHeight( 0 );

        for ( $i = 'A', $j = 0; $i <= 'Z' && $j < $headCount; $i++, $j++ ) {
            $phpExcelInstance->getActiveSheet()->getColumnDimension( $i )->setAutoSize( true );
        }
        Calculation\Calculation::getInstance()->clearCalculationCache();
        Calculation\Calculation::getInstance()->disableCalculationCache();
        Calculation\Calculation::getInstance()->calculate();
        switch ( $this->excelFormat ) {
            case 'Excel2007':
                $objWriter = new Writer\Xlsx( $phpExcelInstance );
                $this->mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                break;
            case 'Excel5':
                $objWriter = new Writer\Xls( $phpExcelInstance );
                $this->mimeType = 'application/vnd.ms-excel';
                break;
            default:
                $objWriter = new Writer\Csv( $phpExcelInstance );
                $this->mimeType = 'text/comma-separated-values';
                break;
        }

        $tempFile = tempnam( '/tmp/', 'PHPEXCEL' ); // '/tmp/'.$filename;
        $objWriter->setPreCalculateFormulas( true );
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
            $phpExcelInstance->disconnectWorksheets();
            unset( $phpExcelInstance );
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
