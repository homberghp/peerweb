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
-- Name: milestone_open_past_due; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW milestone_open_past_due AS
 SELECT pm.prj_id,
    pm.milestone,
    pg.snummer,
    pt.prjtg_id,
    pm.assessment_due
   FROM ((prj_grp pg
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  WHERE ((pm.assessment_due < now()) AND (pg.prj_grp_open = true) AND (pm.prj_milestone_open = true));


ALTER TABLE milestone_open_past_due OWNER TO hom;

--
-- Name: milestone_open_past_due; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE milestone_open_past_due TO peerweb;


--
-- PostgreSQL database dump complete
--

