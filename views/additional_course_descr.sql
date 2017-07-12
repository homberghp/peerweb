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
-- Name: additional_course_descr; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW additional_course_descr AS
 SELECT additional_course.snummer,
    additional_course.course_code,
    fontys_course.course,
    fontys_course.course_description,
    fontys_course.faculty_id AS institute,
    fontys_course.course_short AS abre
   FROM (additional_course
     JOIN fontys_course ON ((additional_course.course_code = fontys_course.course)));


ALTER TABLE additional_course_descr OWNER TO hom;

--
-- Name: VIEW additional_course_descr; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW additional_course_descr IS 'describes additional course student is registered to';


--
-- Name: additional_course_descr; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE additional_course_descr TO peerweb;


--
-- PostgreSQL database dump complete
--

