--
-- PostgreSQL database dump
--

-- Dumped from database version 9.6.3
-- Dumped by pg_dump version 9.6.3

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
 SELECT student.snummer,
    student.achternaam,
    student.tussenvoegsel,
    student.voorletters,
    student.roepnaam,
    student.straat,
    student.huisnr,
    student.pcode,
    student.plaats,
    student.email1,
    student.nationaliteit,
    student.hoofdgrp,
    student.active,
    student.cohort,
    student.gebdat,
    student.sex,
    student.lang,
    student.pcn,
    student.opl,
    student.phone_home,
    student.phone_gsm,
    student.phone_postaddress,
    student.faculty_id,
    alt_email.email2,
    student.slb,
    COALESCE((registered_photos.snummer || '.jpg'::text), 'anonymous.jpg'::text) AS image,
    student.class_id,
    student.studieplan,
    student.geboorteplaats,
    student.geboorteland,
    student.voornamen AS voornaam
   FROM ((student
     LEFT JOIN alt_email USING (snummer))
     LEFT JOIN registered_photos USING (snummer));


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
    ON UPDATE TO student_email DO INSTEAD ( UPDATE student SET achternaam = new.achternaam, tussenvoegsel = new.tussenvoegsel, voorletters = new.voorletters, roepnaam = new.roepnaam, straat = new.straat, huisnr = new.huisnr, pcode = new.pcode, plaats = new.plaats, email1 = new.email1, nationaliteit = new.nationaliteit, hoofdgrp = new.hoofdgrp, active = new.active, cohort = new.cohort, gebdat = new.gebdat, sex = new.sex, lang = new.lang, pcn = new.pcn, opl = new.opl, phone_home = new.phone_home, phone_gsm = new.phone_gsm, phone_postaddress = new.phone_postaddress, faculty_id = new.faculty_id, slb = new.slb, studieplan = new.studieplan, geboorteplaats = new.geboorteplaats, geboorteland = new.geboorteland, voornamen = new.voornaam, class_id = new.class_id
  WHERE (student.snummer = new.snummer);
 DELETE FROM alt_email
  WHERE ((alt_email.snummer = new.snummer) AND (new.email2 IS NULL) AND (NOT (old.email2 IS NULL)) AND (alt_email.email3 IS NULL));
 INSERT INTO alt_email (snummer, email2)  SELECT new.snummer,
            new.email2
          WHERE (NOT (new.snummer IN ( SELECT alt_email.snummer
                   FROM alt_email)));
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

