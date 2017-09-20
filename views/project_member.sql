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
-- Name: project_member; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW project_member AS
 SELECT DISTINCT pm.prj_id,
    pg.snummer
   FROM ((prj_milestone pm
     JOIN prj_tutor pt USING (prjm_id))
     JOIN prj_grp pg USING (prjtg_id));


ALTER TABLE project_member OWNER TO hom;

--
-- Name: VIEW project_member; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW project_member IS 'project member without milstone';


--
-- Name: project_member; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE project_member TO peerweb;


--
-- PostgreSQL database dump complete
--

