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
-- Name: should_close_prj_milestone; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW should_close_prj_milestone AS
 SELECT ((pm.prj_milestone_open = true) AND (pm.prj_milestone_open <> alpt.any_tutor_open)) AS should_close,
    pm.prj_milestone_open,
    alpt.any_tutor_open,
    pm.prjm_id
   FROM (prj_milestone pm
     JOIN ( SELECT bool_or(prj_tutor.prj_tutor_open) AS any_tutor_open,
            prj_tutor.prjm_id
           FROM prj_tutor
          GROUP BY prj_tutor.prjm_id) alpt USING (prjm_id));


ALTER TABLE should_close_prj_milestone OWNER TO hom;

--
-- Name: should_close_prj_milestone; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE should_close_prj_milestone TO peerweb;


--
-- PostgreSQL database dump complete
--

