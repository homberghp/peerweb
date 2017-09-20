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
-- Name: task_timer_group_sum; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW task_timer_group_sum AS
 SELECT pt.grp_num,
    pm.prj_id,
    pm.milestone,
    sum((tt.stop_time - tt.start_time)) AS project_time,
    pt.prjtg_id
   FROM (((task_timer tt
     JOIN prj_milestone pm ON (((tt.prj_id = pm.prj_id) AND (tt.milestone = pm.milestone))))
     JOIN prj_tutor pt ON ((pm.prjm_id = pt.prjm_id)))
     JOIN prj_grp pg ON (((pg.prjtg_id = pt.prjtg_id) AND (tt.snummer = pg.snummer))))
  GROUP BY pt.prjtg_id, pt.grp_num, pm.prj_id, pm.milestone;


ALTER TABLE task_timer_group_sum OWNER TO hom;

--
-- Name: task_timer_group_sum; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE task_timer_group_sum TO peerweb;


--
-- PostgreSQL database dump complete
--

