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
-- Name: svn_group; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW svn_group AS
 SELECT pt.grp_name AS groupname,
    pg.snummer AS username,
    pm.prj_id,
    pm.milestone,
    s.achternaam,
    s.roepnaam,
    pt.prjm_id,
    pt.prjtg_id
   FROM ((((prj_grp pg
     JOIN student s USING (snummer))
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     LEFT JOIN grp_alias ga ON ((pt.prjtg_id = ga.prjtg_id)));


ALTER TABLE svn_group OWNER TO hom;

--
-- Name: svn_group; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE svn_group TO peerweb;
GRANT ALL ON TABLE svn_group TO wwwrun;


--
-- PostgreSQL database dump complete
--

