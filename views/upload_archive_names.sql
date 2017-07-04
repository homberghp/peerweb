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
-- Name: upload_archive_names; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW upload_archive_names AS
 SELECT btrim((apt.afko)::text) AS afko,
    apt.year,
    apt.milestone,
    apt.prjm_id,
    apt.tutor,
    apt.grp_num,
    up.rel_file_path,
    regexp_replace((udt.description)::text, '\s+'::text, '_'::text, 'g'::text) AS doc_type_desc,
    up.snummer AS author,
    up.doctype,
    ((up.snummer || '_'::text) || regexp_replace((student.achternaam)::text, '\s+'::text, '_'::text, 'g'::text)) AS author_name,
    regexp_replace(((((((((((btrim((apt.afko)::text) || '_'::text) || apt.year) || 'M'::text) || apt.milestone) || '/'::text) || (apt.tutor)::text) || '_G'::text) || apt.grp_num) || '/'::text) || (udt.description)::text), '([({}]|\s)+'::text, '_'::text, 'g'::text) AS archfilename
   FROM (((uploads up
     JOIN ( SELECT prj_grp.prjtg_id,
            t.tutor,
            pt.prjm_id,
            prj_milestone.prj_id,
            prj_milestone.milestone,
            pt.grp_num,
            project.afko,
            project.year
           FROM ((((prj_grp
             JOIN prj_tutor pt USING (prjtg_id))
             JOIN tutor t ON ((pt.tutor_id = t.userid)))
             JOIN prj_milestone USING (prjm_id))
             JOIN project USING (prj_id))) apt USING (prjtg_id))
     JOIN uploaddocumenttypes udt USING (prj_id, doctype))
     JOIN student USING (snummer));


ALTER TABLE upload_archive_names OWNER TO hom;

--
-- Name: VIEW upload_archive_names; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW upload_archive_names IS 'used to create zip archives from uploads';


--
-- Name: upload_archive_names; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE upload_archive_names TO peerweb;


--
-- PostgreSQL database dump complete
--

