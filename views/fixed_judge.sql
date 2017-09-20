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
-- Name: fixed_judge; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW fixed_judge AS
 SELECT judge_sum.prj_id,
    judge_sum.milestone,
    judge_sum.snummer
   FROM judge_sum
  WHERE (judge_sum.grade_sum > 0);


ALTER TABLE fixed_judge OWNER TO hom;

--
-- Name: fixed_judge; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE fixed_judge TO peerweb;


--
-- PostgreSQL database dump complete
--

