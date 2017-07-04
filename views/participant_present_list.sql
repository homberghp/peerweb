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
-- Name: participant_present_list; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW participant_present_list AS
 SELECT mh.snummer,
    mh.prj_id,
    mh.course_week_no,
    mh.day,
    mh.hourcode,
    lt.from_ip,
    lt.since
   FROM (module_participant_hours mh
     LEFT JOIN logon_map_on_timetable lt ON ((((mh.course_week_no = lt.course_week_no) AND (mh.day = lt.day) AND (mh.hourcode = lt.hourcode) AND (mh.snummer = lt.snummer)) OR (lt.snummer IS NULL))));


ALTER TABLE participant_present_list OWNER TO hom;

--
-- Name: participant_present_list; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE participant_present_list TO peerweb;


--
-- PostgreSQL database dump complete
--

