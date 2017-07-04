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
-- Name: last_assessment_commit; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW last_assessment_commit AS
 SELECT ac.snummer,
    pm.prj_id,
    pm.milestone,
    pt.prjtg_id,
    max(ac.commit_time) AS commit_time
   FROM ((assessment_commit ac
     JOIN prj_tutor pt ON ((pt.prjtg_id = ac.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjm_id)))
  GROUP BY ac.snummer, pm.prj_id, pm.milestone, pt.prjtg_id;


ALTER TABLE last_assessment_commit OWNER TO hom;

--
-- Name: VIEW last_assessment_commit; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW last_assessment_commit IS 'used by ipeer.php tutor/groupresult.php tutor/moduleresults.php';


--
-- Name: last_assessment_commit; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE last_assessment_commit TO peerweb;


--
-- PostgreSQL database dump complete
--

