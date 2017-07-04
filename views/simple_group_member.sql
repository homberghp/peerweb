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
-- Name: simple_group_member; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW simple_group_member AS
 SELECT prj_grp.prjtg_id,
    prj_grp.snummer
   FROM prj_grp
UNION
 SELECT prj_tutor.prjtg_id,
    prj_tutor.tutor_id AS snummer
   FROM prj_tutor;


ALTER TABLE simple_group_member OWNER TO hom;

--
-- Name: simple_group_member; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,REFERENCES ON TABLE simple_group_member TO wwwrun;


--
-- PostgreSQL database dump complete
--

