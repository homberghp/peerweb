with pro as (select * from all_prj_tutor where prjm_id=861),
  rec as (select snummer as recipient,prjtg_id from prj_grp join pro using(prjtg_id)
  union select tutor_id as recipient,prjtg_id from pro)
select distinct snummer, email1 as email, email2,
       roepnaam ||' '||coalesce(tussenvoegsel||' ','')||achternaam as name,roepnaam,
       afko,description,milestone,assessment_due as due,milestone_name
  from rec  join pro using(prjtg_id) join student_email on(recipient=snummer) where snummer in (3162869,879417);

