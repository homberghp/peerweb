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
-- Name: slb_projects; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW slb_projects AS
 SELECT project.prj_id
   FROM project
  WHERE (project.afko = 'SLB'::bpchar);


ALTER TABLE slb_projects OWNER TO hom;

--
-- Name: slb_projects; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE slb_projects TO peerweb;


--
-- PostgreSQL database dump complete
--

