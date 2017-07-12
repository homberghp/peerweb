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
-- Name: assessment_grp_open; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW assessment_grp_open AS
 SELECT pm.prj_id,
    pm.milestone,
    pt.grp_num,
    pt.prjtg_id,
        CASE
            WHEN (sum(
            CASE
                WHEN pg.prj_grp_open THEN 1
                ELSE 0
            END) > 0) THEN true
            ELSE false
        END AS open
   FROM ((prj_grp pg
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  GROUP BY pt.grp_num, pm.prj_id, pt.prjtg_id, pm.milestone
  ORDER BY pt.grp_num;


ALTER TABLE assessment_grp_open OWNER TO hom;

--
-- Name: VIEW assessment_grp_open; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW assessment_grp_open IS 'used for selector in groupresult';


--
-- Name: assessment_grp_open; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE assessment_grp_open TO peerweb;


--
-- PostgreSQL database dump complete
--

