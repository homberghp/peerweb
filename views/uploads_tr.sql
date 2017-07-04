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
-- Name: uploads_tr; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW uploads_tr AS
 SELECT uploads.prjm_id,
    uploads.snummer,
    uploads.doctype,
    uploads.title,
    uploads.vers,
    uploads.uploadts,
    uploads.upload_id,
    uploads.mime_type,
    uploads.rights,
    uploads.rel_file_path
   FROM uploads;


ALTER TABLE uploads_tr OWNER TO hom;

--
-- PostgreSQL database dump complete
--

