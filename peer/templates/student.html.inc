    <form id='studentdata' method='post' action='<?=$PHP_SELF?>'>
      <fieldset><legend>Peerweb data on you</legend>
	<table border="0">
	  <tr>
	    <th align='left'>name</th>
	    <td> <?=$name?></td>
	  </tr>
	  <tr>
	    <th align='left'>achternaam</th>
	    <th align='left'>voorvoegsel</th>
	    <th align='left'>voorletters</th>
	    <th align='left'>roepnaam</th>
	  </tr>
	  <tr>
	    <td><?=$achternaam?></td>
	    <td><?=$voorvoegsel?></td> 
	    <td><?=$voorletters?></td>
	    <td><?=$roepnaam?></td>
	  </tr>
	  <tr>
	    <th align='left'>snummer</th>
	    <th align='left'>cohort</th>
	    <th align='left'>PCN</th>
          </tr>
          <tr>
	    <td><?=$snummer?><input type='hidden' name='snummer_student_data' value='<?=$snummer?>'/></td>
	    <td><?=$cohort?></td>
	    <td><?=$pcn?></td>
       <td rowspan='10' width='192' style='horizontal-align:center'><img src='<?=$photo?>' alt='<?=$photo?>'/></td>
	  </tr>
	  <tr>
	    <th align='left'>straat</th>
	    <th align='left'>huisnr</th>
            <th align='left'>Study coach (SLB)</th>
	  </tr>
	  <tr>
	    <td><?=$straat?></td>
	    <td><?=$huisnr?></td>
            <td><?=$slb?></td>
	  </tr>
	  <tr>
	    <th align='left'>postcode</th>
	    <th align='left'>plaats</th>
	  </tr>
	  <tr>
	    <td><?=$pcode?></td>
	    <td><?=$plaats?></td>
	  </tr>
	  <tr>
	    <th align='left'>email1</th>
	    <td colspan='2'><?=$email1f?></td>
	  </tr>
	  <tr>
	    <th align='left'>nationaliteit</th>
	    <td colspan='2'><?=$nationaliteit?></td>
	  </tr>
	  <tr>
	    <th align='left'>instituut</th>
	    <td colspan='2'><?=$faculteit?></td>
	  </tr>
	  <tr>
	    <th align='left'>hoofdgrp</th>
            <th align='left'>klas</th>
	  </tr>
	  <tr>
	    <td><?=$hoofdgrp?></td>
            <td><?=$class_id?></td>
	  </tr>
	  <tr>
	    <th align='left'>Opl</th>
	    <td><?=$course_description?></td>
	  </tr>
          <?=$tweede_opl?>
	  <tr>
	    <th align='left'>gebdat</th>
	    <th align='left'>sex</th>
	  </tr>
	  <tr>
	    <td><?=$gebdat?></td>
	    <td><?=$sex?></td>
	  </tr>
	  <tr>
	    <th align='left'>phone_home</th>
	    <th align='left'>phone_gsm</th>
	    <th align='left'>phone_postadres</th>
	  </tr>
	  <tr>
	    <td><input type='text' size='20' name='new_phone_home' value='<?=$phone_home?>'/></td>
	    <td><input type='text' size='20' name='new_phone_gsm' value='<?=$phone_gsm?>'/></td>
	    <td><input type='text' size='20' name='new_phone_postaddress' value='<?=$phone_postaddress?>'/></td>
	  </tr>
	  <tr>
	    <th align='left'>lang</th>
	    <td colspan='2'><?=$lang?></td>
	  </tr>
	  <tr>
	    <td><input type='reset'/></td>
	    <td><input type='submit' name='bsubmit_student_data' value='Submit'/></td>
	  </tr>
	</table>
      </fieldset>
    </form>
