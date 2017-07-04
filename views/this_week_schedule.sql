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
-- Name: this_week_schedule; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW this_week_schedule AS
 SELECT s.day,
    s.hourcode,
    s.start_time,
    s.stop_time,
    ((date_trunc('week'::text, ((now())::date)::timestamp with time zone))::date + (s.day - 1)) AS datum,
    (((date_trunc('week'::text, ((now())::date)::timestamp with time zone))::date + (s.day - 1)) + s.start_time) AS start_ts,
    (((now())::date + (s.day - 1)) + s.stop_time) AS stop_ts
   FROM schedule_hours s;


ALTER TABLE this_week_schedule OWNER TO hom;

--
-- PostgreSQL database dump complete
--

