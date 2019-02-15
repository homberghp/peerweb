begin work;

CREATE OR REPLACE FUNCTION public.iscribe_selector(peerid integer)
 RETURNS TABLE(name text, value integer,namegrp text,ismine int ,css_class text)
 LANGUAGE plpgsql
AS $iscribe_selector$
begin 
      return query
      with my_projects1 as (
      	   select prjm_id,0 as ismine from prj_milestone join project using (prj_id) where peerid=owner_id
      	   union
      	   select distinct prjm_id,1 as ismine from prj_tutor where peerid=tutor_id
      	   union
      	   select distinct prjm_id,2 as ismine from project_scribe join prj_milestone using(prj_id) where peerid=scribe
      ), my_projects as (
		select distinct prjm_id,ismine from my_projects1
		)
      select trim(course_short)||'.'||afko||': '||substr(description,1,12)
			||'('||year||')'||' ['||tutor||', PM#'||prjm_id||'] '||' mils. '||milestone||coalesce(': '||milestone_name,'') as name,
	    prjm_id as value,
	    year||' ['||tutor||']' as namegrp,
	    mp.ismine,
	    case when now()::date <= valid_until and now()::date <=assessment_due then 'active' else 'inactive' end as css_class 
	    from my_projects mp
	    join prj_milestone using(prjm_id)
	    join project using(prj_id)
	    join fontys_course using(course)
	    join tutor on (owner_id=userid)
	    order by year,ismine, css_class,tutor,afko,milestone;
end
  $iscribe_selector$;

select * from iscribe_selector(879417);

commit;
