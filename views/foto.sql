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
-- Name: foto; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW foto AS
 SELECT student.snummer,
    (((((('<img src="'::text || (foto_prefix.prefix)::text) || '/'::text) || (student.snummer)::text) || '.jpg" alt="'::text) || (student.snummer)::text) || '"/>'::text) AS image
   FROM student,
    foto_prefix;


ALTER TABLE foto OWNER TO hom;

--
-- Name: VIEW foto; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW foto IS 'used by images derived from snummers';


--
-- Name: foto; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE foto TO peerweb;


--
-- PostgreSQL database dump complete
--

