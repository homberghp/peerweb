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
-- Name: unknown_student; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW unknown_student AS
 SELECT import_naw.snummer,
    import_naw.achternaam,
    import_naw.tussenvoegsel,
    import_naw.voorletters,
    import_naw.roepnaam,
    import_naw.straat,
    import_naw.huisnr,
    import_naw.pcode,
    import_naw.plaats,
    import_naw.email1,
    import_naw.nationaliteit,
    import_naw.cohort,
    import_naw.gebdat,
    import_naw.sex,
    import_naw.lang,
    import_naw.pcn,
    import_naw.phone_home,
    import_naw.phone_gsm,
    import_naw.phone_postaddress,
    import_naw.faculty_id,
    import_naw.land,
    import_naw.geboorteplaats,
    import_naw.geboorteland,
    import_naw.voornaam
   FROM import_naw
  WHERE (NOT (import_naw.snummer IN ( SELECT student.snummer
           FROM student)));


ALTER TABLE unknown_student OWNER TO hom;

--
-- PostgreSQL database dump complete
--

