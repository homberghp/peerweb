begin work;
drop materialized view  if exists linked_students;
drop table if exists linked_students;
create table linked_students as 
-- create materialized view  linked_students as
select snummer from prj_grp
union
select userid from tutor
union
select snummer from registered_mphotos
union
select snummer from registered_photos
union
select snummer from activity_participant
union
select snummer from project_auditor
union
select owner from personal_repos
union
select snummer from document_author
union
select operator from transaction
;
commit;
