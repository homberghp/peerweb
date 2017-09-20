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
-- Name: prj_tutor_tr; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW prj_tutor_tr AS
 SELECT pt.prjm_id,
    pt.grp_num,
    t.tutor,
    pt.tutor_id,
    pt.prjtg_id,
    pt.prj_tutor_open,
    pt.assessment_complete
   FROM (prj_tutor pt
     JOIN tutor t ON ((pt.tutor_id = t.userid)));


ALTER TABLE prj_tutor_tr OWNER TO hom;

--
-- Name: VIEW prj_tutor_tr; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW prj_tutor_tr IS 'prj_tutor with prj_id and milestone dropped';


--
-- Name: prj_tutor_tr; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE prj_tutor_tr TO peerweb;


--
-- PostgreSQL database dump complete
--

