<?php
//require_once('dbutils.php');
/**
 * make a bitset as a number of inputs of type checkbox
 * @param $nameInForm string name of the bits in the form
 * @param $valueSet integer
 * @param $nameList array of string. Bitnames, lsb first
 * @return array of menuItems
 * the length of the namelist is used to determine the number of bits to be used
 * The bits should be contigeous in the valueset (bit skipping is not supported)
 */
function mkbitsetFields($nameInForm,$valueSet,$nameList){
  $result=array();
  $mask =1;
  for ($i=0; $i < count($nameList); $i++) {
    if (!empty($nameList[$i])) {
      $checked=($valueSet & $mask)?' checked ':'';
      $result[$nameList[$i]] = "<td align='right'>0x".dechex($mask)." <sup>({$mask})</sup></td>".
	"<td><input type='checkbox' name='${nameInForm}[]' value='".$mask."' $checked>&nbsp;$nameList[$i]</td>";
    }
    $mask <<=1;
  }
  return $result;
}
/**
 * Collect the bits of a bitset in a form
 * @param $name string : name of bitset
 * @return integer: bits collected
 */
function collectBitSet($bitset){
  $result=0;
  for($i=0; $i < count($bitset); $i++) {
    //  echo 'bit'.$i.'='.$bitset[$i].'<br>';
    $result |= $bitset[$i];
  }
  return $result;
}
?>
