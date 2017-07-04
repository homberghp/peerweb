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
-- Name: stdresult_overall; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW stdresult_overall AS
 SELECT assessment.prjtg_id,
    assessment.contestant AS snummer,
    avg(assessment.grade) AS grade
   FROM assessment
  GROUP BY assessment.prjtg_id, assessment.contestant;


ALTER TABLE stdresult_overall OWNER TO hom;

--
-- Name: VIEW stdresult_overall; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW stdresult_overall IS 'used by ~/_include/test/peerutils,tutor/groupresult.php';


--
-- Name: stdresult_overall; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE stdresult_overall TO peerweb;


--
-- PostgreSQL database dump complete
--

