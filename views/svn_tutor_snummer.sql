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
-- Name: svn_tutor_snummer; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW svn_tutor_snummer AS
 SELECT pt.tutor_id AS snummer,
    pm.prj_id
   FROM (prj_tutor pt
     JOIN prj_milestone pm USING (prjm_id))
UNION
 SELECT project_scribe.scribe AS snummer,
    project_scribe.prj_id
   FROM project_scribe;


ALTER TABLE svn_tutor_snummer OWNER TO hom;

--
-- Name: VIEW svn_tutor_snummer; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW svn_tutor_snummer IS 'get snummer from repo authz file in svn admin page';


--
-- Name: svn_tutor_snummer; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE svn_tutor_snummer TO peerweb;


--
-- PostgreSQL database dump complete
--

