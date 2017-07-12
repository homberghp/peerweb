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
-- Name: upload_count; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW upload_count AS
 SELECT uploads.snummer,
    count(uploads.snummer) AS document_count
   FROM uploads
  GROUP BY uploads.snummer;


ALTER TABLE upload_count OWNER TO hom;

--
-- Name: VIEW upload_count; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW upload_count IS 'documents per user';


--
-- Name: upload_count; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE upload_count TO peerweb;


--
-- PostgreSQL database dump complete
--

