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
-- Name: all_project_scribe; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW all_project_scribe AS
 SELECT ps.prj_id,
    ps.scribe
   FROM project_scribe ps
UNION
 SELECT pm.prj_id,
    pt.tutor_id AS scribe
   FROM (prj_milestone pm
     JOIN prj_tutor pt ON ((pm.prjm_id = pt.prjm_id)));


ALTER TABLE all_project_scribe OWNER TO hom;

--
-- Name: VIEW all_project_scribe; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW all_project_scribe IS 'tutors and scribes can update presence and tasks';


--
-- Name: all_project_scribe; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE all_project_scribe TO peerweb;


--
-- PostgreSQL database dump complete
--

