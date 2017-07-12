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
-- Name: doctype_upload_group_count; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW doctype_upload_group_count AS
 SELECT u.prjtg_id,
    u.doctype,
    count(u.upload_id) AS doc_count
   FROM uploads u
  GROUP BY u.prjtg_id, u.doctype;


ALTER TABLE doctype_upload_group_count OWNER TO hom;

--
-- Name: doctype_upload_group_count; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE doctype_upload_group_count TO peerweb;


--
-- PostgreSQL database dump complete
--

