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
-- Name: student_plus; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW student_plus AS
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
    alt_email.email2
   FROM (student
     LEFT JOIN alt_email USING (snummer));


ALTER TABLE student_plus OWNER TO hom;

--
-- Name: student_plus; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE student_plus TO peerweb;


--
-- PostgreSQL database dump complete
--

