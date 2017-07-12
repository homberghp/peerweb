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
-- Name: sv09_doubles; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW sv09_doubles AS
 SELECT sv09_27.studentnummer
   FROM sv09_27
  GROUP BY sv09_27.studentnummer
 HAVING (count(1) > 1);


ALTER TABLE sv09_doubles OWNER TO hom;

--
-- PostgreSQL database dump complete
--

