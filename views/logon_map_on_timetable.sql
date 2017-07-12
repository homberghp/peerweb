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
-- Name: logon_map_on_timetable; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW logon_map_on_timetable AS
 SELECT logon.userid AS snummer,
    logon.since,
    logon.id,
    logon.from_ip,
    timetableweek.day,
    timetableweek.hourcode,
    timetableweek.start_time,
    timetableweek.stop_time,
    course_week.start_date,
    course_week.stop_date,
    course_week.course_week_no
   FROM ((logon
     JOIN timetableweek ON ((((substr(to_char(logon.since, 'HH24:MI:SS'::text), 1, 8))::time without time zone >= timetableweek.start_time) AND ((substr(to_char(logon.since, 'HH24:MI:SS'::text), 1, 8))::time without time zone <= timetableweek.stop_time) AND (date_part('dow'::text, logon.since) = (timetableweek.day)::double precision))))
     JOIN course_week ON (((logon.since >= (course_week.start_date)::timestamp without time zone) AND (logon.since <= (course_week.stop_date)::timestamp without time zone))));


ALTER TABLE logon_map_on_timetable OWNER TO hom;

--
-- Name: logon_map_on_timetable; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE logon_map_on_timetable TO peerweb;


--
-- PostgreSQL database dump complete
--

