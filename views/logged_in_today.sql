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
-- Name: logged_in_today; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW logged_in_today AS
 SELECT logon.userid,
    logon.since,
    logon.id,
    logon.from_ip
   FROM logon
  WHERE (logon.since > (now())::date);


ALTER TABLE logged_in_today OWNER TO hom;

--
-- PostgreSQL database dump complete
--

