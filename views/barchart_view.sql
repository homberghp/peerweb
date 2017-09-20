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
-- Name: barchart_view; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW barchart_view AS
 SELECT COALESCE(jrc.size, (0)::bigint) AS size,
    pt.prjtg_id,
    pm.prjm_id,
    pm.prj_id,
    pm.milestone,
    COALESCE(ga.alias, (('g'::text || pt.grp_num))::bpchar) AS alias,
    pt.grp_num,
    ((((ts.roepnaam)::text || ' '::text) || COALESCE(((ts.tussenvoegsel)::text || ' '::text), ''::text)) || (ts.achternaam)::text) AS tut_name,
    t.tutor,
    jrc.ready_count,
    pm.prj_milestone_open,
    pt.prj_tutor_open
   FROM (((((prj_milestone pm
     JOIN prj_tutor pt ON ((pm.prjm_id = pt.prjm_id)))
     JOIN tutor t ON ((pt.tutor_id = t.userid)))
     JOIN student ts ON ((t.userid = ts.snummer)))
     LEFT JOIN judge_ready_count jrc ON ((pt.prjtg_id = jrc.prjtg_id)))
     LEFT JOIN grp_alias ga ON ((ga.prjtg_id = pt.prjtg_id)));


ALTER TABLE barchart_view OWNER TO hom;

--
-- Name: VIEW barchart_view; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW barchart_view IS 'used in openBarChart2.php';


--
-- Name: barchart_view; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE barchart_view TO peerweb;


--
-- PostgreSQL database dump complete
--

