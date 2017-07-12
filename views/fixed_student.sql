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
-- Name: fixed_student; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW fixed_student AS
 SELECT fixed_judge.prj_id,
    fixed_judge.milestone,
    fixed_judge.snummer
   FROM fixed_judge
UNION
 SELECT fixed_contestant.prj_id,
    fixed_contestant.milestone,
    fixed_contestant.snummer
   FROM fixed_contestant;


ALTER TABLE fixed_student OWNER TO hom;

--
-- Name: fixed_student; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE fixed_student TO peerweb;


--
-- PostgreSQL database dump complete
--

