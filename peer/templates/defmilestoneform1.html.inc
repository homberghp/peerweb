<!--/* \$Id: defmilestoneform1.html.inc 1723 2014-01-03 08:34:59Z hom $*/ -->
<table border='3' style='empty-cells:show;border-collapse:3d' rules='groups' frame='box' width='100%' summary='milestone definition form'>
  <colgroup>
    <col width='50%'/>
      <col width='50%'/>
  </colgroup>
  <thead>
    <tr><th class='theadleft'>$prj_id $afko $description</th><th class='theadright'>Year $year</th></tr>
  </thead>
  <tbody>
    <tr><th class='thl'>Project Form</th><td>$project_selector</td></tr>
    <tr><th class='thl'>Project Id </th><td>$afko</td></tr>
    <tr><th class='thl'>Project description </th><td>$description</td></tr>
    <tr><th class='thl'>Year of execution (start year)</th><td>$year</td></tr>
    <tr><th class='thl'>Number of milestones</th><td><input type='text' size='2' align='right' name='milestones' value='$milestones'/>$submit_button</td></tr>
  </tbody>
  <thead> 
    <tr><th class='theadleft'>Owning tutor</th><th class='theadright' style='text-align:left;'>$tutor_owner:&nbsp;$roepnaam $tussenvoegsel $achternaam</th></tr>
  </thead>
</table>
<input type='reset'/>
<!-- /*end template \$Id: defmilestoneform1.html.inc 1723 2014-01-03 08:34:59Z hom $*/ -->
