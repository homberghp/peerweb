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
-- Name: present_in_coursehours; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW present_in_coursehours AS
 SELECT ch.start_date,
    ch.stop_date,
    ch.course_week_no,
    ch.day,
    ch.hourcode,
    ch.start_time,
    ch.stop_time,
    lo.userid,
    lo.since,
    lo.id,
    lo.from_ip
   FROM (course_hours ch
     LEFT JOIN logon lo ON ((((lo.since)::text >= (ch.start_time)::text) AND ((lo.since)::text <= (ch.stop_time)::text))));


ALTER TABLE present_in_coursehours OWNER TO hom;

--
-- Name: present_in_coursehours; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE present_in_coursehours TO peerweb;


--
-- PostgreSQL database dump complete
--

