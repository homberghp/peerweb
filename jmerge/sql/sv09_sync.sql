-- üß utf-8
begin work;
create table if not exists sv09_import_summary (x integer, comment varchar(40), row integer);
truncate sv09_import_summary;
with pre as (select count(1) as x, 'start student count'::text as comment, 1 as row from student ),
     synccount as (select count(1) as x ,'records from progress'::text as comment,2 as row  from importer.sv09_ingeschrevenen),
     updatecount as (select count(1) as x ,'updated student records'::text as comment,3 as row  from importer.sv09_as_student_email_v join student using(snummer))
     insert into sv09_import_summary
     select pre.x,comment,row from pre union
     select synccount.x, comment,row from synccount union
     select updatecount.x,comment,row from updatecount ;
     
insert into student_email select * from importer.sv09_as_student_email_v;

with post as (select count(1) as x, 'final student count'::text as comment,4 as row from student)
insert into sv09_import_summary select post.x,comment,row from post;

with post as (select a.x-b.x as x ,'added students by this import'::text as comment ,
              5 as row from sv09_import_summary a , sv09_import_summary b  where a.row=4 and b.row=1 )
insert into sv09_import_summary select post.x,comment,row from post;

commit;
