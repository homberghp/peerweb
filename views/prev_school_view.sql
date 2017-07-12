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
-- Name: prev_school_view; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW prev_school_view AS
 SELECT schulen.schulen_id AS school_id,
    schulen.schultyp AS school_type,
    schulen.naam_school AS school_name,
    schulen.woonplaats AS plaats
   FROM schulen
UNION
 SELECT scholen_int.scholen_int_id AS school_id,
    scholen_int.school_type,
    scholen_int.naam_volledig AS school_name,
    scholen_int.naam_plaats_vest AS plaats
   FROM scholen_int
  ORDER BY 4, 3;


ALTER TABLE prev_school_view OWNER TO hom;

--
-- PostgreSQL database dump complete
--

