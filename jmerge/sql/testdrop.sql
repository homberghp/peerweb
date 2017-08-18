begin work;
alter table student disable trigger all;
delete  from student where faculty_id=27 and cohort in (2016,2015);
alter table student enable trigger all;
commit;
