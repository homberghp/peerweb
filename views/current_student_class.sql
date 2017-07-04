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
-- Name: current_student_class; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW current_student_class AS
 SELECT student.snummer,
    student.class_id,
    date_part('year'::text, now()) AS course_year
   FROM student;


ALTER TABLE current_student_class OWNER TO hom;

--
-- Name: current_student_class; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE current_student_class TO peerweb;


--
-- PostgreSQL database dump complete
--

