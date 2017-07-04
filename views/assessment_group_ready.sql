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
-- Name: assessment_group_ready; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW assessment_group_ready AS
 SELECT pm.prj_id,
    pm.milestone,
    pt.grp_num
   FROM ((( SELECT DISTINCT assessment.prjtg_id
           FROM assessment
          WHERE (NOT (assessment.prjtg_id IN ( SELECT DISTINCT assessment.prjtg_id
                  WHERE (assessment.grade = 0)
                  ORDER BY assessment.prjtg_id)))
          GROUP BY assessment.prjtg_id
          ORDER BY assessment.prjtg_id) rdy
     JOIN prj_tutor pt ON ((rdy.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


ALTER TABLE assessment_group_ready OWNER TO hom;

--
-- Name: VIEW assessment_group_ready; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW assessment_group_ready IS 'select groups that are ready';


--
-- Name: assessment_group_ready; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE assessment_group_ready TO peerweb;


--
-- PostgreSQL database dump complete
--

