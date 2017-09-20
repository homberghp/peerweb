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
-- Name: project_task_completed_max_trans; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW project_task_completed_max_trans AS
 SELECT max(project_task_completed.trans_id) AS trans_id,
    project_task_completed.task_id,
    project_task_completed.snummer
   FROM project_task_completed
  GROUP BY project_task_completed.task_id, project_task_completed.snummer;


ALTER TABLE project_task_completed_max_trans OWNER TO hom;

--
-- Name: project_task_completed_max_trans; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE project_task_completed_max_trans TO peerweb;


--
-- PostgreSQL database dump complete
--

