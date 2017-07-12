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
-- Name: criteria_v; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW criteria_v AS
 SELECT prjm_criterium.prjm_id,
    prjm_criterium.criterium_id AS criterium,
    base_criteria.nl_short,
    base_criteria.de_short,
    base_criteria.en_short,
    base_criteria.nl,
    base_criteria.de,
    base_criteria.en
   FROM (prjm_criterium
     JOIN base_criteria USING (criterium_id));


ALTER TABLE criteria_v OWNER TO hom;

--
-- Name: criteria_v; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE criteria_v TO peerweb;


--
-- PostgreSQL database dump complete
--

