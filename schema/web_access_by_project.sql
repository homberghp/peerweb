drop view if exists web_access_by_project;

create view web_access_by_project as
select ''||snummer as username, password, prj_id
       from prj_grp
       join prj_tutor using (prjtg_id)
       join prj_milestone using (prjm_id)
       join passwd on(snummer=userid)
       union
select ''||userid as username, password, prj_id
       from prj_tutor
       join prj_milestone using (prjm_id)
       join passwd on (tutor_id=userid)
;
