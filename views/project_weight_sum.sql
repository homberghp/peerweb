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
-- Name: project_weight_sum; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW project_weight_sum AS
 SELECT prj_milestone.prj_id,
    sum(prj_milestone.weight) AS weight_sum
   FROM prj_milestone
  GROUP BY prj_milestone.prj_id;


ALTER TABLE project_weight_sum OWNER TO hom;

--
-- Name: project_weight_sum; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE project_weight_sum TO peerweb;


--
-- PostgreSQL database dump complete
--

