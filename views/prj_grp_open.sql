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
-- Name: prj_grp_open; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW prj_grp_open AS
 SELECT bool_and(pg.prj_grp_open) AS bool_and,
    pm.prj_id,
    pm.milestone,
    pt.grp_num,
    pt.prjtg_id
   FROM ((prj_grp pg
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  GROUP BY pm.prj_id, pm.milestone, pt.grp_num, pt.prjtg_id;


ALTER TABLE prj_grp_open OWNER TO hom;

--
-- Name: prj_grp_open; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE prj_grp_open TO peerweb;


--
-- PostgreSQL database dump complete
--

