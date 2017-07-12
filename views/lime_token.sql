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
-- Name: lime_token; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW lime_token AS
 SELECT s.roepnaam AS firstname,
    (COALESCE(((s.tussenvoegsel)::text || ' '::text), ''::text) || (s.achternaam)::text) AS lastname,
    s.email1 AS email,
    'OK'::text AS emailstatus,
    md5(((s.snummer)::text || now())) AS token,
    s.lang AS language_code,
    s.snummer AS attribute_1,
    pm.prjm_id AS attribute_2,
    pm.prj_id,
    pm.milestone
   FROM (((prj_grp pg
     JOIN student s USING (snummer))
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjm_id)));


ALTER TABLE lime_token OWNER TO hom;

--
-- Name: lime_token; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE lime_token TO peerweb;


--
-- PostgreSQL database dump complete
--

