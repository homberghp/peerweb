CREATE OR REPLACE FUNCTION public.insert_student_email()
 RETURNS trigger
  LANGUAGE plpgsql
  AS $function$
  DECLARE
     cohort integer;
  BEGIN
    IF new.cohort is null THEN
      cohort := date_part('year'::text, (now())::date);
    ELSE
      cohort := new.cohort;
    END IF;
    
    INSERT INTO student (snummer, achternaam, voorvoegsel, voorletters, roepnaam,
    	       straat, huisnr, pcode, plaats, email1, nationaliteit,
               hoofdgrp, active, cohort, gebdat, sex, lang, pcn,
 	       opl, phone_home, phone_gsm, phone_postaddress,
	       faculty_id, slb, studieplan,
	       geboorteplaats, geboorteland, voornaam, class_id)
    VALUES (new.snummer, new.achternaam, new.voorvoegsel, new.voorletters, new.roepnaam,
   	   new.straat, new.huisnr, new.pcode, new.plaats, new.email1, new.nationaliteit,
	   new.hoofdgrp, new.active, cohort, new.gebdat, new.sex, new.lang, new.pcn,
	   new.opl, new.phone_home, new.phone_gsm, new.phone_postaddress,
	   new.faculty_id, new.slb, new.studieplan,
	   new.geboorteplaats, new.geboorteland, new.voornaam, new.class_id
	);
  INSERT INTO alt_email (snummer, email2)
  SELECT new.snummer,new.email2
  WHERE (new.email2 IS NOT NULL);
  return NEW; -- TO SIGNAL SUCCESS
  END;
  $function$
