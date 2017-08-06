--
-- PostgreSQL database dump
--
begin work;
-- Dumped from database version 9.6.3
-- Dumped by pg_dump version 9.6.3
drop view if exists student_email;
SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

SET search_path = public, pg_catalog;

--
-- Name: student_email; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW student_email AS
 SELECT s.*,
    am.email2,
    COALESCE((rp.snummer || '.jpg'::text), 'anonymous.jpg'::text) AS image
   FROM student s
     LEFT JOIN alt_email am USING (snummer)
     LEFT JOIN registered_photos rp USING (snummer);


ALTER TABLE student_email OWNER TO hom;

--
-- Name: student_email student_email_delete; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE student_email_delete AS
    ON DELETE TO student_email DO INSTEAD NOTHING;
--
-- Name: student_email student_email_update; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE student_email_update AS
    ON UPDATE TO student_email DO INSTEAD (
 update student set ( achternaam, tussenvoegsel, voorletters, roepnaam, straat, huisnr,
 		      pcode, plaats, email1, nationaliteit, cohort, gebdat, sex,
		      lang, pcn, opl, phone_home, phone_gsm, phone_postaddress,
		      faculty_id, hoofdgrp, active, slb, land, studieplan, geboorteplaats,
		      geboorteland, voornamen, class_id )=
		     ( new.achternaam, new.tussenvoegsel, new.voorletters, new.roepnaam, new.straat, new.huisnr,
		       new.pcode, new.plaats, new.email1, new.nationaliteit, new.cohort, new.gebdat, new.sex,
		       new.lang, new.pcn, new.opl, new.phone_home, new.phone_gsm, new.phone_postaddress,
		       new.faculty_id, new.hoofdgrp, new.active, new.slb, new.land, new.studieplan, new.geboorteplaats,
		       new.geboorteland, new.voornamen, new.class_id
	)  where snummer = new.snummer;
	
 INSERT INTO alt_email (snummer, email2)  SELECT new.snummer,
            new.email2 where new.email2 notnull
	    on conflict on constraint alt_email_pkey  do nothing;
 UPDATE alt_email SET email2 = new.email2
  WHERE ((alt_email.snummer = new.snummer) AND (NOT (new.email2 IS NULL)));
);


--
-- Name: student_email student_email_insert; Type: TRIGGER; Schema: public; Owner: hom
--

CREATE TRIGGER student_email_insert INSTEAD OF INSERT ON student_email FOR EACH ROW EXECUTE PROCEDURE insert_student_email();


--
-- Name: student_email; Type: ACL; Schema: public; Owner: hom
--

GRANT ALL ON TABLE student_email TO peerweb;


--
-- PostgreSQL database dump complete
--

commit;
