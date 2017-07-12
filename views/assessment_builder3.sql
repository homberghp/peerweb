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
-- Name: assessment_builder3; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW assessment_builder3 AS
 SELECT c.snummer AS contestant,
    j.snummer AS judge,
    cr.criterium,
    0 AS grade,
    j.prjtg_id,
    pt.prjm_id
   FROM (((prj_grp j
     JOIN prj_grp c USING (prjtg_id))
     JOIN prj_tutor pt USING (prjtg_id))
     JOIN criteria_v cr ON ((pt.prjm_id = cr.prjm_id)))
  WHERE (j.snummer <> c.snummer);


ALTER TABLE assessment_builder3 OWNER TO hom;

--
-- Name: assessment_builder3; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE assessment_builder3 TO peerweb;


--
-- PostgreSQL database dump complete
--

