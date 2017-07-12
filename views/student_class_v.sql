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
-- Name: student_class_v; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW student_class_v AS
 SELECT student.snummer,
    student.class_id
   FROM student;


ALTER TABLE student_class_v OWNER TO hom;

--
-- Name: student_class_v; Type: ACL; Schema: public; Owner: hom
--

GRANT ALL ON TABLE student_class_v TO peerweb;


--
-- PostgreSQL database dump complete
--

