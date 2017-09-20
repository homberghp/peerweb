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
-- Name: student_name_email; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW student_name_email AS
 SELECT student.snummer,
    convert_to(rtrim((student.roepnaam)::text), 'latin1'::name) AS roepnaam,
    student.tussenvoegsel,
    convert_to(rtrim((student.achternaam)::text), 'latin1'::name) AS achternaam
   FROM student;


ALTER TABLE student_name_email OWNER TO hom;

--
-- Name: student_name_email; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE student_name_email TO peerweb;


--
-- PostgreSQL database dump complete
--

