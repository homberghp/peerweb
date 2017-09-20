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
-- Name: web_access_by_group; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW web_access_by_group AS
 SELECT pg.snummer AS username,
    pt.prjm_id,
    (pt.prjtg_id)::text AS grp_name
   FROM (prj_grp pg
     JOIN prj_tutor pt USING (prjtg_id))
UNION
 SELECT DISTINCT pt.tutor_id AS username,
    pt.prjm_id,
    'tutor'::text AS grp_name
   FROM prj_tutor pt;


ALTER TABLE web_access_by_group OWNER TO hom;

--
-- Name: web_access_by_group; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,REFERENCES ON TABLE web_access_by_group TO peerweb;
GRANT SELECT,REFERENCES ON TABLE web_access_by_group TO wwwrun;


--
-- PostgreSQL database dump complete
--

