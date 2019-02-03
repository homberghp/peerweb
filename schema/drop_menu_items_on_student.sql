begin work;

-- delete students that have never physically enrolled

truncate prospects;
truncate importer.sv05_aanmelders ;
truncate importer.sv09_ingeschrevenen ;
truncate importer.worksheet ;
truncate importer.blad1 ;

-- peerweb will oinly serve 'active' student data
revoke ALL on student from peerweb;
-- remove privacy data from student table
update student set ( straat, huisnr, pcode, plaats, phone_home, phone_gsm, phone_postaddress, geboorteplaats, geboorteland) =
                   ( null  , null , null , null  , null      , null     , null              , null          , null );

-- delete privacy data from student editor
delete from menu_item where menu_name='student_admin'
       and column_name in ('phone_gsm','phone_home','phone_postaddress','pcode','huisnr','plaats','straat','geboorteplaats','geboorteland','email2');

-- delete privacy data from prospects editor
delete from menu_item where menu_name='prospects'
       and column_name in ('phone_gsm','phone_home','phone_postaddress','pcode','huisnr','plaats','straat','geboorteplaats','geboorteland','email2','slb');

-- take out of view all users that are not in active classes
update student set  active=false where class_id not in (select class_id from student_class where sort1 <10);

-- take out of view all non FHTnL users
update student set active=false where faculty_id <> 47;

--commit;
rollback;
