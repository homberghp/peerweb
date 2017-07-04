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
-- Name: assessment_milestones; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW assessment_milestones AS
 SELECT DISTINCT prj_tutor.prjm_id
   FROM (assessment_groups2
     JOIN prj_tutor USING (prjtg_id));


ALTER TABLE assessment_milestones OWNER TO hom;

--
-- Name: assessment_milestones; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE assessment_milestones TO peerweb;


--
-- PostgreSQL database dump complete
--

