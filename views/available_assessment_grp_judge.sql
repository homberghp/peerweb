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
-- Name: available_assessment_grp_judge; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW available_assessment_grp_judge AS
 SELECT DISTINCT assessment_tr.prjtg_id,
    assessment_tr.judge
   FROM assessment_tr;


ALTER TABLE available_assessment_grp_judge OWNER TO hom;

--
-- Name: VIEW available_assessment_grp_judge; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW available_assessment_grp_judge IS 'assessment enabled for this judge';


--
-- Name: available_assessment_grp_judge; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE available_assessment_grp_judge TO peerweb;


--
-- PostgreSQL database dump complete
--

