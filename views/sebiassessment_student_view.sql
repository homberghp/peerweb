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
-- Name: sebiassessment_student_view; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW sebiassessment_student_view AS
 SELECT s.snummer,
    ('x'::text || s.snummer) AS username,
    pw.password,
    unix_uid.uid,
    unix_uid.gid,
    s.achternaam,
    s.roepnaam,
    s.tussenvoegsel,
    s.opl,
    s.cohort,
    s.email1,
    s.pcn,
    sc.sclass,
    s.lang,
    s.hoofdgrp
   FROM (((student s
     JOIN passwd pw ON ((s.snummer = pw.userid)))
     JOIN unix_uid USING (snummer))
     JOIN student_class sc ON ((s.class_id = sc.class_id)));


ALTER TABLE sebiassessment_student_view OWNER TO hom;

--
-- PostgreSQL database dump complete
--

