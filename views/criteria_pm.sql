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
-- Name: criteria_pm; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW criteria_pm AS
 SELECT base_criteria.criterium_id,
    base_criteria.author,
    base_criteria.nl_short,
    base_criteria.de_short,
    base_criteria.en_short,
    base_criteria.nl,
    base_criteria.de,
    base_criteria.en,
    prjm_criterium.prjm_id
   FROM (base_criteria
     JOIN prjm_criterium USING (criterium_id));


ALTER TABLE criteria_pm OWNER TO hom;

--
-- Name: criteria_pm; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE criteria_pm TO peerweb;


--
-- PostgreSQL database dump complete
--

