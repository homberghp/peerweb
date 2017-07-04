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
-- Name: task_timer_week; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW task_timer_week AS
 SELECT DISTINCT task_timer.snummer,
    date_part('year'::text, task_timer.start_time) AS year,
    date_part('week'::text, task_timer.start_time) AS week
   FROM task_timer
  ORDER BY task_timer.snummer, (date_part('year'::text, task_timer.start_time)), (date_part('week'::text, task_timer.start_time));


ALTER TABLE task_timer_week OWNER TO hom;

--
-- Name: task_timer_week; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE task_timer_week TO peerweb;


--
-- PostgreSQL database dump complete
--

