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
-- Name: loggedin; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW loggedin AS
 SELECT s.achternaam,
    s.roepnaam,
    l.userid,
    l.since,
    l.id,
    l.from_ip
   FROM (logged_in_today l
     JOIN student s ON ((s.snummer = l.userid)));


ALTER TABLE loggedin OWNER TO hom;

--
-- PostgreSQL database dump complete
--

