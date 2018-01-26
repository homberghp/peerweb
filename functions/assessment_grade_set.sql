CREATE OR REPLACE FUNCTION public.assessment_grade_set(grp integer, prod numeric)
 RETURNS SETOF grade_summer_result
 LANGUAGE plpgsql
AS $function$
begin 
return query 
select prjtg_id,snummer,array_agg(criterium) as criterium, array_agg(multiplier) as multiplier,array_agg(grade) as grade from (
select g2.prjtg_id,
       snummer,
       criterium,
       gsize,
       grade_sum_i,
       grade_sum_i::real/(gsize-1) as iav,
       round((grade_sum_g::real/(gsize*(gsize-1)))::numeric,2) as gav,
       round(((grade_sum_i*gsize)/(case when grade_sum_g <>0 then grade_sum_g else 1 end ))::numeric,2) as multiplier,
       round(prod*((grade_sum_i*gsize)/(case when grade_sum_g <>0 then grade_sum_g else 1 end ))::numeric,2) as grade
       from 
       (select * from (
       	       	      select prjtg_id,contestant as snummer,criterium,sum(grade)::real as grade_sum_i 
       	       	       	       from assessment 
       		       	       where prjtg_id=grp
       			       group by prjtg_id,contestant,criterium 
       			       order by prjtg_id,contestant,criterium
       			) i
		 join ( 
		      select prjtg_id,criterium,sum(grade)::real as grade_sum_g 
       	     	      	       from assessment 
       	     	      	       where prjtg_id=grp
       	     	      	       group by prjtg_id,criterium 
       	     	      	       order by prjtg_id,criterium
       			) g using(prjtg_id,criterium)
		 join (
		      select prjtg_id,count(*) as gsize from prj_grp group by prjtg_id) gs using(prjtg_id)
       		      	     order by snummer, criterium
		      ) g2 
union 
select prjtg_id,
       snummer, 
       99 as criterium,
       gsize,grade_sum_sum_i,
       grade_sum_sum_i::real/(gsize-1) as iavg,
       round((grade_sum_sum_g::real/(gsize*(gsize-1)))::numeric,2) as gavg,
       round(((grade_sum_sum_i*gsize)/(case when grade_sum_sum_g<>0 then grade_sum_sum_g else 1 end))::numeric,2) as multiplier, 
       round(prod*((grade_sum_sum_i*gsize)/(case when grade_sum_sum_g<>0 then grade_sum_sum_g else 1 end))::numeric,2) as grade
from (select * from (
select prjtg_id,contestant as snummer,sum(grade)::real as grade_sum_sum_i from assessment where prjtg_id=grp group by prjtg_id,snummer) g4
join (select prjtg_id,sum(grade) as grade_sum_sum_g from assessment where prjtg_id=grp group by prjtg_id) g5 using(prjtg_id)
join (select prjtg_id,count(*) as gsize from prj_grp group by prjtg_id ) gs2 using(prjtg_id) 

) g6
order by prjtg_id,snummer,criterium
) agg group by prjtg_id,snummer;
end
$function$

