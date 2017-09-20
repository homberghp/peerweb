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
-- Name: available_assessment; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW available_assessment AS
 SELECT DISTINCT pm.prjm_id
   FROM ((assessment a
     JOIN prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjm_id)));


ALTER TABLE available_assessment OWNER TO hom;

--
-- Name: VIEW available_assessment; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW available_assessment IS 'used by iresult.php; tutor/groupresult.php';


--
-- Name: available_assessment; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE available_assessment TO peerweb;


--
-- PostgreSQL database dump complete
--

