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
-- Name: judge_grp_avg; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW judge_grp_avg AS
 SELECT pm.prj_id,
    pm.milestone,
    pt.grp_num,
    av.criterium,
    av.grade
   FROM ((( SELECT assessment.prjtg_id,
            assessment.criterium,
            avg(assessment.grade) AS grade
           FROM assessment
          GROUP BY assessment.prjtg_id, assessment.criterium) av
     JOIN prj_tutor pt ON ((av.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


ALTER TABLE judge_grp_avg OWNER TO hom;

--
-- Name: judge_grp_avg; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE judge_grp_avg TO peerweb;


--
-- PostgreSQL database dump complete
--

