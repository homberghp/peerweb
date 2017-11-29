
begin work;

drop view if exists student_email;
alter table student alter column pcode type text using pcode::text;

\i student_email.sql
commit;
