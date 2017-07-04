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
-- Name: my_peer_results_2; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW my_peer_results_2 AS
 SELECT gca.criterium,
    pm.prj_id,
    gca.prjtg_id,
    gca.crit_grade_sum,
    ((gca.crit_grade_sum)::numeric / ((gts.grp_size * (gts.grp_size - 1)))::numeric) AS grp_avg,
    cca.snummer,
    cca.contestant_crit_grade_sum,
    ((cca.contestant_crit_grade_sum)::numeric / ((gts.grp_size - 1))::numeric) AS grade,
        CASE
            WHEN ((gca.crit_grade_sum)::numeric <> (0)::numeric) THEN (((((gts.grp_size - 1) * gts.grp_size))::numeric * (cca.contestant_crit_grade_sum)::numeric) / ((gca.crit_grade_sum * (gts.grp_size - 1)))::numeric)
            ELSE (0)::numeric
        END AS multiplier,
    gts.grp_size,
    t.tutor,
    pm.milestone,
    pt.grp_num,
    pt.prjm_id,
    pt.prj_tutor_open,
    pt.assessment_complete,
    c.nl_short,
    c.de_short,
    c.nl,
    c.de,
    c.en_short,
    c.en
   FROM ((((((grp_crit_avg gca
     JOIN contestant_crit_avg cca USING (prjtg_id, criterium))
     JOIN grp_tg_size gts USING (prjtg_id))
     JOIN prj_tutor pt USING (prjtg_id))
     JOIN tutor t ON ((pt.tutor_id = t.userid)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     JOIN criteria_v c ON (((pm.prjm_id = c.prjm_id) AND (c.criterium = gca.criterium))));


ALTER TABLE my_peer_results_2 OWNER TO hom;

--
-- Name: my_peer_results_2; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE my_peer_results_2 TO peerweb;


--
-- PostgreSQL database dump complete
--

