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
-- Name: all_prj_tutor_y; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW all_prj_tutor_y AS
 SELECT prj_tutor.prjtg_id,
    prj_milestone.prj_id,
    prj_tutor.prjm_id,
    t.tutor,
    prj_tutor.grp_num,
    prj_tutor.grp_name,
    prj_tutor.prj_tutor_open,
    prj_tutor.assessment_complete,
    prj_milestone.milestone,
    prj_milestone.prj_milestone_open,
    prj_milestone.assessment_due,
    prj_milestone.weight,
    prj_milestone.milestone_name,
    prj_milestone.has_assessment,
    project.afko,
    project.description,
    project.year,
    tt.tutor AS tutor_owner,
    project.comment,
    project.valid_until,
    project.termendyear,
    project.course,
    project.owner_id,
    tt.faculty_id
   FROM ((((prj_tutor
     JOIN tutor t ON ((prj_tutor.tutor_id = t.userid)))
     JOIN prj_milestone USING (prjm_id))
     JOIN project USING (prj_id))
     JOIN tutor tt ON ((project.owner_id = tt.userid)));


ALTER TABLE all_prj_tutor_y OWNER TO hom;

--
-- Name: all_prj_tutor_y; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE all_prj_tutor_y TO peerweb;
GRANT SELECT,REFERENCES ON TABLE all_prj_tutor_y TO wwwrun;


--
-- PostgreSQL database dump complete
--

