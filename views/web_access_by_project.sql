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
-- Name: web_access_by_project; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW web_access_by_project AS
 SELECT DISTINCT prj_grp.snummer AS username,
    prj_milestone.prj_id
   FROM ((prj_grp
     JOIN prj_tutor USING (prjtg_id))
     JOIN prj_milestone USING (prjm_id))
UNION
 SELECT DISTINCT prj_tutor.tutor_id AS username,
    prj_milestone.prj_id
   FROM (prj_tutor
     JOIN prj_milestone USING (prjm_id));


ALTER TABLE web_access_by_project OWNER TO hom;

--
-- Name: web_access_by_project; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,REFERENCES ON TABLE web_access_by_project TO peerweb;
GRANT SELECT,REFERENCES ON TABLE web_access_by_project TO wwwrun;


--
-- PostgreSQL database dump complete
--

