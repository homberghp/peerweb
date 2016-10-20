begin work;
create or replace view sebiassessment_student_view
as select snummer,'x'||snummer,
   password, uid,gid,
   achternaam,roepnaam,voorvoegsel,
   opl,cohort,
   email1,pcn,sclass,lang,hoofdgrp
   from student s join passwd pw on(s.snummer=pw.userid)
   join unix_uid using(snummer)
   join student_class sc on(s.class_id=sc.class_id);

commit;
