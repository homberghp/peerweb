<!-- criteria1.html.inc -->
<p>The criteria are used to let the students judge their project team members on these aspects
  In the current implementation all criteria have the same relative
  weight.</p>
<p>This setting (the criteria to apply) is only effective <i>during
  creation</i> of an assessment (page project_groups, action open), when
  this table is used to prefill the assessment table with the chose
  criteria for this project on this page. If you close and reopen a
  project_milestone any criteria that where new since the last open
  will be added. Criteria in a once opened assessment will <i>not</i> be
  removed by this  actions on this page.</p>
<p>If the criteria do not meet your needs, add some of your own on page <a href='base_criteria.php' target='_blank'>criteria definition</a>.</p>
<p>If the assessment is open or used you cannot change the criteria anymore.</p>
			<h2>Project {$prj_id} {$afko} {$description} {$year} milestone {$milestone} (prjm_id={$prjm_id})</h2>
  <p>Project Id (for change)</th><td>{$project_selector}</p>
  <p>Owning tutor {$tutor_owner}:&nbsp;{$roepnaam} {$tussenvoegsel} {$achternaam}</p>
  {$table}
    <p>Set of criteria</th><td>{$input_submit_button}</p>
<!-- end template criteria1.html.inc \$Id: criteria3.html.inc 1723 2014-01-03 08:34:59Z hom $ -->
