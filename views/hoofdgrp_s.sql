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
-- Name: hoofdgrp_s; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW hoofdgrp_s AS
 SELECT DISTINCT student.hoofdgrp,
    student.faculty_id,
    faculty.faculty_short,
    fontys_course.course,
    fontys_course.course_short
   FROM ((student
     JOIN faculty USING (faculty_id))
     JOIN fontys_course ON ((student.opl = fontys_course.course)));


ALTER TABLE hoofdgrp_s OWNER TO hom;

--
-- Name: hoofdgrp_s; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE hoofdgrp_s TO peerweb;


--
-- PostgreSQL database dump complete
--

