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
begin work;
--
-- Name: all_project_milestone; Type: VIEW; Schema: public; Owner: hom
--
drop view if exists all_project_milestone;
CREATE VIEW all_project_milestone AS
 SELECT project.prj_id,
    project.afko as project,
    project.description as project_description,
    project.year project_year,
    project.comment as project_comment,
    project.valid_until,
    project.termendyear,
    project.course,
    project.owner_id,
    prj_milestone.milestone,
    prj_milestone.prj_milestone_open,
    prj_milestone.assessment_due,
    prj_milestone.prjm_id,
    prj_milestone.weight,
    prj_milestone.milestone_name,
    prj_milestone.has_assessment
   FROM (project
     JOIN prj_milestone USING (prj_id));


ALTER TABLE all_project_milestone OWNER TO hom;

--
-- Name: all_project_milestone; Type: ACL; Schema: public; Owner: hom
--

GRANT ALL ON TABLE all_project_milestone TO PUBLIC;


--
-- PostgreSQL database dump complete
--

commit;
