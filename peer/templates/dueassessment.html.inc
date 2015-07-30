<h2 class='normal'>Select the students you would like to warn by mail.</h2>

   $prj_id_selector

<fieldset><legend>Mail form</legend>
<p>The student names shown here have missed the deadline for a <i style='color:red'>still open</i> assessment. If the assessment is closed, the student names will 
  <i style='color:red'>not</i> show up, not even if they failed to fill in their assessment form.</p>
<form name='mailer' method='post' action='$PHP_SELF'>
  $selecttable
  <br/>
   <table><tr>
  <td><b>Subject:</b></td><td><input type='text' name='formsubject' size='60' value='$formsubject'/></td></tr>
  <tr><td valign='top'><b>Body text:</td><td>
    <textarea name='mailbody' cols='80' rows='20'>$mailbody</textarea></td></tr>
   <tr><th colspan='2'>$mailbutton</th></tr>
   </table>
</form>
<p>Note that you can change the default signature in 'personal data and settings &gt; email signature'</p>
</fieldset>
