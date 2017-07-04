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
-- Name: ready_judge_count; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW ready_judge_count AS
 SELECT prj_grp.prjtg_id,
    count(*) AS count
   FROM prj_grp
  WHERE (prj_grp.written = true)
  GROUP BY prj_grp.prjtg_id;


ALTER TABLE ready_judge_count OWNER TO hom;

--
-- Name: ready_judge_count; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE ready_judge_count TO peerweb;


--
-- PostgreSQL database dump complete
--

