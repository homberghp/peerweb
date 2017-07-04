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
-- Name: stdresult; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW stdresult AS
 SELECT a.snummer,
    pm.prj_id,
    pt.grp_num,
    a.criterium,
    pm.milestone,
    a.grade
   FROM ((( SELECT assessment.contestant AS snummer,
            assessment.prjtg_id,
            assessment.criterium,
            avg(assessment.grade) AS grade
           FROM assessment
          GROUP BY assessment.contestant, assessment.prjtg_id, assessment.criterium) a
     JOIN prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


ALTER TABLE stdresult OWNER TO hom;

--
-- Name: VIEW stdresult; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW stdresult IS 'used by ~/_include/test/peerutils,tutor/groupresult.php';


--
-- Name: stdresult; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE stdresult TO peerweb;


--
-- PostgreSQL database dump complete
--

