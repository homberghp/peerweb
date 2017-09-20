-- üß utf-8
begin work;
-- most of the code is to create a summary of the operation.

with pre as (select count(1) as x, 'start student count'::text as comment, 1 as row from student ),
     synccount as (select count(1) as x ,'records from progress'::text as comment,2 as row  from importer.sv09_ingeschrevenen),
     synccounta as (select count(1) as x ,'distinct on student number'::text as comment,3 as row  from importer.sv09_as_student_email_v),
     updatecount as (select count(1) as x ,'updated student records'::text as comment,4 as row  from importer.sv09_as_student_email_v join student using(snummer))
     insert into sv09_import_summary
     select pre.x,comment,row from pre union
     select synccount.x, comment,row from synccount union
     select synccounta.x, comment,row from synccounta union
     select updatecount.x,comment,row from updatecount ;

-- the actual work is quite simple, using and updatable view with a trigger.
insert into student_email select * from importer.sv09_as_student_email_v;

-- finish reporting after instertion.
with post as (select count(1) as x, 'final student count'::text as comment,5 as row from student)
insert into sv09_import_summary select post.x,comment,row from post;

with post as (select a.x-b.x as x ,'added students by this import'::text as comment ,
              6 as row from sv09_import_summary a , sv09_import_summary b  where a.row=5 and b.row=1 )
insert into sv09_import_summary select post.x,comment,row from post;

commit;
