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
-- Name: fixed_contestant; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW fixed_contestant AS
 SELECT contestant_sum.prj_id,
    contestant_sum.milestone,
    contestant_sum.snummer
   FROM contestant_sum
  WHERE (contestant_sum.grade_sum > 0);


ALTER TABLE fixed_contestant OWNER TO hom;

--
-- Name: fixed_contestant; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE fixed_contestant TO peerweb;


--
-- PostgreSQL database dump complete
--

