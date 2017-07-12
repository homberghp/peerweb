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
-- Name: nationality; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW nationality AS
 SELECT iso3166.a2 AS value,
    initcap((iso3166.country)::text) AS name
   FROM iso3166;


ALTER TABLE nationality OWNER TO hom;

--
-- Name: VIEW nationality; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW nationality IS 'used in student_admin';


--
-- Name: nationality; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE nationality TO peerweb;


--
-- PostgreSQL database dump complete
--

