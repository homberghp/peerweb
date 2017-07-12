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
-- Name: viewabledocument; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW viewabledocument AS
 SELECT pga.snummer AS author,
    pgtv.snummer AS viewer,
    pta.grp_num AS author_grp,
    pgtv.grp_num AS viewer_grp,
    pta.prjtg_id,
    pgtv.prjtg_id AS viewer_prjtg_id,
    pma.prj_id,
    pma.milestone,
    up.upload_id AS doc_id,
    up.uploadts,
    up.title,
    ut.doctype,
    pd.due
   FROM ((((((uploads up
     JOIN prj_grp pga ON (((up.prjtg_id = pga.prjtg_id) AND (up.snummer = pga.snummer))))
     JOIN prj_tutor pta ON ((pta.prjtg_id = pga.prjtg_id)))
     JOIN prj_milestone pma ON ((pta.prjm_id = pma.prjm_id)))
     JOIN uploaddocumenttypes ut ON (((pma.prj_id = ut.prj_id) AND (ut.doctype = up.doctype))))
     JOIN ( SELECT ptv.prjtg_id,
            pgv.snummer,
            ptv.prjm_id,
            ptv.grp_num
           FROM (prj_grp pgv
             JOIN prj_tutor ptv ON ((pgv.prjtg_id = ptv.prjtg_id)))) pgtv ON ((pgtv.prjm_id = pta.prjm_id)))
     JOIN project_deliverables pd ON (((pd.prjm_id = pta.prjm_id) AND (up.doctype = pd.doctype))))
  WHERE ((pta.prjtg_id = pgtv.prjtg_id) OR ((pd.due)::timestamp with time zone < now()));


ALTER TABLE viewabledocument OWNER TO hom;

--
-- Name: VIEW viewabledocument; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW viewabledocument IS 'group and project members that might view an upload document';


--
-- Name: viewabledocument; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE viewabledocument TO peerweb;


--
-- PostgreSQL database dump complete
--

