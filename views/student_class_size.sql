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
-- Name: student_class_size; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW student_class_size AS
 SELECT student.class_id,
    student.snummer,
    cs.student_count
   FROM ((student
     JOIN student_class classes USING (class_id))
     JOIN class_size cs USING (class_id));


ALTER TABLE student_class_size OWNER TO hom;

--
-- Name: student_class_size; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE student_class_size TO peerweb;


--
-- PostgreSQL database dump complete
--

