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
-- Name: grp_crit_avg; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW grp_crit_avg AS
 SELECT assessment.prjtg_id,
    assessment.criterium,
    sum(assessment.grade) AS crit_grade_sum
   FROM assessment
  GROUP BY assessment.prjtg_id, assessment.criterium;


ALTER TABLE grp_crit_avg OWNER TO hom;

--
-- Name: grp_crit_avg; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE grp_crit_avg TO peerweb;


--
-- PostgreSQL database dump complete
--

