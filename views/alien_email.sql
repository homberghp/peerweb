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
-- Name: alien_email; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW alien_email AS
 SELECT student.snummer
   FROM student
  WHERE (((student.email1)::text !~~ '%fontys.nl'::text) AND (student.hoofdgrp !~~ 'ALU%'::text));


ALTER TABLE alien_email OWNER TO hom;

--
-- Name: VIEW alien_email; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW alien_email IS 'email not fitting the fontys mold, except alumni';


--
-- Name: alien_email; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE alien_email TO peerweb;


--
-- PostgreSQL database dump complete
--

