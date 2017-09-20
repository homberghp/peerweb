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
-- Name: judge_assessment; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW judge_assessment AS
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
    prj_milestone.prj_id,
    prj_milestone.milestone,
    prj_tutor.prjm_id,
    a.contestant,
    a.judge,
    a.criterium,
    a.grade,
    prj_tutor.grp_num,
    a.prjtg_id
   FROM (((student s
     JOIN assessment a ON ((s.snummer = a.judge)))
     JOIN prj_tutor USING (prjtg_id))
     JOIN prj_milestone USING (prjm_id));


ALTER TABLE judge_assessment OWNER TO hom;

--
-- Name: judge_assessment; Type: ACL; Schema: public; Owner: hom
--

GRANT ALL ON TABLE judge_assessment TO peerweb;


--
-- PostgreSQL database dump complete
--

