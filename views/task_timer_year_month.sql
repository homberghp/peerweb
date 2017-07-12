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
-- Name: task_timer_year_month; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW task_timer_year_month AS
 SELECT DISTINCT date_part('year'::text, task_timer.start_time) AS year,
    date_part('month'::text, task_timer.start_time) AS month,
    to_char(task_timer.start_time, 'IYYY Mon'::text) AS year_month,
    date_trunc('month'::text, task_timer.start_time) AS first_second,
    (date_trunc('month'::text, (task_timer.start_time + '31 days'::interval)) - '00:00:01'::interval) AS last_second
   FROM task_timer
  ORDER BY (date_part('year'::text, task_timer.start_time)), (date_part('month'::text, task_timer.start_time)), (to_char(task_timer.start_time, 'IYYY Mon'::text)), (date_trunc('month'::text, task_timer.start_time)), (date_trunc('month'::text, (task_timer.start_time + '31 days'::interval)) - '00:00:01'::interval);


ALTER TABLE task_timer_year_month OWNER TO hom;

--
-- Name: task_timer_year_month; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE task_timer_year_month TO peerweb;


--
-- PostgreSQL database dump complete
--

