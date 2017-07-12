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
-- Name: minifoto; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW minifoto AS
 SELECT student.snummer,
    (((('<img src="'::text || portrait.photo) || '" alt="'::text) || (student.snummer)::text) || '" style="width:24px;height:auto"/>'::text) AS minifoto
   FROM (student
     JOIN portrait USING (snummer));


ALTER TABLE minifoto OWNER TO hom;

--
-- Name: minifoto; Type: ACL; Schema: public; Owner: hom
--

GRANT ALL ON TABLE minifoto TO peerweb;


--
-- PostgreSQL database dump complete
--

