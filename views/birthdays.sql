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
-- Name: birthdays; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW birthdays AS
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
    student.cohort,
    student.gebdat,
    student.sex,
    student.phone_home,
    student.phone_gsm,
    student.lang,
    student.class_id,
    faculty.faculty_short
   FROM ((student
     JOIN faculty ON ((student.faculty_id = faculty.faculty_id)))
     JOIN student_class classes USING (class_id))
  WHERE ((classes.sclass !~~ 'UITVAL%'::text) AND (to_char((student.gebdat)::timestamp with time zone, 'MM-DD'::text) = to_char(((now())::date)::timestamp with time zone, 'MM-DD'::text)));


ALTER TABLE birthdays OWNER TO hom;

--
-- Name: VIEW birthdays; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW birthdays IS 'Who''s birthday is it today?';


--
-- Name: birthdays; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE birthdays TO peerweb;


--
-- PostgreSQL database dump complete
--

