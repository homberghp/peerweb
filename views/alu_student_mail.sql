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
-- Name: alu_student_mail; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW alu_student_mail AS
 SELECT student.snummer
   FROM (student
     JOIN alt_email USING (snummer))
  WHERE (((student.email1)::text ~~ '%student.fontys.nl%'::text) AND ((alt_email.email2)::text !~~ '%student.fontys.nl%'::text) AND (student.class_id = 363));


ALTER TABLE alu_student_mail OWNER TO hom;

--
-- PostgreSQL database dump complete
--

