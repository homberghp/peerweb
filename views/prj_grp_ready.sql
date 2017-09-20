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
-- Name: prj_grp_ready; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW prj_grp_ready AS
 SELECT prj_grp.prjtg_id,
    (bool_and(prj_grp.written) AND (NOT bool_and(prj_grp.prj_grp_open))) AS ready
   FROM prj_grp
  GROUP BY prj_grp.prjtg_id;


ALTER TABLE prj_grp_ready OWNER TO hom;

--
-- Name: prj_grp_ready; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE prj_grp_ready TO peerweb;


--
-- PostgreSQL database dump complete
--

