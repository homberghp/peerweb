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
-- Name: prj_grp_tr; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW prj_grp_tr AS
 SELECT prj_grp.prjtg_id,
    prj_grp.snummer,
    prj_grp.prj_grp_open,
    prj_grp.written
   FROM prj_grp;


ALTER TABLE prj_grp_tr OWNER TO hom;

--
-- Name: VIEW prj_grp_tr; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW prj_grp_tr IS 'prj_grp with prj_id, milestone, prjm_id and grp_num dropped';


--
-- Name: prj_grp_tr; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE prj_grp_tr TO peerweb;


--
-- PostgreSQL database dump complete
--

