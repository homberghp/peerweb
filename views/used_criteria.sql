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
-- Name: used_criteria; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW used_criteria AS
 SELECT DISTINCT pm.prj_id,
    a.criterium AS used_criterium
   FROM ((assessment a
     JOIN prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


ALTER TABLE used_criteria OWNER TO hom;

--
-- Name: VIEW used_criteria; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW used_criteria IS 'used in criteria3';


--
-- Name: used_criteria; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE used_criteria TO peerweb;


--
-- PostgreSQL database dump complete
--

