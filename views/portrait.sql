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
-- Name: portrait; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW portrait AS
 SELECT st.snummer,
    (('fotos/'::text || COALESCE((rf.snummer)::text, 'anonymous'::text)) || '.jpg'::text) AS photo
   FROM (student st
     LEFT JOIN registered_photos rf USING (snummer));


ALTER TABLE portrait OWNER TO hom;

--
-- Name: portrait; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE portrait TO peerweb;


--
-- PostgreSQL database dump complete
--

