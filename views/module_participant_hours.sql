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
-- Name: module_participant_hours; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW module_participant_hours AS
 SELECT module_participant.prj_id,
    module_participant.snummer,
    course_hours.start_date,
    course_hours.stop_date,
    course_hours.course_week_no,
    course_hours.day,
    course_hours.hourcode,
    course_hours.start_time,
    course_hours.stop_time
   FROM module_participant,
    course_hours;


ALTER TABLE module_participant_hours OWNER TO hom;

--
-- Name: module_participant_hours; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE module_participant_hours TO peerweb;


--
-- PostgreSQL database dump complete
--

