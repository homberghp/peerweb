<?php

require_once('./peerlib/peerutils.inc');
require_once('./peerlib/validators.inc');
require_once('./peerlib/simplequerytable.inc');

if ( isSet( $_REQUEST['newscancode'] ) ) {
  $id = validate( $_REQUEST['newscancode'], 'integer', '0' );
} else if ( isSet( $_REQUEST['id'] ) ) {
  $id = $_REQUEST['id'];
} else {
  $id = 0;
}
$boxes = array( );
$sql = "select id,roepnaam||coalesce(' '||tussenvoegsel||' '::text,' ')||achternaam as name,\n" .
        "straat||' '||huisnr as adres, plaats,country as land,postcode,gebdat,omschrijving_studieprog,status,cohort,'fotos/'||coalesce(rp.snummer,0)||'.jpg' as foto, \n" .
        "rtrim(coalesce(ju.diplvo,j.diplvo)) as diplvo,\n" .
        "rtrim(coalesce(ju.cijfer,j.cijfer)) as cijfer,\n" .
        "rtrim(coalesce(ju.betbew,j.betbew)) as betbew,\n" .
        "rtrim(coalesce(ju.pasprt,j.pasprt)) as pasprt,\n" .
        "rtrim(coalesce(ju.uittre,j.uittre)) as uittre,\n" .
        "rtrim(coalesce(ju.renrij,j.renrij)) as renrij \n" .
        "from jaaglijst j join jaag_naws naws using(id) left join (select * from  jaaglijst_update where id=$id \n" .
        "\tand trans_id=(select max(trans_id) from jaaglijst_update where id=$id))ju using(id) \n" .
        "join iso3166 i on (naws.land=i.a3) left join registered_photos rp on (id=snummer) where id=$id \n";
