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
-- Name: svn_groep; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW svn_groep AS
 SELECT prj_grp.prjtg_id AS "group",
    (prj_grp.snummer)::text AS username
   FROM prj_grp
  WHERE (prj_grp.snummer = 879417)
UNION
 SELECT prj_tutor.prjtg_id AS "group",
    (prj_tutor.tutor_id)::text AS username
   FROM prj_tutor;


ALTER TABLE svn_groep OWNER TO hom;

--
-- Name: svn_groep; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,REFERENCES ON TABLE svn_groep TO wwwrun;


--
-- PostgreSQL database dump complete
--

