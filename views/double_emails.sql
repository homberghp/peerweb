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
-- Name: double_emails; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW double_emails AS
 SELECT student.email1
   FROM student
  GROUP BY student.email1
 HAVING (count(1) > 1)
  ORDER BY student.email1;


ALTER TABLE double_emails OWNER TO hom;

--
-- PostgreSQL database dump complete
--

