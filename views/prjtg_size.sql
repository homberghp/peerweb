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
-- Name: prjtg_size; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW prjtg_size AS
 SELECT count(*) AS size,
    prj_grp.prjtg_id
   FROM prj_grp
  GROUP BY prj_grp.prjtg_id;


ALTER TABLE prjtg_size OWNER TO hom;

--
-- Name: prjtg_size; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE prjtg_size TO peerweb;


--
-- PostgreSQL database dump complete
--

