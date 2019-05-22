
with ng as ( select snummer, row_number() over()
   from prj_grp natural join prj_tutor where prjm_id=954
) , pt as (select prjtg_id,grp_num from prj_tutor where prjm_id=978)

insert into prj_grp (snummer,prjtg_id) select snummer,prjtg_id  from ng join pt on(row_number=grp_num);
