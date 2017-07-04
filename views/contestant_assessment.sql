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
-- Name: contestant_assessment; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW contestant_assessment AS
 SELECT s.snummer,
    s.achternaam,
    s.tussenvoegsel,
    s.voorletters,
    s.roepnaam,
    s.straat,
    s.huisnr,
    s.pcode,
    s.plaats,
    s.email1,
    s.nationaliteit,
    s.hoofdgrp,
    s.cohort,
    pm.prj_id,
    pm.milestone,
    pm.prjm_id,
    a.contestant,
    a.judge,
    a.criterium,
    a.grade,
    pt.grp_num,
    pt.prjtg_id
   FROM ((((student s
     JOIN assessment a ON ((s.snummer = a.contestant)))
     JOIN prj_grp pg ON (((a.contestant = pg.snummer) AND (a.prjtg_id = pg.prjtg_id))))
     JOIN prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)));


ALTER TABLE contestant_assessment OWNER TO hom;

--
-- Name: contestant_assessment; Type: ACL; Schema: public; Owner: hom
--

GRANT ALL ON TABLE contestant_assessment TO peerweb;


--
-- PostgreSQL database dump complete
--

