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
-- Name: has_uploads; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW has_uploads AS
 SELECT DISTINCT prj_milestone.prj_id,
    prj_milestone.milestone
   FROM (prj_milestone
     JOIN uploaddocumenttypes USING (prj_id))
  ORDER BY prj_milestone.prj_id, prj_milestone.milestone;


ALTER TABLE has_uploads OWNER TO hom;

--
-- Name: has_uploads; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE has_uploads TO peerweb;


--
-- PostgreSQL database dump complete
--

