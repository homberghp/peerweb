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
-- Name: judge_notready; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW judge_notready AS
 SELECT DISTINCT assessment.judge AS snummer,
    assessment.prjtg_id
   FROM assessment
  WHERE (assessment.grade = 0);


ALTER TABLE judge_notready OWNER TO hom;

--
-- Name: judge_notready; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE judge_notready TO peerweb;


--
-- PostgreSQL database dump complete
--

