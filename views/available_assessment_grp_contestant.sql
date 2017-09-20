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
-- Name: available_assessment_grp_contestant; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW available_assessment_grp_contestant AS
 SELECT DISTINCT assessment_tr.prjtg_id,
    assessment_tr.contestant
   FROM assessment_tr;


ALTER TABLE available_assessment_grp_contestant OWNER TO hom;

--
-- Name: VIEW available_assessment_grp_contestant; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW available_assessment_grp_contestant IS 'assessment enabled for this contestant';


--
-- Name: available_assessment_grp_contestant; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE available_assessment_grp_contestant TO peerweb;


--
-- PostgreSQL database dump complete
--

