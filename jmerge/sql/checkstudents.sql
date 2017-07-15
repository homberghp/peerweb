begin work;
grant all on worksheet to peerweb;
select * from worksheet w where not exists (select 1 from student where w.snummer=snummer);
commit;
