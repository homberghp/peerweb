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
-- Name: contestant_sum; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW contestant_sum AS
 SELECT pm.prj_id,
    pm.milestone,
    a.snummer,
    a.grade_sum
   FROM ((( SELECT assessment.contestant AS snummer,
            assessment.prjtg_id,
            sum(assessment.grade) AS grade_sum
           FROM assessment
          GROUP BY assessment.contestant, assessment.prjtg_id) a
     JOIN prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


ALTER TABLE contestant_sum OWNER TO hom;

--
-- Name: contestant_sum; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE contestant_sum TO peerweb;


--
-- PostgreSQL database dump complete
--

