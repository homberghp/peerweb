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
-- Name: exam_account; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW exam_account AS
 SELECT ('x'::text || (prj_grp.snummer)::text) AS uname,
    student.roepnaam AS password,
    1 AS uid,
    (pm.prj_id + 10000) AS gid,
    (((student.roepnaam)::text || ' '::text) || (student.achternaam)::text) AS gecos,
    ('/exam/x'::text || (prj_grp.snummer)::text) AS homedir,
    '/bin/bash'::text AS shell,
    pm.prj_id,
    pm.milestone
   FROM (((prj_grp
     JOIN student USING (snummer))
     JOIN prj_tutor pt ON ((prj_grp.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)));


ALTER TABLE exam_account OWNER TO hom;

--
-- Name: exam_account; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE exam_account TO peerweb;


--
-- PostgreSQL database dump complete
--

