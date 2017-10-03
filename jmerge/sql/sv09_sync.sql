-- üß utf-8
begin work;

create table if not exists sv09_import_summary (x integer, comment varchar(40), row integer);
truncate sv09_import_summary;

-- create a summary of the operation.

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
-- insert into student_email select * from importer.sv09_as_student_email_v;
INSERT INTO student ( snummer,achternaam,tussenvoegsel,voorletters,roepnaam,
       	              straat,huisnr,pcode,plaats,
		      email1,nationaliteit,cohort,gebdat,
		      sex,lang,pcn,opl,phone_home,
		      phone_gsm,phone_postaddress,faculty_id,
		      hoofdgrp,active,slb,land,studieplan,
		      geboorteplaats,geboorteland,voornamen,class_id)
    select  	      snummer,achternaam,tussenvoegsel,voorletters,roepnaam,straat,huisnr,
	     	      pcode,plaats,email1,nationaliteit,cohort,gebdat,
		      sex,lang,pcn,opl,phone_home,
	     	      phone_gsm,phone_postaddress,faculty_id,
		      hoofdgrp,active,slb,land,studieplan,
	     	      geboorteplaats,geboorteland,voornamen,class_id
    from importer.sv09_as_student_email_v
    on conflict(snummer)
    -- NOT updating: hoofdgrp, slb, class_id. Not properly maintained on progress side.
    do update set ( snummer,achternaam,tussenvoegsel,voorletters,roepnaam,
       	            straat,huisnr,pcode,plaats,
		    email1,nationaliteit,cohort,gebdat,
		    sex,lang,pcn,opl,phone_home,
		    phone_gsm,phone_postaddress,faculty_id,
		    active,land,studieplan, -- not hoofdgrp not slb
		    geboorteplaats,geboorteland,voornamen) = -- not class_id
	          ( EXCLUDED.snummer,EXCLUDED.achternaam,EXCLUDED.tussenvoegsel,EXCLUDED.voorletters,EXCLUDED.roepnaam,
		    EXCLUDED.straat,EXCLUDED.huisnr,EXCLUDED.pcode,EXCLUDED.plaats,
		    EXCLUDED.email1,EXCLUDED.nationaliteit,EXCLUDED.cohort,EXCLUDED.gebdat,
		    EXCLUDED.sex,EXCLUDED.lang,EXCLUDED.pcn,EXCLUDED.opl,EXCLUDED.phone_home,
		    EXCLUDED.phone_gsm,EXCLUDED.phone_postaddress,EXCLUDED.faculty_id,
		    EXCLUDED.active,EXCLUDED.land,EXCLUDED.studieplan,
		    EXCLUDED.geboorteplaats,EXCLUDED.geboorteland,EXCLUDED.voornamen);
  update alt_email ae set email3=null where (snummer,email3) in (select snummer,email2 from importer.sv09_as_student_email_v);
  INSERT INTO alt_email (snummer, email2)
  select snummer,email2 from importer.sv09_as_student_email_v where (snummer,email2) not in ( select snummer,email2 from alt_email)
  on conflict(snummer)
  do update set email2=excluded.email2;


-- finish reporting after instertion.
with post as (select count(1) as x, 'final student count'::text as comment,5 as row from student)
insert into sv09_import_summary select post.x,comment,row from post;

with post as (select a.x-b.x as x ,'added students by this import'::text as comment ,
              6 as row from sv09_import_summary a , sv09_import_summary b  where a.row=5 and b.row=1 )
insert into sv09_import_summary select post.x,comment,row from post;

commit;
