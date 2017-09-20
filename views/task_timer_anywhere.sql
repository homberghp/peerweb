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
-- Name: task_timer_anywhere; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW task_timer_anywhere AS
 SELECT t.snummer AS userid,
    t.prj_id,
    t.milestone,
    t.task_id,
    t.time_tag,
    w.start_time,
    w.stop_time,
    t.from_ip,
    w.hourcode,
    w.day,
    weekdays.dayname,
    weekdays.day_lang
   FROM task_timer t,
    (timetableweek w
     JOIN weekdays USING (day))
  WHERE ((to_char(t.time_tag, 'HH24:MI:SS'::text) >= (w.start_time)::text) AND (to_char(t.time_tag, 'HH24:MI:SS'::text) <= (w.stop_time)::text) AND (date_part('dow'::text, t.time_tag) = (w.day)::double precision));


ALTER TABLE task_timer_anywhere OWNER TO hom;

--
-- Name: task_timer_anywhere; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE task_timer_anywhere TO peerweb;


--
-- PostgreSQL database dump complete
--

