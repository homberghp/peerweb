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
-- Name: milestone_grp; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW milestone_grp AS
 SELECT DISTINCT pm.prj_id,
    pm.milestone
   FROM (prj_tutor pt
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  ORDER BY pm.prj_id, pm.milestone;


ALTER TABLE milestone_grp OWNER TO hom;

--
-- Name: milestone_grp; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE milestone_grp TO peerweb;


--
-- PostgreSQL database dump complete
--

