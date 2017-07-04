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
-- Name: dead_class; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW dead_class AS
 SELECT DISTINCT s1.class_id
   FROM student s1
  WHERE (NOT (EXISTS ( SELECT 1
           FROM student
          WHERE ((student.class_id = s1.class_id) AND (student.active = true)))));


ALTER TABLE dead_class OWNER TO hom;

--
-- PostgreSQL database dump complete
--

