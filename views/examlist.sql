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
-- Name: examlist; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW examlist AS
 SELECT s.snummer,
    s.achternaam,
    s.voorletters,
    s.roepnaam,
    s.tussenvoegsel,
    s.lang,
    pm.prj_id
   FROM (((student s
     JOIN prj_grp pg USING (snummer))
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


ALTER TABLE examlist OWNER TO hom;

--
-- Name: examlist; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE examlist TO peerweb;


--
-- PostgreSQL database dump complete
--

