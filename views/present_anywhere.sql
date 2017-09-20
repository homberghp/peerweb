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
-- Name: present_anywhere; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW present_anywhere AS
 SELECT logon.userid,
    logon.since,
    logon.id,
    logon.from_ip,
    timetableweek.day,
    timetableweek.hourcode,
    timetableweek.start_time,
    timetableweek.stop_time,
    weekdays.dayname,
    weekdays.day_lang
   FROM logon,
    (timetableweek
     JOIN weekdays USING (day))
  WHERE ((to_char(logon.since, 'HH24:MI:SS'::text) >= (timetableweek.start_time)::text) AND (to_char(logon.since, 'HH24:MI:SS'::text) <= (timetableweek.stop_time)::text) AND (date_part('dow'::text, logon.since) = (timetableweek.day)::double precision));


ALTER TABLE present_anywhere OWNER TO hom;

--
-- Name: present_anywhere; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE present_anywhere TO peerweb;


--
-- PostgreSQL database dump complete
--

