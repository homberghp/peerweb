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
-- Name: project_grp_stakeholders; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW project_grp_stakeholders AS
 SELECT pg.snummer,
    pg.prjtg_id
   FROM prj_grp pg
UNION
 SELECT prj_tutor.tutor_id AS snummer,
    prj_tutor.prjtg_id
   FROM prj_tutor;


ALTER TABLE project_grp_stakeholders OWNER TO hom;

--
-- Name: project_grp_stakeholders; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE project_grp_stakeholders TO peerweb;


--
-- PostgreSQL database dump complete
--

