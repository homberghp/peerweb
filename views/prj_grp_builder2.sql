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
-- Name: prj_grp_builder2; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW prj_grp_builder2 AS
 SELECT pmt.prj_id,
    pgo.snummer,
    pmt.milestone,
    false AS prj_grp_open,
    ptt.grp_num,
    false AS written,
    pmt.prjm_id,
    ptt.prjtg_id,
    pto.prjm_id AS orig_prjm_id
   FROM ((((prj_grp pgo
     JOIN prj_tutor pto ON ((pgo.prjtg_id = pto.prjtg_id)))
     JOIN prj_milestone pmo ON ((pto.prjm_id = pmo.prjm_id)))
     JOIN prj_tutor ptt ON ((pto.grp_num = ptt.grp_num)))
     JOIN prj_milestone pmt ON ((ptt.prjm_id = pmt.prjm_id)));


ALTER TABLE prj_grp_builder2 OWNER TO hom;

--
-- Name: VIEW prj_grp_builder2; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW prj_grp_builder2 IS 'used in copying project groups';


--
-- Name: prj_grp_builder2; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE prj_grp_builder2 TO peerweb;


--
-- PostgreSQL database dump complete
--

