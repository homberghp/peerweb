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
-- Name: tiny_portrait; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW tiny_portrait AS
 SELECT st.snummer,
    (('<img src=''fotos/'::text || COALESCE((rf.snummer)::text, 'anonymous'::text)) || '.jpg'' border=''0'' width=''18'' height=''27''/>'::text) AS portrait
   FROM (student st
     LEFT JOIN registered_photos rf USING (snummer));


ALTER TABLE tiny_portrait OWNER TO hom;

--
-- Name: tiny_portrait; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE tiny_portrait TO peerweb;


--
-- PostgreSQL database dump complete
--

