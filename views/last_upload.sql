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
-- Name: last_upload; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW last_upload AS
 SELECT uploads.snummer,
    uploads.doctype,
    uploads.title,
    uploads.vers,
    uploads.uploadts,
    uploads.upload_id,
    uploads.mime_type,
    uploads.rights,
    uploads.rel_file_path,
    uploads.prjm_id,
    uploads.prjtg_id,
    uploads.mime_type_long,
    uploads.filesize
   FROM uploads
  WHERE (uploads.upload_id = ( SELECT max(uploads_1.upload_id) AS max_id
           FROM uploads uploads_1));


ALTER TABLE last_upload OWNER TO hom;

--
-- PostgreSQL database dump complete
--

