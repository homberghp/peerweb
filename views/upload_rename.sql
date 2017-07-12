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
-- Name: upload_rename; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW upload_rename AS
 SELECT uploads.upload_id,
    uploads.rel_file_path,
    regexp_replace(regexp_replace(uploads.rel_file_path, '\s+'::text, '_'::text, 'g'::text), '\.{2}'::text, '.'::text, 'g'::text) AS new_rel_file_path
   FROM uploads;


ALTER TABLE upload_rename OWNER TO hom;

--
-- Name: upload_rename; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE upload_rename TO peerweb;


--
-- PostgreSQL database dump complete
--

