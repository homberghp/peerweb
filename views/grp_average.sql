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
-- Name: grp_average; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW grp_average AS
 SELECT pm.prj_id,
    av.criterium,
    pm.milestone,
    pt.grp_num,
    av.grp_avg
   FROM ((( SELECT assessment.prjtg_id,
            assessment.criterium,
            avg(assessment.grade) AS grp_avg
           FROM assessment
          GROUP BY assessment.prjtg_id, assessment.criterium) av
     JOIN prj_tutor pt ON ((av.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


ALTER TABLE grp_average OWNER TO hom;

--
-- Name: VIEW grp_average; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW grp_average IS 'Used by tutor/groupresult.php';


--
-- Name: grp_average; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE grp_average TO peerweb;


--
-- PostgreSQL database dump complete
--

