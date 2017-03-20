--
-- PostgreSQL database dump
--

-- Dumped from database version 9.6.2
-- Dumped by pg_dump version 9.6.2
begin work;
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
-- Name: student_project_attributes; Type: VIEW; Schema: public; Owner: hom
--
drop view if exists student_project_attributes;

CREATE VIEW student_project_attributes AS
 SELECT DISTINCT s.snummer,
    pg.snummer AS has_project,
    p.afko,
    p.year,
    p.description,
    pm.milestone,
    pm.milestone_name,
    pt.grp_num,
    pt.prjtg_id,
    p.valid_until,
    ag.snummer AS has_assessment,
    hd.prjm_id AS has_doc
   FROM ((((((student s
     JOIN prj_grp pg USING (snummer))
     JOIN prj_tutor pt USING (prjtg_id))
     JOIN prj_milestone pm USING (prjm_id))
     JOIN project p USING (prj_id))
     LEFT JOIN assessment_groups ag USING (snummer, prjtg_id))
     LEFT JOIN project_deliverables hd USING (prjm_id))
  ORDER BY s.snummer, p.year DESC, p.afko;


ALTER TABLE student_project_attributes OWNER TO hom;

--
-- Name: student_project_attributes; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE student_project_attributes TO peerweb;


--
-- PostgreSQL database dump complete
--

commit;
