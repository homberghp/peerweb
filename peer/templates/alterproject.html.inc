$successReport

<p style='margin:2em'>Project and module are used interchangeably in this site. Using the PROGRESS code as project or modulecode is convenient.
Make sure that the combination of project code and year is unique.</p>

<p style='margin:2em'>Please do not reuse projects, but make new ones. Use this page only if
  you want to change (correct) any of the fields for an existing project definition.</p>

<fieldset id='form1'><legend><b>Project or module data</b></legend>
  <form method='post' name='project' action='$PHP_SELF'>
  <table border='3' style='empty-cells:show;border-collapse:3d' rules='groups' frame='box' summary='project alteration form'>
  <thead> 
  <tr><th class='theadleft'>Existing project</th><th class='theadright'>Existing project</th></tr>
  <tr><th class='theadleft'>Project </th><th class='theadright'>$prj_id $afko $description $year</th></tr>
  </thead>
  <tr><th class='thl'>Project Id $prj_id</th><td>$project_selector</td></tr>
  <tr><th class='thl'>1. Module or project code (<= 10 char.)</th>
       <td>$input_module_code 
       </td>
       </tr>
       <tr><th class='thl'>2. Year.</th><td>$input_year</td></tr>
       <tr><th class='thl'>3. Project description.(<= 30 char.)</th><td>$input_description</td></tr>
       <tr><th class='thl'>4. Valid until</th><td>$input_valid_until</td></tr>
       <tr><th class='thl'>5. Activity project</th><td>$input_activity_project</td></tr>
       <tr><th class='thl' >6. Course</th><td>$input_course</td></tr>
       <tr><th class='thl' >7. Comment</th><td>$input_comment</td></tr>
       <tr><td class='thl'><input type='reset' name='reset' value='Reset form' title='reset form to default'/></td>
       <td align='center'>$input_update_button (on changes) </td></tr>
       <thead> 
       <tr><th class='theadleft'>Owning tutor 
       </th><th class='theadright'>$owner_id:&nbsp;$roepnaam $voorvoegsel $achternaam</th></tr>
</thead>
</table>
</form>
<table summary='tutor owner form'>
	<thead>
	<tr>
	<th>$tutor_owner_form</th>
	<th>&nbsp;</th>
</tr>
</thead>
</table>

