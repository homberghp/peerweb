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
-- Name: document_audience; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW document_audience AS
 SELECT uploads.upload_id,
    uploads.rights,
    uploads.snummer AS reader,
    uploads.prjm_id,
    uploads.prjtg_id AS viewergrp,
    '0 author'::text AS reader_role
   FROM uploads
UNION
 SELECT uploads.upload_id,
    uploads.rights,
    prj_tutor.tutor_id AS reader,
    uploads.prjm_id,
    uploads.prjtg_id AS viewergrp,
    '1 project tutor'::text AS reader_role
   FROM (uploads
     JOIN prj_tutor USING (prjm_id))
UNION
 SELECT uploads.upload_id,
    uploads.rights,
    prj_grp.snummer AS reader,
    uploads.prjm_id,
    uploads.prjtg_id AS viewergrp,
    '2 group member'::text AS reader_role
   FROM (uploads
     JOIN prj_grp USING (prjtg_id))
  WHERE ((uploads.rights[1] = true) AND (prj_grp.snummer <> uploads.snummer))
UNION
 SELECT u.upload_id,
    u.rights,
    pg.snummer AS reader,
    u.prjm_id,
    pg.prjtg_id AS viewergrp,
    '3 project member'::text AS reader_role
   FROM ((uploads u
     JOIN prj_tutor pt ON ((u.prjm_id = pt.prjm_id)))
     JOIN prj_grp pg ON ((pt.prjtg_id = pg.prjtg_id)))
  WHERE ((u.rights[2] = true) AND (u.prjtg_id <> pg.prjtg_id));


ALTER TABLE document_audience OWNER TO hom;

--
-- Name: document_audience; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE document_audience TO peerweb;


--
-- PostgreSQL database dump complete
--

