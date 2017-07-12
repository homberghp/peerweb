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
-- Name: svn_auditor; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW svn_auditor AS
 SELECT DISTINCT project_scribe.scribe AS auditor,
    project_scribe.prj_id,
    prj_milestone.milestone,
    prj_milestone.prjm_id
   FROM (project_scribe
     JOIN prj_milestone USING (prj_id))
  WHERE (NOT (project_scribe.scribe IN ( SELECT tutor.userid
           FROM tutor)));


ALTER TABLE svn_auditor OWNER TO hom;

--
-- Name: svn_auditor; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,REFERENCES ON TABLE svn_auditor TO wwwrun;


--
-- PostgreSQL database dump complete
--

