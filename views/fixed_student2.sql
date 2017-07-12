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
-- Name: fixed_student2; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW fixed_student2 AS
 SELECT DISTINCT pg.snummer,
    pm.prj_id,
    pm.milestone,
    pm.prjm_id,
    pt.prjtg_id,
    pt.grp_num
   FROM (((assessment a
     JOIN prj_grp pg ON (((a.prjtg_id = pg.prjtg_id) AND ((pg.snummer = a.judge) OR (pg.snummer = a.contestant)))))
     JOIN prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)));


ALTER TABLE fixed_student2 OWNER TO hom;

--
-- Name: fixed_student2; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE fixed_student2 TO peerweb;


--
-- PostgreSQL database dump complete
--

