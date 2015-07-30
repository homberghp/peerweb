<?php
/**
 * row factory for task overview tables
 */
class TaskRowFactory implements RowFactory {

  private $old_grp_num = 1;
  protected $rainbow;
  private $rowColor;
  function __construct(){
    $this->rainbow = new RainBow(STARTCOLOR,COLORINCREMENT_RED,COLORINCREMENT_GREEN,COLORINCREMENT_BLUE);
    $this->rowColor= $this->rainbow->getCurrent();
  }


  public function startRow($valueArray){
    extract($valueArray);
    if ($this->old_grp_num != $grp_num ){
      $this->old_grp_num = $grp_num;
      $this->rowColor = $this->rainbow->getNext();
    }
    return "\t<tr style='background-color:".$this->rowColor."'>\n\t\t<td>$snummer</td>\n".
      "\t\t<td>$grp_num</td>\n".
      "\t\t<td>$name</td>\n".
      "\t\t<td onmouseover=".'"balloon.showTooltip(event,\'<div><center style=\\\'font-weight:bold;\\\'>'.
      $name.'<br/><img src=\\\''.$photo.
      '\\\'/></center></div>\')"'."><img src='$photo' width='24' height='36' /></td>\n";
  }
  public function buildHeader($data) {
    return "<th>snummer</th><th>grp</th><th>Name</th><th>pict</th>\n";
  }
 
  public function buildCell($valueArray){
    $result ='';
    extract($valueArray);
    if (isSet($valueArray['title'])) {
      $title =" title='".$valueArray['title']."' ";
      $class ="class='hasnote notegreen num' ";
    } else { 
      $class=$title='';
    }
    $result .="\t\t<td $class $title>".$valueArray['check']."</td>\n";
    return $result;
  }
  private $columnCounter=1;
  public function buildHeaderCell($valueArray){
    extract($valueArray);
    return "\t\t<th title='".$valueArray['checktitle']."' class='hasnote noteblue'>$task_name</th>\n";
  }
}
