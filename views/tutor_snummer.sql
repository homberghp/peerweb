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
-- Name: tutor_snummer; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW tutor_snummer AS
 SELECT tutor.userid AS snummer,
    tutor.tutor AS tutor_code
   FROM tutor;


ALTER TABLE tutor_snummer OWNER TO hom;

--
-- Name: tutor_snummer; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE tutor_snummer TO peerweb;


--
-- PostgreSQL database dump complete
--

