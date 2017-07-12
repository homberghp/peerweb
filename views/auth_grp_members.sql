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
-- Name: auth_grp_members; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW auth_grp_members AS
 SELECT (pg.snummer)::text AS username,
    (((((((p.afko)::text || '_'::text) || p.year) || '_'::text) || pm.milestone) || '_'::text) || (COALESCE(ga.alias, (('group'::text || lpad((pt.grp_num)::text, 2, '00'::text)))::bpchar))::text) AS groupname
   FROM ((((prj_grp pg
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     JOIN project p ON ((pm.prj_id = p.prj_id)))
     LEFT JOIN grp_alias ga ON ((pt.prjtg_id = ga.prjtg_id)));


ALTER TABLE auth_grp_members OWNER TO hom;

--
-- Name: auth_grp_members; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE auth_grp_members TO peerweb;


--
-- PostgreSQL database dump complete
--

