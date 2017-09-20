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
-- Name: assessment_groups; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW assessment_groups AS
 SELECT DISTINCT a.judge AS snummer,
    a.prjtg_id
   FROM assessment a;


ALTER TABLE assessment_groups OWNER TO hom;

--
-- Name: VIEW assessment_groups; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW assessment_groups IS 'needed by student_project_attributes';


--
-- Name: assessment_groups; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE assessment_groups TO peerweb;


--
-- PostgreSQL database dump complete
--