$resultSet = $dbConn->execute( $sql );
if ( $resultSet === false ) {
  echo ( "<br>Cannot get jaagbuit data with " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>");
}
if ( !$resultSet->EOF ) {
  extract( $resultSet->fields );
  $checks = array( 'diplvo', 'cijfer', 'betbew', 'pasprt', 'uittre', 'renrij' );
  $rownr = 0;
  foreach ( $checks as $check ) {
    switch ( $check ) {
      case 'diplvo':
        $title = 'Kopie Diploma<br/> vooropleiding';
        $img = 'images/diploma_thumb.jpg';
        break;
      case 'cijfer':
        $title = 'Gewaarmerkte <br/>Cijferlijst';
        $img = 'images/rapport_thumb.gif';
        break;
      case 'betbew':
        $title = 'Betalingsbewijs';
        $img = 'images/bankafschrift_thumb.jpg';
        break;
      case 'pasprt':
        $title = 'Kopie paspoort';
        $img = 'images/passport_thumb.jpg';
        break;
      case 'uittre':
        $title = 'Uittreksel<br/>bevolkingsregister';
        $img = 'images/bevolkingsregister_thumb.jpg';
        break;
      case 'renrij':
        $title = 'Herinschrijving<br/>studielink';
        $img = 'images/studielinklogo_thumb.png';
        break;
    }

    if ( $resultSet->fields[$check] == 'Voltooid' || $resultSet->fields[$check] == 'Vrijstelling' ) {
      $widgetcells = "<td colspan='2' style='text-align:center;font-weight:normal;font-style:italic;font-size:80%'>" . $resultSet->fields[$check] . "</td>";
    } else {
      if ( $resultSet->fields[$check] == 'Ingeleverd' ) {
        $checked = 'checked';
      } else {
        $checked = '';
      }
      $widgetcells = "\t<td  class='button'>\n" .
              "\t\t<button type='button' onclick=\"javascript:toggle('$check')\">\t" .
              "\t\t\t<img align='middle' src='$img'/>\n" .
              "\t\t</button>\n" .
              "\t</td>\n" .
              "\t<td><input type='checkbox' name='$check' id='$check' value='Ingeleverd' $checked />\n" .
              "\t\t<input type='hidden' name='boxes[]' value='$check'/>\n" .
              "\t</td>\n";
    }

    $boxwidget = "<tr class='" . (($rownr++ % 2) ? 'odd' : 'even') . " big'>\n" .
            "<td class='itemtext'>$title</td>\n" .
            $widgetcells .
            "</tr>\n";
    $boxes[$check] = $boxwidget;
  }
} else {
  $name = 'NOT FOUND';
}

$sqlhistory = "select  \n" .
        "to_char(ts,'YYYY-MM-DD HH24:MI') as date_time,\n" .
        "rtrim(coalesce(ju.diplvo,j.diplvo)) as diplvo,\n" .
        "rtrim(coalesce(ju.cijfer,j.cijfer)) as cijfer,\n" .
        "rtrim(coalesce(ju.betbew,j.betbew)) as betbew,\n" .
        "rtrim(coalesce(ju.pasprt,j.pasprt)) as pasprt,\n" .
        "rtrim(coalesce(ju.uittre,j.uittre)) as uittre, \n" .
        "rtrim(coalesce(ju.renrij,j.renrij)) as renrij, \n" .
        " trans_id,operator,op_name as operator_name\n" .
        " from jaaglijst j left join jaaglijst_update ju using (id) natural join transaction_operator where id =$id\n" .
        "union\n" .
        "select to_char((select value::timestamp from peer_settings where key='jaag_import'),'YYYY-MM-DD HH24:MI') as date_time,\n" .
        "j.diplvo, \n" .
        "j.cijfer,\n" .
        "j.betbew,\n" .
        "j.pasprt,\n" .
        "j.uittre, \n" .
        "j.renrij, \n" .
        " 0 as trans_id,0 as operator,'Initial import from PS' as operator_name\n" .
        " from jaaglijst j where id =$id\n" .
        "order by trans_id desc";
$resultSet = $dbConn->execute( $sqlhistory );
if ( $resultSet === false ) {
  echo ( "<br>Cannot get prj_id milestone " . $sql . " reason " . $dbConn->ErrorMsg() . "<br>");
}
$hisTable = "<table border='1' style='border-collapse:collapse'>
<tr>
<th class='tabledata head' style='text-algin:left;'>Date time</th>

  <th class='tabledata head' style='text-algin:left;'>Diplvo</th>
  <th class='tabledata head' style='text-algin:left;'>Cijfer</th>
  <th class='tabledata head' style='text-algin:left;'>Betbew</th>
  <th class='tabledata head' style='text-algin:left;'>Pasprt</th>
  <th class='tabledata head' style='text-algin:left;'>Uittre</th>
  <th class='tabledata head' style='text-algin:left;'>Studielink</th>
  <th class='tabledata head' style='text-algin:left;'>Trans id</th>

  <th class='tabledata head' style='text-algin:left;'>Operator</th>
  <th class='tabledata head' style='text-algin:left;'>Operator name</th>
</tr>\n";

while ( !$resultSet->EOF ) {
  extract( $resultSet->fields );
  $hisTable .= "<tr>\n\t<td>$date_time</td>\n" .
          "\t<td class='$diplvo'>$diplvo</td>\n" .
          "\t<td class='$cijfer'>$cijfer</td>\n" .
          "\t<td class='$betbew'>$betbew</td>\n" .
          "\t<td class='$pasprt'>$pasprt</td>\n" .
          "\t<td class='$uittre'>$uittre</td>\n" .
          "\t<td class='$renrij'>$renrij</td>\n" .
          "\t<td style='text-align:right'>$trans_id</td>\n" .
          "\t<td style='text-align:center'>$operator</td>\n" .
          "\t<td>$operator_name</td>\n" .
          "</tr>\n";
  $resultSet->moveNext();
}
$hisTable .="</table>\n";
include 'templates/buit.xhtml';
?>