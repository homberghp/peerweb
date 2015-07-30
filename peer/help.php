<?php
pagehead2('Help',"
<link rel='stylesheet' type='text/css' href='$root_url/style/newdivstyle.css'/>
<style type='text/css'>
 p {text-align: justify;}
 p:first-letter {font-size:180%; font-weight:bold; color:#800;}
 </style>
");

?>
<div class='navopening'>
<form action="close.html">
<table border='0' width='100%'>
 <tr>
  <td>
   <h1 style='color:white;'>Help peer web </h1>
  </td>
  <td align='right' width='15%'>
   <input type="image" src="<?=IMAGEROOT?>/error.png" name="close" alt="Close" onClick="self.close()">
  </td>
 </tr>
</table>
</form></div>
<div style='padding:2em 1em 2em 2em;'>
  <p>At the moment there is very little help...</p>
<form action="close.html">
<input type="button" class="button" name="close" value="Close" onClick="self.close()">
</form>
<hr>
</div>
<!-- $Id: help.php 1723 2014-01-03 08:34:59Z hom $ -->
</body>
</html>
