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
-- Name: act_presence_list2; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW act_presence_list2 AS
 SELECT act.act_id,
    act.datum,
    act.short,
    act.description,
    act.act_type,
    act.part,
    act.start_time,
    act.prjm_id,
    cand.snummer,
    COALESCE(ga.alias, (('g'::text || pt.grp_num))::bpchar) AS agroup,
    ap.presence AS present,
    ar.reason AS note
   FROM ((((((prj_grp cand
     JOIN prj_tutor pt ON ((cand.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     LEFT JOIN grp_alias ga ON ((ga.prjtg_id = pt.prjtg_id)))
     JOIN activity act ON ((pm.prjm_id = act.prjm_id)))
     LEFT JOIN activity_participant ap ON (((cand.snummer = ap.snummer) AND (act.act_id = ap.act_id))))
     LEFT JOIN absence_reason ar ON (((ap.snummer = ar.snummer) AND (ar.act_id = ap.act_id))));


ALTER TABLE act_presence_list2 OWNER TO hom;

--
-- Name: VIEW act_presence_list2; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW act_presence_list2 IS 'creates activity presence list';


--
-- Name: act_presence_list2; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE act_presence_list2 TO peerweb;


--
-- PostgreSQL database dump complete
--

