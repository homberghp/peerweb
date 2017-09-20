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
-- Name: assessment_group_notready; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW assessment_group_notready AS
 SELECT DISTINCT assessment.prjtg_id
   FROM assessment
  WHERE (assessment.grade = 0)
  GROUP BY assessment.prjtg_id;


ALTER TABLE assessment_group_notready OWNER TO hom;

--
-- Name: VIEW assessment_group_notready; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW assessment_group_notready IS 'lists prjtg_id where exists grade =0 (not graded)';


--
-- Name: assessment_group_notready; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE assessment_group_notready TO peerweb;


--
-- PostgreSQL database dump complete
--

