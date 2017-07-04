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
-- Name: svn_tutor; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW svn_tutor AS
 SELECT DISTINCT t.userid AS username,
    t.tutor,
    pm.prj_id,
    pm.milestone,
    pm.prjm_id
   FROM ((prj_tutor pt
     JOIN tutor t ON ((pt.tutor_id = t.userid)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  ORDER BY t.userid, t.tutor, pm.prj_id, pm.milestone;


ALTER TABLE svn_tutor OWNER TO hom;

--
-- Name: svn_tutor; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,REFERENCES ON TABLE svn_tutor TO wwwrun;


--
-- PostgreSQL database dump complete
--

