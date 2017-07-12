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
-- Name: present_in_courseweek; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW present_in_courseweek AS
 SELECT course_week.start_date,
    course_week.stop_date,
    course_week.course_week_no,
    present_anywhere.userid,
    present_anywhere.since,
    present_anywhere.id,
    present_anywhere.from_ip,
    present_anywhere.day,
    present_anywhere.hourcode,
    present_anywhere.start_time,
    present_anywhere.stop_time,
    present_anywhere.dayname,
    present_anywhere.day_lang
   FROM (course_week
     LEFT JOIN present_anywhere ON (((present_anywhere.since >= (course_week.start_date)::timestamp without time zone) AND (present_anywhere.since <= (course_week.stop_date)::timestamp without time zone))));


ALTER TABLE present_in_courseweek OWNER TO hom;

--
-- Name: present_in_courseweek; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE present_in_courseweek TO peerweb;


--
-- PostgreSQL database dump complete
--

