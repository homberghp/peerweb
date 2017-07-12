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
-- Name: student_latin1; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW student_latin1 AS
 SELECT student.snummer,
    convert_to((student.achternaam)::text, 'iso_8859_1'::name) AS achternaam,
    convert_to((student.tussenvoegsel)::text, 'iso_8859_1'::name) AS tussenvoegsel,
    student.voorletters,
    convert_to((student.roepnaam)::text, 'iso_8859_1'::name) AS roepnaam,
    convert_to((student.straat)::text, 'iso_8859_1'::name) AS straat,
    student.huisnr,
    student.pcode,
    convert_to((student.plaats)::text, 'iso_8859_1'::name) AS plaats,
    student.email1,
    student.nationaliteit,
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
    student.hoofdgrp,
    student.active,
    student.slb,
    student.land,
    student.studieplan
   FROM student;


ALTER TABLE student_latin1 OWNER TO hom;

--
-- Name: VIEW student_latin1; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW student_latin1 IS 'win/excel do not grasp utf-8 encoding header';


--
-- Name: student_latin1; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE student_latin1 TO peerweb;


--
-- PostgreSQL database dump complete
--

