begin work;
--
-- PostgreSQL database dump
--

-- Dumped from database version 10.1
-- Dumped by pg_dump version 10.1

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
-- Name: all_prj_tutor; Type: VIEW; Schema: public; Owner: hom
--
drop view if exists all_prj_tutor;
CREATE or replace VIEW all_prj_tutor AS
 SELECT prj_tutor.prjtg_id,
    prj_milestone.prj_id,
    prj_milestone.prjm_id,
    t.tutor,
    prj_tutor.tutor_id,
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
    trim(project.afko) as afko,
    trim(project.description) as description,
    project.year,
    tt.tutor AS tutor_owner,
    project.comment,
    project.valid_until,
    project.termendyear,
    project.course,
    project.owner_id,
    grp_alias.long_name,
    grp_alias.alias,
    grp_alias.website,
    grp_alias.productname,
    prj_tutor.tutor_grade
   FROM (((((prj_tutor
     JOIN prj_milestone USING (prjm_id))
     JOIN project USING (prj_id))
     JOIN tutor t ON ((t.userid = prj_tutor.tutor_id)))
     JOIN tutor tt ON ((tt.userid = project.owner_id)))
     LEFT JOIN grp_alias USING (prjtg_id));


ALTER TABLE all_prj_tutor OWNER TO hom;

--
-- Name: VIEW all_prj_tutor; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW all_prj_tutor IS 'all from prj_tutor (by prjtg_id) up to project';


--
-- Name: all_prj_tutor; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE all_prj_tutor TO peerweb;
GRANT SELECT,REFERENCES ON TABLE all_prj_tutor TO wwwrun;


--
-- PostgreSQL database dump complete
--

commit;
