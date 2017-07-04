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
-- Name: judge_grade_count; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW judge_grade_count AS
 SELECT pm.prj_id,
    pm.milestone,
    pt.grp_num,
    rdy.ready_judge
   FROM ((( SELECT assessment.prjtg_id,
            count(*) AS ready_judge
           FROM assessment
          WHERE (assessment.grade <> 0)
          GROUP BY assessment.prjtg_id) rdy
     JOIN prj_tutor pt ON ((rdy.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


ALTER TABLE judge_grade_count OWNER TO hom;

--
-- Name: judge_grade_count; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE judge_grade_count TO peerweb;


--
-- PostgreSQL database dump complete
--

