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
-- Name: task_timer_project_sum; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW task_timer_project_sum AS
 SELECT task_timer.snummer,
    task_timer.prj_id,
    task_timer.milestone,
    sum((task_timer.stop_time - task_timer.start_time)) AS project_time
   FROM task_timer
  GROUP BY task_timer.snummer, task_timer.prj_id, task_timer.milestone;


ALTER TABLE task_timer_project_sum OWNER TO hom;

--
-- Name: task_timer_project_sum; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE task_timer_project_sum TO peerweb;


--
-- PostgreSQL database dump complete
--

