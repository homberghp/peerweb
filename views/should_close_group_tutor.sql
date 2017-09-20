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
-- Name: should_close_group_tutor; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW should_close_group_tutor AS
 SELECT ((pt.prj_tutor_open = true) AND (pt.prj_tutor_open <> algo.any_group_open)) AS should_close,
    pt.prj_tutor_open,
    algo.any_group_open,
    pt.prjtg_id
   FROM (prj_tutor pt
     JOIN ( SELECT bool_or(prj_grp.prj_grp_open) AS any_group_open,
            prj_grp.prjtg_id
           FROM prj_grp
          GROUP BY prj_grp.prjtg_id) algo USING (prjtg_id));


ALTER TABLE should_close_group_tutor OWNER TO hom;

--
-- Name: should_close_group_tutor; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE should_close_group_tutor TO peerweb;


--
-- PostgreSQL database dump complete
--

