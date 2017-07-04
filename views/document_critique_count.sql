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
-- Name: document_critique_count; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW document_critique_count AS
 SELECT count(document_critique.critique_id) AS critique_count,
    document_critique.doc_id
   FROM document_critique
  WHERE (document_critique.deleted = false)
  GROUP BY document_critique.doc_id;


ALTER TABLE document_critique_count OWNER TO hom;

--
-- Name: document_critique_count; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE document_critique_count TO peerweb;


--
-- PostgreSQL database dump complete
--

