create table if not exists prospects as select * from importer.sv05_as_student_email_v where false;
grant all on prospects to public;
-- if not created, make sure it is empty.
alter table prospects drop constraint if exists prospects_snummer_pk;

alter table prospects add constraint prospects_snummer_pk primary key (snummer);
-- upsert, on update, update ass but slb and class_id
insert into prospects select * from importer.sv05_as_student_email_v
on conflict(snummer)
        do update set (achternaam, tussenvoegsel, voorletters, roepnaam, straat, huisnr, pcode, 
	   	       plaats, email1, nationaliteit, cohort, gebdat, sex, lang, pcn, 
		       opl, phone_home, phone_gsm, phone_postaddress, faculty_id, 
		       hoofdgrp, active, land, studieplan, geboorteplaats, geboorteland, voornamen, 
		        email2) =
		       (EXCLUDED.achternaam, EXCLUDED.tussenvoegsel, EXCLUDED.voorletters, EXCLUDED.roepnaam, EXCLUDED.straat, EXCLUDED.huisnr, EXCLUDED.pcode,
		        EXCLUDED.plaats, EXCLUDED.email1, EXCLUDED.nationaliteit, EXCLUDED.cohort, EXCLUDED.gebdat, EXCLUDED.sex, EXCLUDED.lang, EXCLUDED.pcn, 
			EXCLUDED.opl, EXCLUDED.phone_home, EXCLUDED.phone_gsm, EXCLUDED.phone_postaddress, EXCLUDED.faculty_id,
			EXCLUDED.hoofdgrp, EXCLUDED.active, EXCLUDED.land, EXCLUDED.studieplan, EXCLUDED.geboorteplaats, EXCLUDED.geboorteland, EXCLUDED.voornamen, 
			EXCLUDED.email2);
