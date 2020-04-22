--
-- PostgreSQL database dump
--

-- Dumped from database version 12.2 (Ubuntu 12.2-2.pgdg19.10+1)
-- Dumped by pg_dump version 12.2 (Ubuntu 12.2-2.pgdg19.10+1)
begin work;
SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: act_presence_list2; Type: VIEW; Schema: public; Owner: rpadmin
--

drop view if exists public.act_presence_list2;
CREATE VIEW public.act_presence_list2 AS
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
    grp_num,
    ap.presence AS present,
    ar.reason AS note
   FROM ((((((public.prj_grp cand
     JOIN public.prj_tutor pt ON ((cand.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     LEFT JOIN public.grp_alias ga ON ((ga.prjtg_id = pt.prjtg_id)))
     JOIN public.activity act ON ((pm.prjm_id = act.prjm_id)))
     LEFT JOIN public.activity_participant ap ON (((cand.snummer = ap.snummer) AND (act.act_id = ap.act_id))))
     LEFT JOIN public.absence_reason ar ON (((ap.snummer = ar.snummer) AND (ar.act_id = ap.act_id))));


ALTER TABLE public.act_presence_list2 OWNER TO rpadmin;

--
-- Name: VIEW act_presence_list2; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.act_presence_list2 IS 'creates activity presence list';


--
-- Name: TABLE act_presence_list2; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.act_presence_list2 TO peerweb;


--
-- PostgreSQL database dump complete
--

commit;
