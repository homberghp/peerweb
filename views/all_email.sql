--
-- PostgreSQL database dump
--

-- Dumped from database version 10.0
-- Dumped by pg_dump version 10.0

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
-- Name: all_email; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW all_email AS
 SELECT student.snummer,
    student.achternaam,
    student.roepnaam,
    student.email1,
    alt_email.email2,
    alt_email.email3,
    student.class_id
   FROM (student
     JOIN alt_email USING (snummer));


ALTER TABLE all_email OWNER TO hom;

--
-- PostgreSQL database dump complete
--

