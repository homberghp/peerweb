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
-- Name: project_group; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW project_group AS
 SELECT (student.snummer)::text AS username,
    p.password,
    pt.prjm_id,
    pm.prj_id,
    pm.milestone,
    pt.grp_num AS gid
   FROM ((((student
     JOIN prj_grp pg USING (snummer))
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjm_id)))
     JOIN passwd p ON ((student.snummer = p.userid)))
UNION
 SELECT (pt.tutor_id)::text AS username,
    p.password,
    pt.prjm_id,
    pm.prj_id,
    pm.milestone,
    0 AS gid
   FROM ((prj_tutor pt
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjm_id)))
     JOIN passwd p ON ((p.userid = pt.tutor_id)))
UNION
 SELECT (project_auditor.snummer)::text AS username,
    p.password,
    project_auditor.prjm_id,
    prj_milestone.prj_id,
    prj_milestone.milestone,
    project_auditor.gid
   FROM ((project_auditor
     JOIN prj_milestone USING (prjm_id))
     JOIN passwd p ON ((project_auditor.snummer = p.userid)));


ALTER TABLE project_group OWNER TO hom;

--
-- Name: project_group; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE project_group TO peerweb;
GRANT SELECT ON TABLE project_group TO wwwrun;


--
-- PostgreSQL database dump complete
--

