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
-- Name: trac_init_data; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW trac_init_data AS
 SELECT apt.prj_id,
    apt.year,
    apt.milestone,
    apt.prjm_id,
    btrim((COALESCE(apt.alias, (('g'::text || apt.grp_num))::bpchar))::text) AS alias,
    apt.grp_num,
    (((((apt.afko)::text || '_'::text) || apt.year) || '_'::text) || (COALESCE(apt.alias, (('g'::text || apt.grp_num))::bpchar))::text) AS project_name,
    ((((((('trac_'::text || apt.year) || '_'::text) || (apt.afko)::text) || '_m'::text) || apt.milestone) || '_'::text) || replace((COALESCE(apt.alias, (('g'::text || apt.grp_num))::bpchar))::text, '-'::text, '_'::text)) AS dbname,
    r.repospath,
    regexp_replace((r.repospath)::text, '^/home/svn/'::text, '/home/trac/'::text) AS trac_path
   FROM (all_prj_tutor apt
     JOIN repositories r USING (prjtg_id));


ALTER TABLE trac_init_data OWNER TO hom;

--
-- Name: trac_init_data; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE trac_init_data TO peerweb;
GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE trac_init_data TO wwwrun;


--
-- PostgreSQL database dump complete
--

