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
-- Name: assessment_zero_count; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW assessment_zero_count AS
 SELECT pm.prj_id,
    pm.milestone,
    pt.grp_num,
    gz.gcount AS count
   FROM ((( SELECT count(assessment.grade) AS gcount,
            assessment.prjtg_id
           FROM assessment
          WHERE (assessment.grade = 0)
          GROUP BY assessment.prjtg_id) gz
     JOIN prj_tutor pt ON ((gz.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


ALTER TABLE assessment_zero_count OWNER TO hom;

--
-- Name: VIEW assessment_zero_count; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW assessment_zero_count IS 'count zeros per group';


--
-- Name: assessment_zero_count; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE assessment_zero_count TO peerweb;


--
-- PostgreSQL database dump complete
--

