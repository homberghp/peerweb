<?php
  /**
   * @author Pieter van den Hombergh
   * @param matrix matrix of proper dimension to multiply with
   * @param vector of proper dimension
   * @return vector 
   * No checks on sizes are done
   */
function matrixMultiply($matrix, $vector) {
    $result = array();
    for ($i=0; $i< count($matrix); $i++) {
	$result[$i] = 0;
	for( $j=0; $j< count($vector); $j++ ) {
	    $result[$i] += $matrix[$i][$j]*$vector[$j];
	}
    }
    return $result;
}

function matrixmatrixMultiply($matrix1, $matrix2) {
    $result = array();
    $resultRow=array();
    for ($i=0; $i< count($matrix1); $i++) {
	for( $j=0; $j< count($matrix1[$i]); $j++ ) {
	    $resultRow[$j] = 0;
	}
	for( $j=0; $j< count($matrix1[$i]); $j++ ) {
	    $resultRow[$j] += $matrix1[$i][$j]*$matrix2[$j][$i];
	}
	$result[$i]=$resultRow;
    }
    return $result;
}
function printMatrix($matrix) {
    echo "<table ><tr>";
    for ($i=0; $i< count($matrix); $i++) {
	echo "<tr>\n\t<td>\n";
	$continuation="<td>";
	for( $j=0; $j< count($matrix[$i]); $j++ ) {
	    echo $continuation.$matrix[$i][$j];
	    $continuation="</td><td>";
	}
	echo "\t</td>\n</tr>\n";
    }
    echo "</table>\n";
}
function printVector( $vector ){
    echo "<table ><tr>";
    for ($i=0; $i< count($vector); $i++) {
	echo "<tr><td>".$vector[$i]."</td></tr>\n";
    }
    echo "</table>\n";
}
function vectorLength($vector){
    $result=0;
    for ($i=0; $i< count($vector); $i++) {
	$result += $vector[$i]*$vector[$i];
    }
    return sqrt($result);

}
?>