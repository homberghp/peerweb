
--
-- PostgreSQL database dump
--

-- Dumped from database version 10.6 (Ubuntu 10.6-1.pgdg18.04+1)
-- Dumped by pg_dump version 10.6 (Ubuntu 10.6-1.pgdg18.04+1)
begin work;
SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: student_email; Type: VIEW; Schema: public; Owner: hom
--
drop view if exists student_email;

CREATE VIEW public.student_email AS
 SELECT s.snummer,
    s.achternaam,
    s.tussenvoegsel,
    s.voorletters,
    s.roepnaam,
    s.straat,
    s.huisnr,
    s.pcode,
    s.plaats,
    s.email1,
    s.nationaliteit,
    s.cohort,
    s.gebdat,
    s.sex,
    s.lang,
    s.pcn,
    s.opl,
    s.phone_home,
    s.phone_gsm,
    s.phone_postaddress,
    s.faculty_id,
    s.hoofdgrp,
    s.active,
    s.slb,
    s.land,
    s.studieplan,
    s.geboorteplaats,
    s.geboorteland,
    s.voornamen,
    s.class_id,
    am.email2,
    COALESCE((rp.snummer || '.jpg'::text), 'anonymous.jpg'::text) AS image
   FROM ((public.student s
     LEFT JOIN public.alt_email am USING (snummer))
     LEFT JOIN public.registered_photos rp USING (snummer))
     where active;

ALTER TABLE public.student_email OWNER TO rpadmin;

--
-- Name: student_email student_email_delete; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE student_email_delete AS
    ON DELETE TO public.student_email DO INSTEAD NOTHING;


--
-- Name: student_email student_email_update; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE student_email_update AS
    ON UPDATE TO public.student_email DO INSTEAD ( UPDATE public.student SET achternaam = new.achternaam, tussenvoegsel = new.tussenvoegsel, voorletters = new.voorletters, roepnaam = new.roepnaam, straat = new.straat, huisnr = new.huisnr, pcode = new.pcode, plaats = new.plaats, email1 = new.email1, nationaliteit = new.nationaliteit, cohort = new.cohort, gebdat = new.gebdat, sex = new.sex, lang = new.lang, pcn = new.pcn, opl = new.opl, phone_home = new.phone_home, phone_gsm = new.phone_gsm, phone_postaddress = new.phone_postaddress, faculty_id = new.faculty_id, hoofdgrp = new.hoofdgrp, active = new.active, slb = new.slb, land = new.land, studieplan = new.studieplan, geboorteplaats = new.geboorteplaats, geboorteland = new.geboorteland, voornamen = new.voornamen, class_id = new.class_id
  WHERE (student.snummer = new.snummer) and student.active;
 INSERT INTO public.alt_email (snummer, email2)  SELECT new.snummer,
            new.email2
          WHERE (new.email2 IS NOT NULL) ON CONFLICT ON CONSTRAINT alt_email_pkey DO NOTHING;
 UPDATE public.alt_email SET email2 = new.email2
  WHERE ((alt_email.snummer = new.snummer) AND (NOT (new.email2 IS NULL)));
);


--
-- Name: student_email student_email_insert; Type: TRIGGER; Schema: public; Owner: hom
--

CREATE TRIGGER student_email_insert INSTEAD OF INSERT ON public.student_email FOR EACH ROW EXECUTE PROCEDURE public.insert_student_email();


--
-- Name: TABLE student_email; Type: ACL; Schema: public; Owner: hom
--

GRANT ALL ON TABLE public.student_email TO peerweb;


--
-- PostgreSQL database dump complete
--

commit;