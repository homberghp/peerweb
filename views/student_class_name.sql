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
-- Name: student_class_name; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW student_class_name AS
 SELECT student.snummer,
    classes.sclass
   FROM (student
     JOIN student_class classes USING (class_id));


ALTER TABLE student_class_name OWNER TO hom;

--
-- Name: student_class_name; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE student_class_name TO peerweb;


--
-- PostgreSQL database dump complete
--

