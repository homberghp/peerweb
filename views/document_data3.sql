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
-- Name: document_data3; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW document_data3 AS
 SELECT rtrim((p.afko)::text) AS afko,
    rtrim((p.description)::text) AS description,
    pm.prj_id,
    p.year,
    pm.milestone,
    pm.prjm_id,
    pt.grp_num,
    pt.prjtg_id,
    ga.long_name,
    u.title,
    u.rel_file_path,
    u.uploadts,
    u.filesize,
    pd.due,
    rtrim((u.mime_type_long)::text) AS mime_type,
    u.vers,
    ut.doctype,
    ut.description AS dtdescr,
    u.upload_id,
    u.snummer,
    st.roepnaam,
    st.tussenvoegsel,
    st.achternaam,
    cl.sclass,
    document_critique_count.critique_count,
    u.rights
   FROM (((((((((uploads u
     JOIN prj_milestone pm ON ((u.prjm_id = pm.prjm_id)))
     JOIN uploaddocumenttypes ut ON (((pm.prj_id = ut.prj_id) AND (u.doctype = ut.doctype))))
     JOIN project_deliverables pd ON (((u.prjm_id = pd.prjm_id) AND (u.doctype = pd.doctype))))
     JOIN prj_tutor pt ON (((pm.prjm_id = pt.prjm_id) AND (u.prjtg_id = pt.prjtg_id))))
     JOIN student st ON ((u.snummer = st.snummer)))
     JOIN student_class cl ON ((st.class_id = cl.class_id)))
     LEFT JOIN grp_alias ga ON ((pt.prjtg_id = ga.prjtg_id)))
     JOIN project p ON ((p.prj_id = pm.prj_id)))
     LEFT JOIN document_critique_count ON ((u.upload_id = document_critique_count.doc_id)));


ALTER TABLE document_data3 OWNER TO hom;

--
-- Name: VIEW document_data3; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW document_data3 IS 'all document data with prj_id and milestone removed';


--
-- Name: document_data3; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE document_data3 TO peerweb;


--
-- PostgreSQL database dump complete
--

