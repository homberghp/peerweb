begin work;
create or replace view simple_group_member as
       	  select prjtg_id,snummer from prj_grp
	  union
	  select prjtg_id,tutor_id as snummer from prj_tutor;
	  

commit;
