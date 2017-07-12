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
-- Name: course_hours; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW course_hours AS
 SELECT course_week.start_date,
    course_week.stop_date,
    course_week.course_week_no,
    timetableweek.day,
    timetableweek.hourcode,
    timetableweek.start_time,
    timetableweek.stop_time
   FROM course_week,
    timetableweek;


ALTER TABLE course_hours OWNER TO hom;

--
-- Name: course_hours; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE course_hours TO peerweb;


--
-- PostgreSQL database dump complete
--

