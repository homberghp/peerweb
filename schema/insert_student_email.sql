begin work;
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
	)
	on conflict(snummer) do update set (achternaam, voorvoegsel, voorletters, roepnaam,
    	       straat, huisnr, pcode, plaats, email1, nationaliteit,
               hoofdgrp, active, cohort, gebdat, sex, lang, pcn,
 	       opl, phone_home, phone_gsm, phone_postaddress,
	       faculty_id, slb, studieplan,
	       geboorteplaats, geboorteland, voornaam, class_id)=(EXCLUDED.achternaam, EXCLUDED.voorvoegsel, EXCLUDED.voorletters, EXCLUDED.roepnaam,
   	   EXCLUDED.straat, EXCLUDED.huisnr, EXCLUDED.pcode, EXCLUDED.plaats, EXCLUDED.email1, EXCLUDED.nationaliteit,
	   EXCLUDED.hoofdgrp, EXCLUDED.active, EXCLUDED.cohort, EXCLUDED.gebdat, EXCLUDED.sex, EXCLUDED.lang, EXCLUDED.pcn,
	   EXCLUDED.opl, EXCLUDED.phone_home, EXCLUDED.phone_gsm, EXCLUDED.phone_postaddress,
	   EXCLUDED.faculty_id, EXCLUDED.slb, EXCLUDED.studieplan,
	   EXCLUDED.geboorteplaats, EXCLUDED.geboorteland, EXCLUDED.voornaam, EXCLUDED.class_id);
  INSERT INTO alt_email (snummer, email2)
  SELECT new.snummer,new.email2
  WHERE (new.email2 IS NOT NULL);
  insert into passwd (userid) select new.snummer where not exists (select 1 from passwd where userid=new.snummer);
  return NEW; -- TO SIGNAL SUCCESS
  END;
  $function$;

--rollback;
commit;
-- test
begin work;
 insert into student_email values(879417, 'Hombergh', 'van den', 'PJFJ', 'Gerard', 'Kerboschstraat', '12  ', '5913WH ', 'Venlo', 'p.vandenhombergh@fontys.nl', 'NL', 'TUTORINF  ',
true, 1955, '1955-03-18', 'M', 'NL', 879417, 112, '+31 77 3200075',
'+31 653 759 245', '+31 8850 79417', 47, NULL, 877516, '879417.jpg', 411, 864, 'Venlo', 'NLD', 'Petrus Joseph Franciscus Johannes') returning *;

insert into student_email values(879416, 'Hombergh', 'van den', 'PJFJ', 'Twan', 'Kerboschstraat', '12  ', '5913WH ', 'Venlo', 'p.vandenhombergh@fontys.nl', 'NL', 'TUTORINF  ',
true, 1955, '1955-03-18', 'M', 'NL', 879416, 112, '+31 77 3200075',
'+31 653 759 245', '+31 8850 79417', 47, NULL, 877516, '879417.jpg', 411, 864, 'Venlo', 'NLD', 'Petrus Joseph Franciscus Johannes');

select * from student_email where snummer in( 879416, 879417);
rollback;

