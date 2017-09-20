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
-- Name: task_timer_at_fontys; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW task_timer_at_fontys AS
 SELECT task_timer_anywhere.userid,
    task_timer_anywhere.prj_id,
    task_timer_anywhere.milestone,
    task_timer_anywhere.task_id,
    task_timer_anywhere.time_tag,
    task_timer_anywhere.start_time,
    task_timer_anywhere.stop_time,
    task_timer_anywhere.from_ip,
    task_timer_anywhere.hourcode,
    task_timer_anywhere.day,
    task_timer_anywhere.dayname,
    task_timer_anywhere.day_lang
   FROM task_timer_anywhere
  WHERE (task_timer_anywhere.from_ip <<= '145.85.0.0/16'::inet);


ALTER TABLE task_timer_at_fontys OWNER TO hom;

--
-- Name: task_timer_at_fontys; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE task_timer_at_fontys TO peerweb;


--
-- PostgreSQL database dump complete
--

